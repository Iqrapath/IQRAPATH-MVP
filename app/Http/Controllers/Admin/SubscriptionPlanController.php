<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionPlanController extends Controller
{
    /**
     * Display a listing of the subscription plans.
     */
    public function index(): Response
    {
        $plans = SubscriptionPlan::withCount('subscriptions')
            ->withCount(['subscriptions as active_subscriptions_count' => function ($query) {
                $query->where('status', 'active');
            }])
            ->get();
            
        return Inertia::render('Admin/Subscriptions/Index', [
            'plans' => $plans,
        ]);
    }

    /**
     * Show the form for creating a new subscription plan.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Subscriptions/Create');
    }

    /**
     * Store a newly created subscription plan in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:subscription_plans,name',
            'description' => 'nullable|string',
            'price_naira' => 'required|numeric|min:0',
            'price_dollar' => 'required|numeric|min:0',
            'billing_cycle' => ['required', Rule::in(['monthly', 'quarterly', 'biannually', 'annually'])],
            'duration_months' => 'required|integer|min:1',
            'features' => 'nullable|array',
            'tags' => 'nullable|array',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:2048', // 2MB max
        ]);

        // Handle image upload if provided
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('subscription-plans', 'public');
            $validated['image_path'] = $path;
        }

        // Create the plan
        SubscriptionPlan::create($validated);

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'Subscription plan created successfully.');
    }

    /**
     * Display the specified subscription plan.
     */
    public function show(SubscriptionPlan $subscriptionPlan): Response
    {
        $subscriptionPlan->load(['subscriptions' => function ($query) {
            $query->with('user')->where('status', 'active');
        }]);
        
        return Inertia::render('Admin/Subscriptions/Show', [
            'plan' => $subscriptionPlan,
        ]);
    }

    /**
     * Show the form for editing the specified subscription plan.
     */
    public function edit(SubscriptionPlan $subscriptionPlan): Response
    {
        return Inertia::render('Admin/Subscriptions/Edit', [
            'plan' => $subscriptionPlan,
        ]);
    }

    /**
     * Update the specified subscription plan in storage.
     */
    public function update(Request $request, SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('subscription_plans')->ignore($subscriptionPlan->id)],
            'description' => 'nullable|string',
            'price_naira' => 'required|numeric|min:0',
            'price_dollar' => 'required|numeric|min:0',
            'billing_cycle' => ['required', Rule::in(['monthly', 'quarterly', 'biannually', 'annually'])],
            'duration_months' => 'required|integer|min:1',
            'features' => 'nullable|array',
            'tags' => 'nullable|array',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:2048', // 2MB max
        ]);

        // Handle image upload if provided
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($subscriptionPlan->image_path) {
                Storage::disk('public')->delete($subscriptionPlan->image_path);
            }
            
            $path = $request->file('image')->store('subscription-plans', 'public');
            $validated['image_path'] = $path;
        }

        // Update the plan
        $subscriptionPlan->update($validated);

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'Subscription plan updated successfully.');
    }

    /**
     * Remove the specified subscription plan from storage.
     */
    public function destroy(SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        // Check if the plan has active subscriptions
        $activeSubscriptions = $subscriptionPlan->subscriptions()->where('status', 'active')->exists();
        
        if ($activeSubscriptions) {
            return back()->with('error', 'Cannot delete a plan with active subscriptions.');
        }
        
        // Delete image if exists
        if ($subscriptionPlan->image_path) {
            Storage::disk('public')->delete($subscriptionPlan->image_path);
        }
        
        // Delete the plan
        $subscriptionPlan->delete();

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'Subscription plan deleted successfully.');
    }
    
    /**
     * Toggle the active status of a subscription plan.
     */
    public function toggleActive(SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        $subscriptionPlan->is_active = !$subscriptionPlan->is_active;
        $subscriptionPlan->save();
        
        $status = $subscriptionPlan->is_active ? 'activated' : 'deactivated';
        
        return back()->with('success', "Subscription plan {$status} successfully.");
    }
    
    /**
     * Duplicate a subscription plan.
     */
    public function duplicate(SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        $newPlan = $subscriptionPlan->replicate();
        $newPlan->name = $subscriptionPlan->name . ' (Copy)';
        $newPlan->is_active = false; // Set as inactive by default
        
        // Handle image duplication if exists
        if ($subscriptionPlan->image_path) {
            $originalPath = $subscriptionPlan->image_path;
            $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
            $newPath = 'subscription-plans/' . uniqid() . '.' . $extension;
            
            if (Storage::disk('public')->exists($originalPath)) {
                Storage::disk('public')->copy($originalPath, $newPath);
                $newPlan->image_path = $newPath;
            }
        }
        
        $newPlan->save();
        
        return redirect()->route('admin.subscriptions.edit', $newPlan)
            ->with('success', 'Subscription plan duplicated successfully.');
    }
    
    /**
     * View users enrolled in a subscription plan.
     */
    public function enrolledUsers(SubscriptionPlan $subscriptionPlan): Response
    {
        $subscriptions = $subscriptionPlan->subscriptions()
            ->with('user')
            ->where('status', 'active')
            ->paginate(15);
            
        return Inertia::render('Admin/Subscriptions/EnrolledUsers', [
            'plan' => $subscriptionPlan,
            'subscriptions' => $subscriptions,
        ]);
    }
} 