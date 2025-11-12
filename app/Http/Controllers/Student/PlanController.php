<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\EnrollmentRequest;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\FinancialService;
use App\Services\NotificationService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
        $balanceNGN = $wallet ? (float) $wallet->balance : 0.0;
        
        // Convert NGN to USD for display
        $currencyService = app(\App\Services\CurrencyService::class);
        $balanceUSD = $balanceNGN > 0 ? $currencyService->convertAmount((float) $balanceNGN, 'NGN', 'USD') : 0;
        
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
        $balanceNGN = $wallet ? (float) $wallet->balance : 0.0;
        
        // Convert NGN to USD for display
        $currencyService = app(\App\Services\CurrencyService::class);
        $balanceUSD = $balanceNGN > 0 ? $currencyService->convertAmount((float) $balanceNGN, 'NGN', 'USD') : 0;
        
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
                    $currencyService->convertAmount((float) $requiredAmount, $data['currency'], 'NGN');
                
                $currentBalance = $wallet ? (float) $wallet->balance : 0.0;
                
                if ($currentBalance < $requiredAmountNGN) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient wallet balance.',
                        'code' => 'INSUFFICIENT_FUNDS',
                        'data' => [
                            'required' => $requiredAmount,
                            'current' => $data['currency'] === 'NGN' ? $currentBalance : 
                                $currencyService->convertAmount((float) $currentBalance, 'NGN', $data['currency']),
                            'shortfall' => $requiredAmount - ($data['currency'] === 'NGN' ? $currentBalance : 
                                $currencyService->convertAmount((float) $currentBalance, 'NGN', $data['currency']))
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
                $this->notificationService->createNotification(
                    $user,
                    'subscription_activated',
                    [
                        'title' => 'Subscription Activated',
                        'body' => "Your {$plan->name} subscription has been activated successfully.",
                        'subscription_id' => $subscription->id,
                        'plan_name' => $plan->name,
                    ],
                    'success'
                );
            }
            
            // Determine redirect URL based on payment method
            $redirectUrl = null;
            $message = 'Subscription enrolled successfully.';
            
            if ($data['payment_method'] === 'wallet') {
                // Wallet payment already processed, subscription activated
                $message = 'Subscription activated successfully.';
            } else {
                // Redirect to payment page for card, bank transfer, or PayPal
                $redirectUrl = route('student.plans.payment', $subscription->subscription_uuid);
                $message = 'Please complete payment to activate your subscription.';
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'subscription' => $subscription->load('plan'),
                    'redirect_url' => $redirectUrl
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
    public function updateAutoRenewal(Request $request, string $subscriptionUuid): RedirectResponse
    {
        try {
            $user = $request->user();
            
            // Find subscription
            $subscription = \App\Models\Subscription::where('subscription_uuid', $subscriptionUuid)
                ->where('user_id', $user->id)
                ->firstOrFail();
            
            // Check if subscription is active
            if ($subscription->status !== 'active') {
                return back()->with('error', 'Only active subscriptions can have auto-renewal updated.');
            }
            
            // Toggle auto-renewal (flip the current value)
            $newAutoRenewValue = !$subscription->auto_renew;
            $subscription->update(['auto_renew' => $newAutoRenewValue]);
            
            // Send notification
            $this->notificationService->createNotification(
                $user,
                'auto_renewal_updated',
                [
                    'title' => 'Auto-Renewal Updated',
                    'body' => $newAutoRenewValue 
                        ? 'Auto-renewal has been enabled for your subscription.' 
                        : 'Auto-renewal has been disabled for your subscription.',
                    'subscription_id' => $subscription->id,
                ],
                'info'
            );
            
            return back()->with('success', 'Auto-renewal setting updated successfully.');
            
        } catch (\Exception $e) {
            \Log::error('Auto-renewal update failed', [
                'user_id' => $request->user()->id,
                'subscription_uuid' => $subscriptionUuid,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'An error occurred while updating auto-renewal. Please try again.');
        }
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(Request $request, string $subscriptionUuid)
    {
        try {
            $user = $request->user();
            
            $subscription = Subscription::where('subscription_uuid', $subscriptionUuid)
                ->where('user_id', $user->id)
                ->with('plan')
                ->firstOrFail();
            
            // Verify subscription can be cancelled
            if ($subscription->status !== 'active') {
                return back()->with('error', 'Only active subscriptions can be cancelled.');
            }
            
            // Cancel the subscription
            $this->subscriptionService->cancelSubscription($subscription);
            
            // Send notification
            $this->notificationService->createNotification(
                $user,
                'subscription_cancelled',
                [
                    'title' => 'Subscription Cancelled',
                    'body' => "Your {$subscription->plan->name} subscription has been cancelled successfully.",
                    'subscription_id' => $subscription->id,
                    'plan_name' => $subscription->plan->name,
                ],
                'info'
            );
            
            return redirect()->route('student.plans.index')->with('success', 'Subscription cancelled successfully.');
            
        } catch (\Exception $e) {
            \Log::error('Subscription cancellation failed', [
                'user_id' => $request->user()->id,
                'subscription_uuid' => $subscriptionUuid,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'An error occurred while cancelling the subscription. Please try again.');
        }
    }

    /**
     * Match teachers based on student preferences
     */
    public function matchTeachers(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'student_age' => 'required|integer|min:5|max:100',
            'preferred_subject' => 'required|string',
            'best_time' => 'required|string|in:morning,afternoon,evening',
            'memorization_level' => 'required|string|in:juz-amma,half-quran,full-quran',
        ]);

        try {
            // MATCHING ALGORITHM
            // Step 1: Get base pool of verified, active teachers
            $teachersQuery = \App\Models\User::where('role', 'teacher')
                ->where('account_status', 'active')
                ->whereHas('teacherProfile', function ($query) {
                    $query->where('verified', true);
                })
                ->with(['teacherProfile', 'teacherAvailabilities']);

            // Step 2: Filter by availability time preference
            $bestTime = $request->best_time;
            $timeRanges = [
                'morning' => ['06:00:00', '11:59:59'],
                'afternoon' => ['12:00:00', '16:59:59'],
                'evening' => ['17:00:00', '21:59:59'],
            ];

            if (isset($timeRanges[$bestTime])) {
                [$startTime, $endTime] = $timeRanges[$bestTime];
                
                $teachersQuery->whereHas('teacherAvailabilities', function ($query) use ($startTime, $endTime) {
                    $query->where('is_active', true)
                          ->where('holiday_mode', false)
                          ->where(function ($q) use ($startTime, $endTime) {
                              // Check if teacher's availability overlaps with requested time
                              $q->where(function ($timeQuery) use ($startTime, $endTime) {
                                  $timeQuery->where('start_time', '<=', $endTime)
                                           ->where('end_time', '>=', $startTime);
                              })
                              // Or check preferred_teaching_hours
                              ->orWhere('preferred_teaching_hours', 'like', '%' . ucfirst($startTime < '12:00:00' ? 'morning' : ($startTime < '17:00:00' ? 'afternoon' : 'evening')) . '%');
                          });
                });
            }

            // Step 3: Get teachers and calculate match scores
            $teachers = $teachersQuery->get()
                ->map(function ($teacher) use ($request) {
                    $profile = $teacher->teacherProfile;
                    
                    // Calculate match score (0-100)
                    $matchScore = 0;
                    
                    // Rating weight (40 points max)
                    $rating = $profile->rating ?? 0;
                    $matchScore += ($rating / 5) * 40;
                    
                    // Experience weight (30 points max)
                    $experienceYears = (int) filter_var($profile->experience_years ?? '0', FILTER_SANITIZE_NUMBER_INT);
                    $matchScore += min($experienceYears * 5, 30);
                    
                    // Reviews count weight (20 points max)
                    $reviewsCount = $profile->reviews_count ?? 0;
                    $matchScore += min($reviewsCount * 2, 20);
                    
                    // Availability match (10 points)
                    if ($teacher->teacherAvailabilities->isNotEmpty()) {
                        $matchScore += 10;
                    }
                    
                    return [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                        'image' => $teacher->avatar,
                        'subjects' => 'Quran Memorization & Islamic Studies',
                        'rating' => $rating ?: 4.5,
                        'reviews_count' => $reviewsCount,
                        'experience_years' => $profile->experience_years ?? '2+ years',
                        'price_naira' => $profile->hourly_rate_ngn ?? 2000,
                        'bio' => $profile->bio ?? 'Experienced Quran teacher dedicated to helping students memorize the Holy Quran.',
                        'match_score' => round($matchScore, 1),
                    ];
                })
                // Step 4: Sort by match score (highest first)
                ->sortByDesc('match_score')
                // Step 5: Take top 6 matches
                ->take(6)
                ->values();

            if ($teachers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No teachers found matching your preferences. Please try adjusting your criteria.',
                    'matched_teachers' => []
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "Found {$teachers->count()} teacher(s) perfect for your memorization journey!",
                'matched_teachers' => $teachers
            ]);

        } catch (\Exception $e) {
            \Log::error('Teacher matching failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while finding teachers. Please try again.',
                'matched_teachers' => []
            ], 500);
        }
    }
}
