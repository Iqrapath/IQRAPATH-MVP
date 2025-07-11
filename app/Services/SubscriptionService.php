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
     * Create a new subscription.
     *
     * @param User $user
     * @param SubscriptionPlan $plan
     * @param array $data
     * @return Subscription
     */
    public function createSubscription(User $user, SubscriptionPlan $plan, array $data): Subscription
    {
        $startDate = Carbon::now();
        $endDate = $startDate->copy()->addMonths($plan->duration_months);
        
        $currency = $data['currency'] ?? 'naira';
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