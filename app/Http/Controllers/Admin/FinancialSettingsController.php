<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinancialSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class FinancialSettingsController extends Controller
{
    /**
     * Display the financial settings page.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $settings = FinancialSetting::all()->keyBy('key');
        
        return Inertia::render('Admin/Settings/Financial', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update the financial settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'currency' => 'sometimes|string|max:10',
            'currency_symbol' => 'sometimes|string|max:10',
            'decimal_separator' => 'sometimes|string|max:1',
            'thousand_separator' => 'sometimes|string|max:1',
            'teacher_commission_rate' => 'sometimes|numeric|min:0|max:100',
            'platform_fee' => 'sometimes|numeric|min:0',
            'tax_rate' => 'sometimes|numeric|min:0|max:100',
            'min_withdrawal_amount' => 'sometimes|numeric|min:0',
            'payment_gateway' => 'sometimes|string|max:50',
            'payment_gateway_keys' => 'sometimes|json',
            'automatic_payouts' => 'sometimes|boolean',
            'payout_schedule' => 'sometimes|string|max:50',
        ]);

        foreach ($validated as $key => $value) {
            FinancialSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        // Clear the settings cache
        $this->clearSettingsCache();

        return redirect()->back()->with('success', 'Financial settings updated successfully.');
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

        FinancialSetting::updateOrCreate(
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
        Cache::forget('financial_settings');
    }
}
