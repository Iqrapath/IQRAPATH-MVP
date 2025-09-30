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
     * Convert amount between currencies using real-time exchange rates.
     */
    public function convertAmount(float $amount, string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $rate = $this->getExchangeRate($fromCurrency, $toCurrency);
        return $amount * $rate;
    }

    /**
     * Get real-time exchange rate between currencies.
     */
    public function getExchangeRate(string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        // Try to get cached rate first
        $cacheKey = "exchange_rate_{$fromCurrency}_{$toCurrency}";
        $cachedRate = \Illuminate\Support\Facades\Cache::get($cacheKey);
        
        if ($cachedRate) {
            return $cachedRate;
        }

        // Fetch fresh rate from API
        $rate = $this->fetchExchangeRate($fromCurrency, $toCurrency);
        
        // Cache for 5 minutes
        \Illuminate\Support\Facades\Cache::put($cacheKey, $rate, 300);
        
        return $rate;
    }

    /**
     * Fetch exchange rate from API with dual fallback.
     */
    private function fetchExchangeRate(string $fromCurrency, string $toCurrency): float
    {
        // Primary API: Fixer.io
        $rate = $this->fetchFromFixer($fromCurrency, $toCurrency);
        
        if ($rate > 0) {
            return $rate;
        }

        // Fallback API: CurrencyLayer
        $rate = $this->fetchFromCurrencyLayer($fromCurrency, $toCurrency);
        
        if ($rate > 0) {
            return $rate;
        }

        // Final fallback: stored rates in financial_settings
        return $this->getStoredRate($fromCurrency, $toCurrency);
    }

    /**
     * Fetch rate from Fixer.io API.
     */
    private function fetchFromFixer(string $fromCurrency, string $toCurrency): float
    {
        try {
            $apiKey = config('services.fixer.api_key');
            if (!$apiKey) {
                return 0;
            }

            $url = "http://data.fixer.io/api/latest?access_key={$apiKey}&base={$fromCurrency}&symbols={$toCurrency}";
            $response = \Illuminate\Support\Facades\Http::timeout(5)->get($url);
            
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['rates'][$toCurrency])) {
                    return (float) $data['rates'][$toCurrency];
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Fixer.io API failed: ' . $e->getMessage());
        }

        return 0;
    }

    /**
     * Fetch rate from CurrencyLayer API.
     */
    private function fetchFromCurrencyLayer(string $fromCurrency, string $toCurrency): float
    {
        try {
            $apiKey = config('services.currencylayer.api_key');
            if (!$apiKey) {
                return 0;
            }

            $url = "http://api.currencylayer.com/live?access_key={$apiKey}&currencies={$toCurrency}&source={$fromCurrency}";
            $response = \Illuminate\Support\Facades\Http::timeout(5)->get($url);
            
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['quotes'][$fromCurrency . $toCurrency])) {
                    return (float) $data['quotes'][$fromCurrency . $toCurrency];
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('CurrencyLayer API failed: ' . $e->getMessage());
        }

        return 0;
    }

    /**
     * Get stored rate from financial_settings table.
     */
    private function getStoredRate(string $fromCurrency, string $toCurrency): float
    {
        $settingKey = "exchange_rate_{$fromCurrency}_{$toCurrency}";
        $rate = FinancialSetting::get($settingKey);
        
        if ($rate) {
            return (float) $rate;
        }

        // Default fallback rates
        $defaultRates = [
            'NGN' => ['USD' => 0.00067, 'EUR' => 0.00062, 'GBP' => 0.00053],
            'USD' => ['NGN' => 1500, 'EUR' => 0.85, 'GBP' => 0.79],
            'EUR' => ['NGN' => 1600, 'USD' => 1.18, 'GBP' => 0.93],
            'GBP' => ['NGN' => 1900, 'USD' => 1.27, 'EUR' => 1.08],
        ];

        return $defaultRates[$fromCurrency][$toCurrency] ?? 1.0;
    }

    /**
     * Update exchange rates in financial_settings table.
     */
    public function updateStoredRates(): void
    {
        $currencies = ['NGN', 'USD', 'EUR', 'GBP'];
        
        foreach ($currencies as $from) {
            foreach ($currencies as $to) {
                if ($from !== $to) {
                    $rate = $this->fetchExchangeRate($from, $to);
                    if ($rate > 0) {
                        $settingKey = "exchange_rate_{$from}_{$to}";
                        FinancialSetting::set($settingKey, $rate);
                    }
                }
            }
        }
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
