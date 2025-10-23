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
    public function index(Request $request): Response
    {
        $query = SubscriptionPlan::withCount('subscriptions');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $status = $request->get('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Filter by billing cycle
        if ($request->filled('billing_cycle')) {
            $query->where('billing_cycle', $request->get('billing_cycle'));
        }

        $plans = $query->orderBy('created_at', 'desc')->paginate(10);

        // Get statistics
        $stats = [
            'total_plans' => SubscriptionPlan::count(),
            'active_plans' => SubscriptionPlan::where('is_active', true)->count(),
            'total_subscriptions' => Subscription::count(),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'monthly_revenue' => Subscription::where('status', 'active')
                ->whereMonth('created_at', now()->month)
                ->sum('amount_paid'),
        ];
            
        return Inertia::render('admin/subscriptions/index', [
            'plans' => $plans,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'billing_cycle']),
        ]);
    }

    /**
     * Show the form for creating a new subscription plan.
     */
    public function create(): Response
    {
        return Inertia::render('admin/subscriptions/create');
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
            'billing_cycle' => ['required', Rule::in(['monthly', 'annual'])],
            'duration_months' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) use ($request) {
                    $billingCycle = $request->input('billing_cycle');
                    if ($billingCycle === 'monthly' && $value != 1) {
                        $fail('Monthly plans must have duration of 1 month.');
                    }
                    if ($billingCycle === 'annual' && $value != 12) {
                        $fail('Annual plans must have duration of 12 months.');
                    }
                },
            ],
            'features' => 'nullable|string', // Changed from array to string
            'tags' => 'nullable|string', // Changed from array to string
            'is_active' => 'required|boolean',
            'image' => 'nullable|image|max:2048', // 2MB max
        ]);

        // Parse JSON strings to arrays
        if ($validated['features']) {
            $validated['features'] = json_decode($validated['features'], true) ?: [];
        }
        if ($validated['tags']) {
            $validated['tags'] = json_decode($validated['tags'], true) ?: [];
        }

        // Convert is_active to boolean
        $validated['is_active'] = filter_var($validated['is_active'], FILTER_VALIDATE_BOOLEAN);

        // Handle image upload if provided
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('subscription-plans', 'public');
            $validated['image_path'] = $path;
        }

        // Create the plan
        SubscriptionPlan::create($validated);

        return redirect()->route('admin.subscription-plans.index')
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
        
        return Inertia::render('admin/subscriptions/show', [
            'plan' => $subscriptionPlan,
        ]);
    }

    /**
     * Show the form for editing the specified subscription plan.
     */
    public function edit(SubscriptionPlan $subscriptionPlan): Response
    {
        return Inertia::render('admin/subscriptions/edit', [
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
            'billing_cycle' => ['required', Rule::in(['monthly', 'annual'])],
            'duration_months' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) use ($request) {
                    $billingCycle = $request->input('billing_cycle');
                    if ($billingCycle === 'monthly' && $value != 1) {
                        $fail('Monthly plans must have duration of 1 month.');
                    }
                    if ($billingCycle === 'annual' && $value != 12) {
                        $fail('Annual plans must have duration of 12 months.');
                    }
                },
            ],
            'features' => 'nullable|string', // Changed from array to string
            'tags' => 'nullable|string', // Changed from array to string
            'is_active' => 'required|boolean',
            'image' => 'nullable|image|max:2048', // 2MB max
        ]);

        // Parse JSON strings to arrays
        if ($validated['features']) {
            $validated['features'] = json_decode($validated['features'], true) ?: [];
        }
        if ($validated['tags']) {
            $validated['tags'] = json_decode($validated['tags'], true) ?: [];
        }

        // Convert is_active to boolean
        $validated['is_active'] = filter_var($validated['is_active'], FILTER_VALIDATE_BOOLEAN);

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

        return redirect()->route('admin.subscription-plans.index')
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

        return redirect()->route('admin.subscription-plans.index')
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
        
        return redirect()->route('admin.subscription-plans.edit', $newPlan)
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
            
        return Inertia::render('admin/subscriptions/enrolled-users', [
            'plan' => $subscriptionPlan,
            'subscriptions' => $subscriptions,
        ]);
    }
} 