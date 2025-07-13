<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class GeneralSettingsController extends Controller
{
    /**
     * Display the general settings page.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $settings = SystemSetting::all()->keyBy('key');
        
        return Inertia::render('Admin/Settings/General', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update the general settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'sometimes|string|max:255',
            'site_description' => 'sometimes|string|max:1000',
            'contact_email' => 'sometimes|email|max:255',
            'contact_phone' => 'sometimes|string|max:20',
            'logo_path' => 'sometimes|nullable|string|max:255',
            'favicon_path' => 'sometimes|nullable|string|max:255',
            'primary_color' => 'sometimes|string|max:20',
            'secondary_color' => 'sometimes|string|max:20',
            'timezone' => 'sometimes|string|max:100',
            'date_format' => 'sometimes|string|max:50',
            'time_format' => 'sometimes|string|max:50',
            'maintenance_mode' => 'sometimes|boolean',
        ]);

        foreach ($validated as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        // Clear the settings cache
        $this->clearSettingsCache();

        return redirect()->back()->with('success', 'General settings updated successfully.');
    }

    /**
     * Update a single setting.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $key
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSingle(Request $request, $key)
    {
        $validated = $request->validate([
            'value' => 'required',
        ]);

        SystemSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $validated['value']]
        );

        // Clear the settings cache
        $this->clearSettingsCache();

        return response()->json(['success' => true, 'message' => 'Setting updated successfully.']);
    }

    /**
     * Clear the settings cache.
     *
     * @return void
     */
    protected function clearSettingsCache()
    {
        Cache::forget('system_settings');
    }
}
