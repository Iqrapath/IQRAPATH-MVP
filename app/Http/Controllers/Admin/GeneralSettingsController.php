<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\FileUploadValidationService;
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
        $settings = SystemSetting::all()->keyBy('setting_key');
        
        // Get file upload limits
        $fileUploadLimits = FileUploadValidationService::getAllFileLimits();
        
        return Inertia::render('Admin/Settings/General', [
            'settings' => $settings,
            'fileUploadLimits' => $fileUploadLimits,
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
            // File upload size settings
            'file_upload_max_size_profile_photo' => 'sometimes|integer|min:100|max:51200', // 100KB to 50MB
            'file_upload_max_size_document' => 'sometimes|integer|min:100|max:51200',
            'file_upload_max_size_video' => 'sometimes|integer|min:100|max:102400', // 100KB to 100MB
            'file_upload_max_size_attachment' => 'sometimes|integer|min:100|max:102400',
        ]);

        foreach ($validated as $key => $value) {
            SystemSetting::updateOrCreate(
                ['setting_key' => $key],
                ['setting_value' => $value]
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
            ['setting_key' => $key],
            ['setting_value' => $validated['value']]
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
        Cache::forget('all_system_settings');
        SystemSetting::clearCache();
    }
}
