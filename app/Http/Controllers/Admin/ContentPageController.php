<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContentPageRequest;
use App\Http\Requests\UpdateContentPageRequest;
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
     * Display a listing of content pages.
     */
    public function index(Request $request): Response
    {
        $pages = $this->contentPageService->getAll();

        return Inertia::render('admin/content-pages/index', [
            'pages' => $pages,
        ]);
    }

    /**
     * Show the form for creating a new content page.
     */
    public function create(): Response
    {
        return Inertia::render('admin/content-pages/create');
    }

    /**
     * Store a newly created content page.
     */
    public function store(StoreContentPageRequest $request)
    {
        $this->contentPageService->createOrUpdate(
            $request->page_key,
            $request->title,
            $request->content,
            $request->user()
        );

        return redirect()->route('admin.content-pages.index')
            ->with('success', 'Content page created successfully.');
    }

    /**
     * Display the specified content page.
     */
    public function show(string $key): Response
    {
        $page = $this->contentPageService->getByKey($key);

        if (!$page) {
            abort(404);
        }

        return Inertia::render('admin/content-pages/show', [
            'page' => $page,
        ]);
    }

    /**
     * Show the form for editing the specified content page.
     */
    public function edit(string $key): Response
    {
        $page = $this->contentPageService->getByKey($key);

        if (!$page) {
            abort(404);
        }

        return Inertia::render('admin/content-pages/edit', [
            'page' => $page,
        ]);
    }

    /**
     * Update the specified content page.
     */
    public function update(UpdateContentPageRequest $request, string $key)
    {
        $this->contentPageService->createOrUpdate(
            $key,
            $request->title,
            $request->content,
            $request->user()
        );

        return redirect()->route('admin.content-pages.index')
            ->with('success', 'Content page updated successfully.');
    }

    /**
     * Remove the specified content page.
     */
    public function destroy(string $key)
    {
        $deleted = $this->contentPageService->delete($key);

        if (!$deleted) {
            return redirect()->back()
                ->with('error', 'Content page not found.');
        }

        return redirect()->route('admin.content-pages.index')
            ->with('success', 'Content page deleted successfully.');
    }
}
