<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecuritySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class SecuritySettingsController extends Controller
{
    /**
     * Display the security settings page.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $settings = SecuritySetting::all()->keyBy('key');
        
        return Inertia::render('Admin/Settings/Security', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update the security settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'password_min_length' => 'sometimes|integer|min:6|max:32',
            'password_requires_letters' => 'sometimes|boolean',
            'password_requires_numbers' => 'sometimes|boolean',
            'password_requires_symbols' => 'sometimes|boolean',
            'password_requires_mixed_case' => 'sometimes|boolean',
            'password_expiry_days' => 'sometimes|integer|min:0',
            'login_max_attempts' => 'sometimes|integer|min:1',
            'login_lockout_minutes' => 'sometimes|integer|min:1',
            'session_lifetime_minutes' => 'sometimes|integer|min:1',
            'enable_2fa' => 'sometimes|boolean',
            'force_2fa_for_admins' => 'sometimes|boolean',
            'force_2fa_for_teachers' => 'sometimes|boolean',
            'ip_restriction_enabled' => 'sometimes|boolean',
            'allowed_ip_addresses' => 'sometimes|array',
            'allowed_ip_addresses.*' => 'sometimes|ip',
        ]);

        foreach ($validated as $key => $value) {
            // Handle array values
            if (is_array($value) && $key === 'allowed_ip_addresses') {
                $value = json_encode($value);
            }
            
            SecuritySetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        // Clear the settings cache
        $this->clearSettingsCache();

        return redirect()->back()->with('success', 'Security settings updated successfully.');
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

        SecuritySetting::updateOrCreate(
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
        Cache::forget('security_settings');
    }
}
