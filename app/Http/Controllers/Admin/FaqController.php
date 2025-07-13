<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class FaqController extends Controller
{
    /**
     * Display a listing of FAQs.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $faqs = Faq::orderBy('order')->get();
        
        return Inertia::render('Admin/Settings/Faqs/Index', [
            'faqs' => $faqs,
        ]);
    }

    /**
     * Show the form for creating a new FAQ.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        $categories = $this->getCategories();
        
        return Inertia::render('Admin/Settings/Faqs/Create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created FAQ in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'category' => 'required|string|max:100',
            'order' => 'nullable|integer|min:0',
            'is_published' => 'boolean',
        ]);

        // If order not specified, put at the end
        if (empty($validated['order'])) {
            $maxOrder = Faq::max('order') ?? 0;
            $validated['order'] = $maxOrder + 1;
        }

        Faq::create([
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'category' => $validated['category'],
            'order' => $validated['order'],
            'is_published' => $validated['is_published'] ?? true,
        ]);

        // Clear the FAQs cache
        $this->clearFaqsCache();

        return redirect()->route('admin.settings.faqs.index')
            ->with('success', 'FAQ created successfully.');
    }

    /**
     * Show the form for editing the specified FAQ.
     *
     * @param  int  $id
     * @return \Inertia\Response
     */
    public function edit($id)
    {
        $faq = Faq::findOrFail($id);
        $categories = $this->getCategories();
        
        return Inertia::render('Admin/Settings/Faqs/Edit', [
            'faq' => $faq,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified FAQ in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $faq = Faq::findOrFail($id);
        
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'category' => 'required|string|max:100',
            'order' => 'required|integer|min:0',
            'is_published' => 'boolean',
        ]);

        $faq->update([
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'category' => $validated['category'],
            'order' => $validated['order'],
            'is_published' => $validated['is_published'] ?? $faq->is_published,
        ]);

        // Clear the FAQs cache
        $this->clearFaqsCache();

        return redirect()->route('admin.settings.faqs.index')
            ->with('success', 'FAQ updated successfully.');
    }

    /**
     * Remove the specified FAQ from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $faq = Faq::findOrFail($id);
        $faq->delete();
        
        // Clear the FAQs cache
        $this->clearFaqsCache();

        return redirect()->route('admin.settings.faqs.index')
            ->with('success', 'FAQ deleted successfully.');
    }

    /**
     * Update the order of FAQs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOrder(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:faqs,id',
            'items.*.order' => 'required|integer|min:0',
        ]);

        foreach ($validated['items'] as $item) {
            Faq::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        // Clear the FAQs cache
        $this->clearFaqsCache();

        return response()->json([
            'success' => true,
            'message' => 'FAQ order updated successfully.',
        ]);
    }

    /**
     * Toggle the published status of a FAQ.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function togglePublished(Request $request, $id)
    {
        $faq = Faq::findOrFail($id);
        $faq->is_published = !$faq->is_published;
        $faq->save();
        
        // Clear the FAQs cache
        $this->clearFaqsCache();

        return response()->json([
            'success' => true,
            'message' => 'FAQ ' . ($faq->is_published ? 'published' : 'unpublished') . ' successfully.',
            'is_published' => $faq->is_published,
        ]);
    }

    /**
     * Get all available FAQ categories.
     *
     * @return array
     */
    protected function getCategories()
    {
        // Get existing categories from the database
        $existingCategories = Faq::distinct('category')->pluck('category')->toArray();
        
        // Default categories
        $defaultCategories = [
            'General',
            'Registration',
            'Teachers',
            'Students',
            'Guardians',
            'Payments',
            'Classes',
            'Technical',
        ];
        
        // Merge and remove duplicates
        return array_unique(array_merge($defaultCategories, $existingCategories));
    }

    /**
     * Clear the FAQs cache.
     *
     * @return void
     */
    protected function clearFaqsCache()
    {
        Cache::forget('faqs');
        Cache::forget('published_faqs');
    }
}
