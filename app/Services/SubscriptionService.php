<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SubscriptionService
{
    /**
     * Validate subscription eligibility.
     */
    public function validateSubscriptionEligibility(User $user, SubscriptionPlan $plan): array
    {
        $errors = [];
        
        // Check if user role is student
        if ($user->role !== 'student') {
            $errors[] = 'Only students can subscribe to plans.';
        }
        
        // Check if plan is active
        if (!$plan->is_active) {
            $errors[] = 'This subscription plan is no longer available.';
        }
        
        // Check for active subscription
        $activeSubscription = $user->subscriptions()
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->first();
            
        if ($activeSubscription) {
            $errors[] = 'You already have an active subscription. Please wait for it to expire or cancel it first.';
        }
        
        return $errors;
    }

    /**
     * Create a new subscription.
     *
     * @param User $user
     * @param SubscriptionPlan $plan
     * @param array $data
     * @return Subscription
     */
    public function createSubscription(User $user, SubscriptionPlan $plan, array $data): Subscription
    {
        // Validate eligibility first
        $validationErrors = $this->validateSubscriptionEligibility($user, $plan);
        if (!empty($validationErrors)) {
            throw new \Exception(implode(' ', $validationErrors));
        }
        
        $startDate = Carbon::now();
        $endDate = $startDate->copy()->addMonths($plan->duration_months);
        
        $currency = strtoupper($data['currency'] ?? 'NGN');
        if (!in_array($currency, ['USD', 'NGN'])) {
            throw new \Exception('Invalid currency. Only USD and NGN are supported.');
        }
        
        $amount = $plan->getPriceForCurrency($currency);
        
        // Create the subscription
        $subscription = Subscription::create([
            'subscription_uuid' => Str::uuid(),
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'amount_paid' => $amount,
            'currency' => $currency,
            'status' => 'pending', // Will be updated to 'active' after payment
            'next_billing_date' => $data['auto_renew'] ?? false ? $endDate : null,
            'auto_renew' => $data['auto_renew'] ?? false,
            'payment_method' => $data['payment_method'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? null,
        ]);
        
        // Create the transaction record
        $this->createTransaction($subscription, [
            'amount' => $amount,
            'currency' => $currency,
            'type' => 'new_subscription',
            'status' => 'pending',
            'payment_method' => $data['payment_method'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? null,
            'payment_details' => $data['payment_details'] ?? null,
        ]);
        
        return $subscription;
    }
    
    /**
     * Activate a subscription after successful payment.
     *
     * @param Subscription $subscription
     * @param array $paymentData
     * @return Subscription
     */
    public function activateSubscription(Subscription $subscription, array $paymentData = []): Subscription
    {
        // Update subscription status
        $subscription->status = 'active';
        $subscription->payment_reference = $paymentData['payment_reference'] ?? $subscription->payment_reference;
        $subscription->save();
        
        // Update transaction status
        $transaction = $subscription->transactions()->latest()->first();
        if ($transaction) {
            $transaction->status = 'completed';
            $transaction->payment_reference = $paymentData['payment_reference'] ?? $transaction->payment_reference;
            $transaction->payment_details = array_merge($transaction->payment_details ?? [], $paymentData['payment_details'] ?? []);
            $transaction->save();
        }
        
        return $subscription;
    }
    
    /**
     * Cancel a subscription.
     *
     * @param Subscription $subscription
     * @return Subscription
     */
    public function cancelSubscription(Subscription $subscription): Subscription
    {
        $subscription->cancel();
        
        // Create a cancellation transaction if needed
        if ($subscription->transactions()->where('type', 'cancellation')->doesntExist()) {
            $this->createTransaction($subscription, [
                'amount' => 0,
                'currency' => $subscription->currency,
                'type' => 'cancellation',
                'status' => 'completed',
            ]);
        }
        
        return $subscription;
    }
    
    /**
     * Renew a subscription.
     *
     * @param Subscription $subscription
     * @param array $paymentData
     * @return Subscription
     */
    public function renewSubscription(Subscription $subscription, array $paymentData = []): Subscription
    {
        $plan = $subscription->plan;
        $amount = $plan->getPriceForCurrency($subscription->currency);
        
        // Create a renewal transaction
        $transaction = $this->createTransaction($subscription, [
            'amount' => $amount,
            'currency' => $subscription->currency,
            'type' => 'renewal',
            'status' => 'pending',
            'payment_method' => $paymentData['payment_method'] ?? $subscription->payment_method,
            'payment_reference' => $paymentData['payment_reference'] ?? null,
            'payment_details' => $paymentData['payment_details'] ?? null,
        ]);
        
        // If payment is already confirmed
        if (isset($paymentData['is_paid']) && $paymentData['is_paid']) {
            $transaction->markAsCompleted();
            $subscription->renew();
        } else {
            // For auto-renewal, attempt to charge the saved payment method
            if ($subscription->auto_renew && $subscription->payment_reference) {
                try {
                    // 1. Get the saved payment method from the user
                    $paymentMethod = \App\Models\PaymentMethod::where('user_id', $subscription->user_id)
                        ->where('gateway_token', $subscription->payment_reference)
                        ->where('is_active', true)
                        ->first();
                    
                    if (!$paymentMethod) {
                        throw new \Exception('Saved payment method not found or inactive');
                    }
                    
                    // Check if payment method is expired
                    if ($paymentMethod->isExpired()) {
                        throw new \Exception('Payment method has expired');
                    }
                    
                    // 2. Charge the payment method using the payment gateway
                    $paymentGatewayService = app(\App\Services\PaymentGatewayService::class);
                    $notificationService = app(\App\Services\NotificationService::class);
                    
                    $paymentResult = $paymentGatewayService->processPayment([
                        'gateway' => $paymentMethod->gateway,
                        'amount' => $amount,
                        'currency' => $subscription->currency,
                        'payment_method_id' => $paymentMethod->gateway_token,
                        'customer_email' => $subscription->user->email,
                        'description' => "Subscription renewal: {$plan->name}",
                        'return_url' => route('student.dashboard'),
                        'metadata' => [
                            'subscription_id' => $subscription->id,
                            'user_id' => $subscription->user_id,
                            'plan_id' => $plan->id,
                            'type' => 'renewal'
                        ]
                    ]);
                    
                    // 3. If successful, mark transaction as completed and renew subscription
                    if ($paymentResult['success']) {
                        $transaction->update([
                            'status' => 'completed',
                            'payment_reference' => $paymentResult['reference'] ?? $paymentResult['transaction_id'],
                            'payment_details' => $paymentResult
                        ]);
                        
                        $subscription->renew();
                        $paymentMethod->markAsUsed();
                        
                        // Send success notification
                        $notificationService->createNotification(
                            $subscription->user,
                            'subscription_renewed',
                            [
                                'title' => 'Subscription Renewed Successfully',
                                'body' => "Your {$plan->name} subscription has been automatically renewed for {$amount} {$subscription->currency}.",
                                'subscription_id' => $subscription->id,
                                'amount' => $amount,
                                'currency' => $subscription->currency,
                                'next_billing_date' => $subscription->end_date->format('F d, Y')
                            ],
                            'success'
                        );
                        
                        \Log::info('Auto-renewal successful', [
                            'subscription_id' => $subscription->id,
                            'user_id' => $subscription->user_id,
                            'amount' => $amount,
                            'currency' => $subscription->currency,
                            'transaction_id' => $transaction->id
                        ]);
                    } else {
                        // 4. If failed, send notification to user to update payment method
                        $transaction->update(['status' => 'failed']);
                        
                        $notificationService->createNotification(
                            $subscription->user,
                            'renewal_payment_failed',
                            [
                                'title' => 'Subscription Renewal Failed',
                                'body' => "We couldn't renew your {$plan->name} subscription. Please update your payment method to continue your subscription.",
                                'subscription_id' => $subscription->id,
                                'amount' => $amount,
                                'currency' => $subscription->currency,
                                'error_message' => $paymentResult['message'] ?? 'Payment processing failed'
                            ],
                            'error'
                        );
                        
                        \Log::warning('Auto-renewal payment failed', [
                            'subscription_id' => $subscription->id,
                            'user_id' => $subscription->user_id,
                            'error' => $paymentResult['message'] ?? 'Unknown error'
                        ]);
                    }
                    
                } catch (\Exception $e) {
                    // Log error and notify user
                    $transaction->update(['status' => 'failed']);
                    
                    \Log::error('Auto-renewal exception', [
                        'subscription_id' => $subscription->id,
                        'user_id' => $subscription->user_id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    // Send notification about the failure
                    try {
                        $notificationService = app(\App\Services\NotificationService::class);
                        $notificationService->createNotification(
                            $subscription->user,
                            'renewal_error',
                            [
                                'title' => 'Subscription Renewal Error',
                                'body' => "There was an error renewing your {$plan->name} subscription. Please contact support or update your payment method.",
                                'subscription_id' => $subscription->id,
                                'error_message' => $e->getMessage()
                            ],
                            'error'
                        );
                    } catch (\Exception $notificationError) {
                        \Log::error('Failed to send renewal error notification', [
                            'subscription_id' => $subscription->id,
                            'error' => $notificationError->getMessage()
                        ]);
                    }
                }
            }
        }
        
        return $subscription;
    }
    
    /**
     * Process a refund for a subscription.
     *
     * @param Subscription $subscription
     * @param string|null $reason
     * @return SubscriptionTransaction|null
     */
    public function processRefund(Subscription $subscription, ?string $reason = null): ?SubscriptionTransaction
    {
        $transaction = $subscription->transactions()
            ->where('type', 'new_subscription')
            ->orWhere('type', 'renewal')
            ->where('status', 'completed')
            ->latest()
            ->first();
            
        if (!$transaction) {
            return null;
        }
        
        // Cancel the subscription
        $this->cancelSubscription($subscription);
        
        // Process the refund
        return $transaction->processRefund($reason);
    }
    
    /**
     * Create a transaction for a subscription.
     *
     * @param Subscription $subscription
     * @param array $data
     * @return SubscriptionTransaction
     */
    protected function createTransaction(Subscription $subscription, array $data): SubscriptionTransaction
    {
        return SubscriptionTransaction::create([
            'transaction_uuid' => Str::uuid(),
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'type' => $data['type'],
            'status' => $data['status'] ?? 'pending',
            'payment_method' => $data['payment_method'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? null,
            'payment_details' => $data['payment_details'] ?? null,
        ]);
    }
    
    /**
     * Check for expired subscriptions and update their status.
     *
     * @return int Number of subscriptions updated
     */
    public function processExpiredSubscriptions(): int
    {
        $count = 0;
        $expiredSubscriptions = Subscription::where('status', 'active')
            ->where('end_date', '<', now())
            ->get();
            
        foreach ($expiredSubscriptions as $subscription) {
            if ($subscription->auto_renew) {
                // Attempt to renew
                $this->renewSubscription($subscription);
            } else {
                // Mark as expired
                $subscription->markAsExpired();
                $count++;
            }
        }
        
        return $count;
    }
} 