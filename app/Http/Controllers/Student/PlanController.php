<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\EnrollmentRequest;
use App\Models\SubscriptionPlan;
use App\Services\FinancialService;
use App\Services\NotificationService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private FinancialService $financialService,
        private NotificationService $notificationService
    ) {}

    /**
     * Display the memorization plans landing page.
     */
    public function landing(Request $request): Response
    {
        $user = $request->user();
        
        // Get user's active subscription if any
        $activePlan = $user->subscriptions()
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->with('plan')
            ->first();
        
        return Inertia::render('student/plans/landing', [
            'user' => $user,
            'activePlan' => $activePlan,
        ]);
    }

    /**
     * Display all available subscription plans.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Get all active subscription plans ordered by price
        $plans = SubscriptionPlan::getByBillingCycle();
        
        // Get user's active subscription if any
        $activePlan = $user->subscriptions()
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->with('plan')
            ->first();
        
        // Get wallet balances (wallet stores balance in NGN, convert to USD)
        $wallet = $user->studentWallet;
        $balanceNGN = $wallet ? $wallet->balance : 0;
        
        // Convert NGN to USD for display
        $currencyService = app(\App\Services\CurrencyService::class);
        $balanceUSD = $balanceNGN > 0 ? $currencyService->convertAmount($balanceNGN, 'NGN', 'USD') : 0;
        
        $walletBalance = [
            'usd' => round($balanceUSD, 2),
            'ngn' => $balanceNGN,
        ];
        
        return Inertia::render('student/plans/index', [
            'plans' => $plans,
            'activePlan' => $activePlan,
            'walletBalance' => $walletBalance,
            'user' => $user,
        ]);
    }

    /**
     * Display a specific subscription plan.
     */
    public function show(Request $request, SubscriptionPlan $plan): Response
    {
        $user = $request->user();
        
        // Load student profile relationship with specific fields
        $user->load(['studentProfile' => function ($query) {
            $query->select(
                'id',
                'user_id',
                'age_group',
                'grade_level',
                'preferred_learning_times',
                'learning_goals',
                'additional_notes'
            );
        }]);
        
        // Check if plan is active
        if (!$plan->is_active) {
            return redirect()->route('student.plans.index')
                ->with('error', 'This plan is no longer available.');
        }
        
        // Get user's active subscription if any
        $activePlan = $user->subscriptions()
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->with('plan')
            ->first();
        
        // Get wallet balances (wallet stores balance in NGN, convert to USD)
        $wallet = $user->studentWallet;
        $balanceNGN = $wallet ? $wallet->balance : 0;
        
        // Convert NGN to USD for display
        $currencyService = app(\App\Services\CurrencyService::class);
        $balanceUSD = $balanceNGN > 0 ? $currencyService->convertAmount($balanceNGN, 'NGN', 'USD') : 0;
        
        $walletBalance = [
            'usd' => round($balanceUSD, 2),
            'ngn' => $balanceNGN,
        ];
        
        // Get or create student profile if it doesn't exist
        $studentProfile = $user->studentProfile;
        
        if (!$studentProfile) {
            // Create a student profile if it doesn't exist
            $studentProfile = $user->studentProfile()->create([
                'status' => 'active',
                'registration_date' => now(),
            ]);
        }
        
        // Prepare student profile data
        $studentProfileData = [
            'age_group' => $studentProfile->age_group,
            'grade_level' => $studentProfile->grade_level,
            'preferred_learning_times' => $studentProfile->preferred_learning_times,
            'learning_goals' => $studentProfile->learning_goals,
            'additional_notes' => $studentProfile->additional_notes,
        ];
        
        // Get user's default payment method
        $defaultPaymentMethod = $user->paymentMethods()
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
        
        return Inertia::render('student/plans/show', [
            'plan' => $plan,
            'activePlan' => $activePlan,
            'walletBalance' => $walletBalance,
            'defaultPaymentMethod' => $defaultPaymentMethod,
            'user' => array_merge($user->toArray(), [
                'studentProfile' => $studentProfileData,
            ]),
        ]);
    }

    /**
     * Process plan enrollment.
     */
    public function enroll(EnrollmentRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $data = $request->validated();
            
            // Get the selected plan
            $plan = SubscriptionPlan::findOrFail($data['plan_id']);
            
            // Validate subscription eligibility
            $validationErrors = $this->subscriptionService->validateSubscriptionEligibility($user, $plan);
            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => implode(' ', $validationErrors),
                    'code' => 'VALIDATION_FAILED'
                ], 422);
            }
            
            // Validate wallet balance if wallet payment is selected
            if ($data['payment_method'] === 'wallet') {
                $wallet = $user->studentWallet;
                $requiredAmount = $plan->getPriceForCurrency($data['currency']);
                
                // Convert required amount to NGN if needed (wallet balance is in NGN)
                $currencyService = app(\App\Services\CurrencyService::class);
                $requiredAmountNGN = $data['currency'] === 'NGN' ? $requiredAmount : 
                    $currencyService->convertAmount($requiredAmount, $data['currency'], 'NGN');
                
                $currentBalance = $wallet ? $wallet->balance : 0;
                
                if ($currentBalance < $requiredAmountNGN) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient wallet balance.',
                        'code' => 'INSUFFICIENT_FUNDS',
                        'data' => [
                            'required' => $requiredAmount,
                            'current' => $data['currency'] === 'NGN' ? $currentBalance : 
                                $currencyService->convertAmount($currentBalance, 'NGN', $data['currency']),
                            'shortfall' => $requiredAmount - ($data['currency'] === 'NGN' ? $currentBalance : 
                                $currencyService->convertAmount($currentBalance, 'NGN', $data['currency']))
                        ]
                    ], 422);
                }
            }
            
            // Create subscription through service
            $subscription = $this->subscriptionService->createSubscription($user, $plan, $data);
            
            // Process payment if wallet method
            if ($data['payment_method'] === 'wallet') {
                // Process wallet payment
                $this->financialService->processSubscriptionPayment(
                    $user,
                    $subscription,
                    $data['currency']
                );
                
                // Activate subscription
                $subscription = $this->subscriptionService->activateSubscription($subscription);
                
                // Send notifications
                $this->notificationService->createNotification([
                    'title' => 'Subscription Activated',
                    'body' => "Your {$plan->name} subscription has been activated successfully.",
                    'type' => 'subscription',
                    'sender_type' => 'system',
                    'user_id' => $user->id,
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Subscription enrolled successfully.',
                'data' => [
                    'subscription' => $subscription->load('plan'),
                    'redirect_url' => $data['payment_method'] === 'wallet' 
                        ? null 
                        : route('student.plans.payment', $subscription->subscription_uuid)
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Plan enrollment failed', [
                'user_id' => $request->user()->id,
                'plan_id' => $data['plan_id'] ?? null,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your enrollment. Please try again.',
                'code' => 'ENROLLMENT_FAILED'
            ], 500);
        }
    }

    /**
     * Update auto-renewal setting for a subscription.
     */
    public function updateAutoRenewal(Request $request, string $subscriptionUuid): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Validate request
            $request->validate([
                'auto_renew' => 'required|boolean',
            ]);
            
            // Find subscription
            $subscription = \App\Models\Subscription::where('subscription_uuid', $subscriptionUuid)
                ->where('user_id', $user->id)
                ->firstOrFail();
            
            // Check if subscription is active
            if ($subscription->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only active subscriptions can have auto-renewal updated.',
                ], 422);
            }
            
            // Toggle auto-renewal
            $subscription->toggleAutoRenewal($request->auto_renew);
            
            // Send notification
            $this->notificationService->createNotification([
                'title' => 'Auto-Renewal Updated',
                'body' => $request->auto_renew 
                    ? 'Auto-renewal has been enabled for your subscription.' 
                    : 'Auto-renewal has been disabled for your subscription.',
                'type' => 'subscription',
                'sender_type' => 'system',
                'user_id' => $user->id,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Auto-renewal setting updated successfully.',
                'data' => [
                    'subscription' => $subscription->load('plan'),
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Auto-renewal update failed', [
                'user_id' => $request->user()->id,
                'subscription_uuid' => $subscriptionUuid,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating auto-renewal. Please try again.',
            ], 500);
        }
    }
}