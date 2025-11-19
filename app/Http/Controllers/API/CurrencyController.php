<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function __construct(
        private CurrencyService $currencyService
    ) {}

    /**
     * Convert amount between currencies.
     */
    public function convert(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'from' => 'required|string|in:USD,NGN',
            'to' => 'required|string|in:USD,NGN',
        ]);

        try {
            $amount = (float) $request->input('amount');
            $fromCurrency = $request->input('from');
            $toCurrency = $request->input('to');

            if ($amount <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Amount must be greater than 0',
                ], 400);
            }

            $convertedAmount = $this->currencyService->convertAmount(
                $amount,
                $fromCurrency,
                $toCurrency
            );

            $exchangeRate = $this->currencyService->getExchangeRate($fromCurrency, $toCurrency);

            return response()->json([
                'success' => true,
                'data' => [
                    'original_amount' => $amount,
                    'converted_amount' => round($convertedAmount, 2),
                    'from_currency' => $fromCurrency,
                    'to_currency' => $toCurrency,
                    'exchange_rate' => $exchangeRate,
                    'formatted_original' => $this->currencyService->formatAmount($amount, $fromCurrency),
                    'formatted_converted' => $this->currencyService->formatAmount($convertedAmount, $toCurrency),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Currency conversion failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current exchange rate between currencies.
     */
    public function exchangeRate(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|string|in:USD,NGN',
            'to' => 'required|string|in:USD,NGN',
        ]);

        try {
            $fromCurrency = $request->input('from');
            $toCurrency = $request->input('to');

            $rate = $this->currencyService->getExchangeRate($fromCurrency, $toCurrency);

            return response()->json([
                'success' => true,
                'data' => [
                    'from_currency' => $fromCurrency,
                    'to_currency' => $toCurrency,
                    'exchange_rate' => $rate,
                    'rate_display' => "1 {$fromCurrency} = {$rate} {$toCurrency}",
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get exchange rate: ' . $e->getMessage(),
            ], 500);
        }
    }
}