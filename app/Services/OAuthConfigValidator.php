<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class OAuthConfigValidator
{
    /**
     * Validate all OAuth configurations
     * 
     * @return array
     */
    public function validateAll(): array
    {
        $results = [
            'google' => $this->validateGoogleConfig(),
            'facebook' => $this->validateFacebookConfig(),
        ];

        // Log overall validation status
        $allValid = $results['google']['valid'] && $results['facebook']['valid'];
        
        if (!$allValid) {
            Log::warning('OAuth configuration validation failed', $results);
        } else {
            Log::info('OAuth configuration validation passed');
        }

        return $results;
    }

    /**
     * Validate Google OAuth configuration
     * 
     * @return array
     */
    public function validateGoogleConfig(): array
    {
        $clientId = Config::get('services.google.client_id');
        $clientSecret = Config::get('services.google.client_secret');
        $redirectUri = Config::get('services.google.redirect');

        $errors = [];
        $warnings = [];

        // Check client ID
        if (empty($clientId)) {
            $errors[] = 'GOOGLE_CLIENT_ID is not configured';
        } elseif (strlen($clientId) < 20) {
            $warnings[] = 'GOOGLE_CLIENT_ID appears to be invalid (too short)';
        }

        // Check client secret
        if (empty($clientSecret)) {
            $errors[] = 'GOOGLE_CLIENT_SECRET is not configured';
        } elseif (strlen($clientSecret) < 20) {
            $warnings[] = 'GOOGLE_CLIENT_SECRET appears to be invalid (too short)';
        }

        // Check redirect URI
        if (empty($redirectUri)) {
            $errors[] = 'GOOGLE_REDIRECT_URI is not configured';
        } else {
            // Validate redirect URI format
            if (!filter_var($redirectUri, FILTER_VALIDATE_URL)) {
                $errors[] = 'GOOGLE_REDIRECT_URI is not a valid URL';
            } elseif (!str_contains($redirectUri, '/auth/google/callback')) {
                $warnings[] = 'GOOGLE_REDIRECT_URI does not match expected callback path (/auth/google/callback)';
            }
        }

        $valid = empty($errors);

        if (!$valid) {
            Log::warning('Google OAuth configuration invalid', [
                'errors' => $errors,
                'warnings' => $warnings,
            ]);
        }

        return [
            'provider' => 'google',
            'valid' => $valid,
            'errors' => $errors,
            'warnings' => $warnings,
            'config' => [
                'client_id_set' => !empty($clientId),
                'client_secret_set' => !empty($clientSecret),
                'redirect_uri' => $redirectUri,
            ],
        ];
    }

    /**
     * Validate Facebook OAuth configuration
     * 
     * @return array
     */
    public function validateFacebookConfig(): array
    {
        $clientId = Config::get('services.facebook.client_id');
        $clientSecret = Config::get('services.facebook.client_secret');
        $redirectUri = Config::get('services.facebook.redirect');

        $errors = [];
        $warnings = [];

        // Check client ID
        if (empty($clientId)) {
            $errors[] = 'FACEBOOK_CLIENT_ID is not configured';
        } elseif (strlen($clientId) < 10) {
            $warnings[] = 'FACEBOOK_CLIENT_ID appears to be invalid (too short)';
        }

        // Check client secret
        if (empty($clientSecret)) {
            $errors[] = 'FACEBOOK_CLIENT_SECRET is not configured';
        } elseif (strlen($clientSecret) < 20) {
            $warnings[] = 'FACEBOOK_CLIENT_SECRET appears to be invalid (too short)';
        }

        // Check redirect URI
        if (empty($redirectUri)) {
            $errors[] = 'FACEBOOK_REDIRECT_URI is not configured';
        } else {
            // Validate redirect URI format
            if (!filter_var($redirectUri, FILTER_VALIDATE_URL)) {
                $errors[] = 'FACEBOOK_REDIRECT_URI is not a valid URL';
            } elseif (!str_contains($redirectUri, '/auth/facebook/callback')) {
                $warnings[] = 'FACEBOOK_REDIRECT_URI does not match expected callback path (/auth/facebook/callback)';
            }
        }

        $valid = empty($errors);

        if (!$valid) {
            Log::warning('Facebook OAuth configuration invalid', [
                'errors' => $errors,
                'warnings' => $warnings,
            ]);
        }

        return [
            'provider' => 'facebook',
            'valid' => $valid,
            'errors' => $errors,
            'warnings' => $warnings,
            'config' => [
                'client_id_set' => !empty($clientId),
                'client_secret_set' => !empty($clientSecret),
                'redirect_uri' => $redirectUri,
            ],
        ];
    }

    /**
     * Check if a specific provider is configured
     * 
     * @param string $provider
     * @return bool
     */
    public function isProviderConfigured(string $provider): bool
    {
        $validation = match ($provider) {
            'google' => $this->validateGoogleConfig(),
            'facebook' => $this->validateFacebookConfig(),
            default => ['valid' => false],
        };

        return $validation['valid'];
    }

    /**
     * Get configuration status for display
     * 
     * @return array
     */
    public function getConfigurationStatus(): array
    {
        $results = $this->validateAll();

        return [
            'google' => [
                'enabled' => $results['google']['valid'],
                'errors' => $results['google']['errors'],
                'warnings' => $results['google']['warnings'],
            ],
            'facebook' => [
                'enabled' => $results['facebook']['valid'],
                'errors' => $results['facebook']['errors'],
                'warnings' => $results['facebook']['warnings'],
            ],
        ];
    }
}
