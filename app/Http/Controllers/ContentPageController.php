<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ContentPageService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContentPageController extends Controller
{
    public function __construct(
        private ContentPageService $contentPageService
    ) {}

    /**
     * Display terms and conditions.
     */
    public function terms(): Response
    {
        $content = $this->contentPageService->getContent(
            'terms_conditions',
            'Terms & Conditions content is not available at the moment. Please contact support for more information.'
        );

        return Inertia::render('content/terms', [
            'content' => $content,
        ]);
    }

    /**
     * Display privacy policy.
     */
    public function privacy(): Response
    {
        $content = $this->contentPageService->getContent(
            'privacy_policy',
            'Privacy Policy content is not available at the moment. Please contact support for more information.'
        );

        return Inertia::render('content/privacy', [
            'content' => $content,
        ]);
    }

    /**
     * Get sign-up content for registration pages.
     */
    public function getSignUpContent(): array
    {
        return $this->contentPageService->getSignUpContent();
    }
}
