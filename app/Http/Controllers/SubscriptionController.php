<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    use AuthorizesRequests;
    
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Display available subscription plans.
     */
    public function plans(): Response
    {
        $plans = SubscriptionPlan::where('is_active', true)->get();
        
        return Inertia::render('Subscriptions/Plans', [
            'plans' => $plans,
        ]);
    }
    
    /**
     * Show the subscription checkout page.
     */
    public function checkout(SubscriptionPlan $plan): Response
    {
        return Inertia::render('Subscriptions/Checkout', [
            'plan' => $plan,
        ]);
    }
    
    /**
     * Process the subscription purchase.
     */
    public function purchase(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'currency' => 'required|in:naira,dollar',
            'auto_renew' => 'boolean',
        ]);
        
        // Redirect to payment methods selection
        return redirect()->route('payment.methods', $plan->id)
            ->with('checkout_data', $validated);
    }
    
    /**
     * Display the user's active subscriptions.
     */
    public function mySubscriptions(): Response
    {
        $user = Auth::user();
        
        $activeSubscriptions = $user->subscriptions()
            ->with('plan')
            ->where('status', 'active')
            ->get();
            
        $expiredSubscriptions = $user->subscriptions()
            ->with('plan')
            ->where('status', 'expired')
            ->orWhere('status', 'cancelled')
            ->orderBy('end_date', 'desc')
            ->get();
            
        return Inertia::render('Subscriptions/MySubscriptions', [
            'activeSubscriptions' => $activeSubscriptions,
            'expiredSubscriptions' => $expiredSubscriptions,
        ]);
    }
    
    /**
     * Show subscription details.
     */
    public function show(Subscription $subscription): Response
    {
        $this->authorize('view', $subscription);
        
        $subscription->load('plan', 'transactions');
        
        return Inertia::render('Subscriptions/Show', [
            'subscription' => $subscription,
        ]);
    }
    
    /**
     * Cancel a subscription.
     */
    public function cancel(Request $request, Subscription $subscription): RedirectResponse
    {
        $this->authorize('update', $subscription);
        
        $this->subscriptionService->cancelSubscription($subscription);
        
        return redirect()->route('subscriptions.my')
            ->with('success', 'Subscription cancelled successfully.');
    }
    
    /**
     * Toggle auto-renewal for a subscription.
     */
    public function toggleAutoRenew(Request $request, Subscription $subscription): RedirectResponse
    {
        $this->authorize('update', $subscription);
        
        $subscription->auto_renew = !$subscription->auto_renew;
        $subscription->next_billing_date = $subscription->auto_renew ? $subscription->end_date : null;
        $subscription->save();
        
        $status = $subscription->auto_renew ? 'enabled' : 'disabled';
        
        return back()->with('success', "Auto-renewal {$status} successfully.");
    }
    
    /**
     * Renew a subscription.
     */
    public function renew(Request $request, Subscription $subscription): RedirectResponse
    {
        $this->authorize('update', $subscription);
        
        $validated = $request->validate([
            'payment_method' => 'required|string',
        ]);
        
        $this->subscriptionService->renewSubscription($subscription, [
            'payment_method' => $validated['payment_method'],
            'is_paid' => true, // Simulate immediate payment
            'payment_reference' => 'RENEWAL_' . time(),
            'payment_details' => [
                'status' => 'success',
                'transaction_id' => 'RENEWAL_' . uniqid(),
                'payment_date' => now()->toDateTimeString(),
            ],
        ]);
        
        return redirect()->route('subscriptions.my')
            ->with('success', 'Subscription renewed successfully.');
    }
} 