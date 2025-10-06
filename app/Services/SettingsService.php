<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Models\FinancialSetting;
use App\Models\FeatureFlag;
use App\Models\SecuritySetting;
use App\Models\ContentPage;
use App\Models\Faq;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    /**
     * Get a system setting by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getSystemSetting(string $key, $default = null)
    {
        return $this->getSetting('system_settings', $key, $default);
    }

    /**
     * Get all system settings.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllSystemSettings()
    {
        return $this->getAllSettings('system_settings', SystemSetting::class);
    }

    /**
     * Get a financial setting by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getFinancialSetting(string $key, $default = null)
    {
        return $this->getSetting('financial_settings', $key, $default);
    }

    /**
     * Get all financial settings.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllFinancialSettings()
    {
        return $this->getAllSettings('financial_settings', FinancialSetting::class);
    }

    /**
     * Check if a feature is enabled.
     *
     * @param string $key
     * @param bool $default
     * @return bool
     */
    public function isFeatureEnabled(string $key, bool $default = false)
    {
        $value = $this->getSetting('feature_flags', $key, $default);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get all feature flags.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllFeatureFlags()
    {
        return $this->getAllSettings('feature_flags', FeatureFlag::class);
    }

    /**
     * Get a security setting by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getSecuritySetting(string $key, $default = null)
    {
        return $this->getSetting('security_settings', $key, $default);
    }

    /**
     * Get teacher verification settings.
     *
     * @return array
     */
    public function getTeacherVerificationSettings(): array
    {
        return [
            'require_documents' => $this->isFeatureEnabled('teacher_verification_require_documents', false),
            'require_video' => $this->isFeatureEnabled('teacher_verification_require_video', true),
            'auto_approve_after_video' => $this->isFeatureEnabled('teacher_verification_auto_approve', true),
        ];
    }

    /**
     * Update teacher verification settings.
     *
     * @param array $settings
     * @return void
     */
    public function updateTeacherVerificationSettings(array $settings): void
    {
        $allowedKeys = ['teacher_verification_require_documents', 'teacher_verification_require_video', 'teacher_verification_auto_approve'];
        
        foreach ($settings as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                FeatureFlag::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value ? 'true' : 'false', 'description' => $this->getFeatureDescription($key)]
                );
            }
        }
        
        // Clear feature flags cache
        Cache::forget('feature_flags');
    }

    /**
     * Get feature description for verification settings.
     *
     * @param string $key
     * @return string
     */
    private function getFeatureDescription(string $key): string
    {
        $descriptions = [
            'teacher_verification_require_documents' => 'Require document verification for teacher approval',
            'teacher_verification_require_video' => 'Require video verification for teacher approval',
            'teacher_verification_auto_approve' => 'Auto-approve teachers after video verification passes',
        ];
        
        return $descriptions[$key] ?? 'Teacher verification setting';
    }

    /**
     * Get all security settings.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllSecuritySettings()
    {
        return $this->getAllSettings('security_settings', SecuritySetting::class);
    }

    /**
     * Get a content page by slug.
     *
     * @param string $slug
     * @return \App\Models\ContentPage|null
     */
    public function getContentPage(string $slug)
    {
        return Cache::remember('content_page_' . $slug, now()->addDay(), function () use ($slug) {
            return ContentPage::where('slug', $slug)
                ->where('is_published', true)
                ->first();
        });
    }

    /**
     * Get all published content pages.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPublishedContentPages()
    {
        return Cache::remember('published_content_pages', now()->addDay(), function () {
            return ContentPage::where('is_published', true)->get();
        });
    }

    /**
     * Get FAQs by category.
     *
     * @param string|null $category
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFaqs(string $category = null)
    {
        $cacheKey = 'faqs' . ($category ? '_' . $category : '');
        
        return Cache::remember($cacheKey, now()->addDay(), function () use ($category) {
            $query = Faq::where('is_published', true)->orderBy('order');
            
            if ($category) {
                $query->where('category', $category);
            }
            
            return $query->get();
        });
    }

    /**
     * Get all FAQ categories.
     *
     * @return array
     */
    public function getFaqCategories()
    {
        return Cache::remember('faq_categories', now()->addDay(), function () {
            return Faq::where('is_published', true)
                ->distinct('category')
                ->pluck('category')
                ->toArray();
        });
    }

    /**
     * Get a setting by key from the specified cache group.
     *
     * @param string $cacheGroup
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getSetting(string $cacheGroup, string $key, $default = null)
    {
        $settings = $this->getAllSettings($cacheGroup, $this->getModelClassForCacheGroup($cacheGroup));
        return $settings[$key] ?? $default;
    }

    /**
     * Get all settings for a specific cache group.
     *
     * @param string $cacheGroup
     * @param string $modelClass
     * @return \Illuminate\Support\Collection
     */
    protected function getAllSettings(string $cacheGroup, string $modelClass)
    {
        return Cache::remember($cacheGroup, now()->addDay(), function () use ($modelClass) {
            return $modelClass::all()->pluck('value', 'key')->toArray();
        });
    }

    /**
     * Get the model class for a cache group.
     *
     * @param string $cacheGroup
     * @return string
     */
    protected function getModelClassForCacheGroup(string $cacheGroup)
    {
        $map = [
            'system_settings' => SystemSetting::class,
            'financial_settings' => FinancialSetting::class,
            'feature_flags' => FeatureFlag::class,
            'security_settings' => SecuritySetting::class,
        ];

        return $map[$cacheGroup] ?? SystemSetting::class;
    }

    /**
     * Clear all settings caches.
     *
     * @return void
     */
    public function clearAllCaches()
    {
        Cache::forget('system_settings');
        Cache::forget('financial_settings');
        Cache::forget('feature_flags');
        Cache::forget('security_settings');
        Cache::forget('admin_roles');
        Cache::forget('content_pages');
        Cache::forget('published_content_pages');
        Cache::forget('faqs');
        Cache::forget('published_faqs');
        Cache::forget('faq_categories');
    }
} 