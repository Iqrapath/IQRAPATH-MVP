<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ContentPage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ContentPageService
{
    /**
     * Get content page by key.
     */
    public function getByKey(string $key): ?ContentPage
    {
        return ContentPage::getByKey($key);
    }

    /**
     * Get content by key with fallback.
     */
    public function getContent(string $key, string $fallback = ''): string
    {
        return ContentPage::getContent($key, $fallback);
    }

    /**
     * Create or update content page.
     */
    public function createOrUpdate(string $key, string $title, string $content, ?User $user = null): ContentPage
    {
        return DB::transaction(function () use ($key, $title, $content, $user) {
            $page = ContentPage::updateOrCreate(
                ['page_key' => $key],
                [
                    'title' => $title,
                    'content' => $content,
                    'last_updated_by' => $user?->id,
                ]
            );

            return $page;
        });
    }

    /**
     * Get all content pages.
     */
    public function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return ContentPage::with('lastUpdatedBy')->orderBy('title')->get();
    }

    /**
     * Delete content page.
     */
    public function delete(string $key): bool
    {
        $page = ContentPage::getByKey($key);
        
        if (!$page) {
            return false;
        }

        return $page->delete();
    }

    /**
     * Get sign-up related content pages.
     */
    public function getSignUpContent(): array
    {
        return [
            'terms_conditions' => $this->getContent('terms_conditions', 'Terms & Conditions content not available.'),
            'privacy_policy' => $this->getContent('privacy_policy', 'Privacy Policy content not available.'),
        ];
    }

    /**
     * Seed default content pages if they don't exist.
     */
    public function seedDefaults(): void
    {
        $defaultPages = [
            [
                'page_key' => 'terms_conditions',
                'title' => 'Terms & Conditions',
                'content' => 'Welcome to IqraQuest – a trusted platform for connecting Quran teachers with students and guardians across the globe. These Terms & Conditions ("Terms") govern your use of the IqraQuest website, services, and any associated content provided by IqraQuest.',
            ],
            [
                'page_key' => 'privacy_policy',
                'title' => 'Privacy Policy',
                'content' => 'At IqraQuest, we take your privacy seriously. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our platform.',
            ],
        ];

        foreach ($defaultPages as $pageData) {
            ContentPage::updateOrCreate(
                ['page_key' => $pageData['page_key']],
                [
                    'title' => $pageData['title'],
                    'content' => $pageData['content'],
                ]
            );
        }
    }
}
