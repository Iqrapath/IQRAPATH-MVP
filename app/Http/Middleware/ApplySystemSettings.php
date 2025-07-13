<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class ApplySystemSettings
{
    /**
     * The settings service instance.
     *
     * @var \App\Services\SettingsService
     */
    protected $settings;

    /**
     * Create a new middleware instance.
     *
     * @param  \App\Services\SettingsService  $settings
     * @return void
     */
    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Apply system settings
        $this->applySystemSettings();

        // Apply security settings
        $this->applySecuritySettings();

        // Check if maintenance mode is enabled
        if ($this->settings->getSystemSetting('maintenance_mode', false) && !$request->is('admin/*') && !$request->is('login')) {
            return response()->view('maintenance', [], 503);
        }

        return $next($request);
    }

    /**
     * Apply system settings to the application.
     *
     * @return void
     */
    protected function applySystemSettings(): void
    {
        // Set timezone
        $timezone = $this->settings->getSystemSetting('timezone');
        if ($timezone) {
            Config::set('app.timezone', $timezone);
            date_default_timezone_set($timezone);
        }

        // Set app name
        $siteName = $this->settings->getSystemSetting('site_name');
        if ($siteName) {
            Config::set('app.name', $siteName);
        }

        // Share settings with all views
        view()->share('systemSettings', $this->settings->getAllSystemSettings());
    }

    /**
     * Apply security settings to the application.
     *
     * @return void
     */
    protected function applySecuritySettings(): void
    {
        // Set session lifetime
        $sessionLifetime = $this->settings->getSecuritySetting('session_lifetime_minutes');
        if ($sessionLifetime) {
            Config::set('session.lifetime', $sessionLifetime);
        }

        // Set password timeout
        $passwordTimeout = $this->settings->getSecuritySetting('password_timeout_minutes');
        if ($passwordTimeout) {
            Config::set('auth.password_timeout', $passwordTimeout * 60);
        }
    }
} 