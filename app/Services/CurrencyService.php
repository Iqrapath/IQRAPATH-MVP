<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FinancialSetting;

class CurrencyService
{
    /**
     * Get all available currencies.
     */
    public function getAvailableCurrencies(): array
    {
        return [
            [
                'value' => 'NGN',
                'label' => 'Nigerian Naira (NGN)',
                'symbol' => '₦',
                'is_default' => true
            ],
            [
                'value' => 'USD',
                'label' => 'US Dollar (USD)',
                'symbol' => '$',
                'is_default' => false
            ],
            [
                'value' => 'EUR',
                'label' => 'Euro (EUR)',
                'symbol' => '€',
                'is_default' => false
            ],
            [
                'value' => 'GBP',
                'label' => 'British Pound (GBP)',
                'symbol' => '£',
                'is_default' => false
            ]
        ];
    }

    /**
     * Get the platform's default currency.
     */
    public function getPlatformCurrency(): string
    {
        $setting = FinancialSetting::where('setting_key', 'platform_currency')->first();
        return $setting ? $setting->setting_value : 'NGN';
    }

    /**
     * Get currency symbol by currency code.
     */
    public function getCurrencySymbol(string $currencyCode): string
    {
        $currencies = $this->getAvailableCurrencies();
        $currency = collect($currencies)->firstWhere('value', $currencyCode);
        return $currency ? $currency['symbol'] : '₦';
    }

    /**
     * Get currency label by currency code.
     */
    public function getCurrencyLabel(string $currencyCode): string
    {
        $currencies = $this->getAvailableCurrencies();
        $currency = collect($currencies)->firstWhere('value', $currencyCode);
        return $currency ? $currency['label'] : 'Nigerian Naira (NGN)';
    }

    /**
     * Check if multi-currency mode is enabled.
     */
    public function isMultiCurrencyEnabled(): bool
    {
        $setting = FinancialSetting::where('setting_key', 'multi_currency_mode')->first();
        return $setting ? $setting->setting_value === 'true' : true;
    }

    /**
     * Format amount with currency symbol.
     */
    public function formatAmount(float $amount, string $currencyCode = 'NGN'): string
    {
        $symbol = $this->getCurrencySymbol($currencyCode);
        
        // Format number with appropriate decimal places
        $formattedAmount = number_format($amount, 2);
        
        // Add currency symbol based on currency
        if (in_array($currencyCode, ['USD', 'EUR', 'GBP'])) {
            return $symbol . $formattedAmount;
        } else {
            // For NGN and other currencies, symbol comes after
            return $formattedAmount . ' ' . $symbol;
        }
    }

    /**
     * Convert amount between currencies (basic conversion rates).
     * In a real application, you would use a live currency API.
     */
    public function convertAmount(float $amount, string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        // Basic conversion rates (these should be fetched from a live API in production)
        $rates = [
            'NGN' => ['USD' => 0.00067, 'EUR' => 0.00062, 'GBP' => 0.00053],
            'USD' => ['NGN' => 1500, 'EUR' => 0.85, 'GBP' => 0.79],
            'EUR' => ['NGN' => 1600, 'USD' => 1.18, 'GBP' => 0.93],
            'GBP' => ['NGN' => 1900, 'USD' => 1.27, 'EUR' => 1.08],
        ];

        if (isset($rates[$fromCurrency][$toCurrency])) {
            return $amount * $rates[$fromCurrency][$toCurrency];
        }

        return $amount; // Return original amount if conversion not available
    }

    /**
     * Get teacher's preferred currency or platform default.
     */
    public function getTeacherPreferredCurrency(int $teacherId): string
    {
        $teacherProfile = \App\Models\TeacherProfile::where('user_id', $teacherId)->first();
        
        if ($teacherProfile && $teacherProfile->preferred_currency) {
            return $teacherProfile->preferred_currency;
        }

        return $this->getPlatformCurrency();
    }

    /**
     * Update teacher's preferred currency.
     */
    public function updateTeacherPreferredCurrency(int $teacherId, string $currencyCode): bool
    {
        $teacherProfile = \App\Models\TeacherProfile::where('user_id', $teacherId)->first();
        
        if (!$teacherProfile) {
            return false;
        }

        // Validate currency code
        $availableCurrencies = collect($this->getAvailableCurrencies())->pluck('value')->toArray();
        if (!in_array($currencyCode, $availableCurrencies)) {
            return false;
        }

        $teacherProfile->update(['preferred_currency' => $currencyCode]);
        return true;
    }
}
