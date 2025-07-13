<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ContentPagesController extends Controller
{
    /**
     * Display a listing of content pages.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $pages = ContentPage::all();
        
        return Inertia::render('Admin/Settings/ContentPages/Index', [
            'pages' => $pages,
        ]);
    }

    /**
     * Show the form for creating a new content page.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        return Inertia::render('Admin/Settings/ContentPages/Create');
    }

    /**
     * Store a newly created content page in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:content_pages,slug',
            'content' => 'required|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:1000',
            'is_published' => 'boolean',
        ]);

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        ContentPage::create([
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'content' => $validated['content'],
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'is_published' => $validated['is_published'] ?? true,
        ]);

        // Clear the content pages cache
        $this->clearContentPagesCache();

        return redirect()->route('admin.settings.content-pages.index')
            ->with('success', 'Content page created successfully.');
    }

    /**
     * Show the form for editing the specified content page.
     *
     * @param  int  $id
     * @return \Inertia\Response
     */
    public function edit($id)
    {
        $page = ContentPage::findOrFail($id);
        
        return Inertia::render('Admin/Settings/ContentPages/Edit', [
            'page' => $page,
        ]);
    }

    /**
     * Update the specified content page in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $page = ContentPage::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:content_pages,slug,' . $id,
            'content' => 'required|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:1000',
            'is_published' => 'boolean',
        ]);

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $page->update([
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'content' => $validated['content'],
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'is_published' => $validated['is_published'] ?? true,
        ]);

        // Clear the content pages cache
        $this->clearContentPagesCache();

        return redirect()->route('admin.settings.content-pages.index')
            ->with('success', 'Content page updated successfully.');
    }

    /**
     * Remove the specified content page from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $page = ContentPage::findOrFail($id);
        $page->delete();
        
        // Clear the content pages cache
        $this->clearContentPagesCache();

        return redirect()->route('admin.settings.content-pages.index')
            ->with('success', 'Content page deleted successfully.');
    }

    /**
     * Display the specified content page to visitors.
     *
     * @param  string  $slug
     * @return \Inertia\Response
     */
    public function show($slug)
    {
        $page = ContentPage::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();
        
        return Inertia::render('ContentPage', [
            'page' => $page,
        ]);
    }

    /**
     * Toggle the published status of a content page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function togglePublished(Request $request, $id)
    {
        $page = ContentPage::findOrFail($id);
        $page->is_published = !$page->is_published;
        $page->save();
        
        // Clear the content pages cache
        $this->clearContentPagesCache();

        return response()->json([
            'success' => true,
            'message' => 'Page ' . ($page->is_published ? 'published' : 'unpublished') . ' successfully.',
            'is_published' => $page->is_published,
        ]);
    }

    /**
     * Clear the content pages cache.
     *
     * @return void
     */
    protected function clearContentPagesCache()
    {
        Cache::forget('content_pages');
        Cache::forget('published_content_pages');
    }
}
