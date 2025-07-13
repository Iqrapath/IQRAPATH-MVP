<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class FeatureControlsController extends Controller
{
    /**
     * Display the feature controls page.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $features = FeatureFlag::all()->keyBy('key');
        
        return Inertia::render('Admin/Settings/Features', [
            'features' => $features,
        ]);
    }

    /**
     * Update the feature flags.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'enable_registration' => 'sometimes|boolean',
            'enable_teacher_applications' => 'sometimes|boolean',
            'enable_guardian_accounts' => 'sometimes|boolean',
            'enable_document_verification' => 'sometimes|boolean',
            'enable_zoom_integration' => 'sometimes|boolean',
            'enable_notifications' => 'sometimes|boolean',
            'enable_messaging' => 'sometimes|boolean',
            'enable_reviews' => 'sometimes|boolean',
            'enable_blog' => 'sometimes|boolean',
            'enable_forums' => 'sometimes|boolean',
            'enable_resources' => 'sometimes|boolean',
            'enable_progress_tracking' => 'sometimes|boolean',
        ]);

        foreach ($validated as $key => $value) {
            FeatureFlag::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        // Clear the features cache
        $this->clearFeaturesCache();

        return redirect()->back()->with('success', 'Feature controls updated successfully.');
    }

    /**
     * Toggle a single feature flag.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $key
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggle(Request $request, $key)
    {
        $validated = $request->validate([
            'enabled' => 'required|boolean',
        ]);

        FeatureFlag::updateOrCreate(
            ['key' => $key],
            ['value' => $validated['enabled']]
        );

        // Clear the features cache
        $this->clearFeaturesCache();

        return response()->json([
            'success' => true, 
            'message' => 'Feature ' . ($validated['enabled'] ? 'enabled' : 'disabled') . ' successfully.'
        ]);
    }

    /**
     * Clear the features cache.
     *
     * @return void
     */
    protected function clearFeaturesCache()
    {
        Cache::forget('feature_flags');
    }
}
