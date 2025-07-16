<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationTemplateController extends Controller
{
    /**
     * Display a listing of notification templates.
     */
    public function index(Request $request)
    {
        $templates = NotificationTemplate::when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            })
            ->when($request->type, function ($query, $type) {
                $query->where('type', $type);
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
            
        // Format pagination data to match what the frontend expects
        $formattedTemplates = [
            'data' => $templates->items(),
            'links' => [
                'prev' => $templates->previousPageUrl(),
                'next' => $templates->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $templates->currentPage(),
                'from' => $templates->firstItem() ?? 0,
                'last_page' => $templates->lastPage(),
                'links' => $templates->linkCollection()->toArray(),
                'path' => $templates->path(),
                'per_page' => $templates->perPage(),
                'to' => $templates->lastItem() ?? 0,
                'total' => $templates->total(),
            ],
        ];

        return Inertia::render('admin/notification-component/templates', [
            'templates' => $formattedTemplates,
            'filters' => $request->only(['search', 'type']),
        ]);
    }

    /**
     * Show the form for creating a new template.
     */
    public function create()
    {
        return Inertia::render('admin/notification-component/template-create');
    }

    /**
     * Store a newly created template in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'required|string',
            'is_active' => 'boolean',
        ]);

        NotificationTemplate::create([
            'name' => $request->name,
            'title' => $request->title,
            'body' => $request->body,
            'type' => $request->type,
            'is_active' => $request->is_active ?? true,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.notification.templates')
            ->with('success', 'Template created successfully.');
    }
    
    /**
     * Display the specified template.
     */
    public function show(NotificationTemplate $template)
    {
        return Inertia::render('admin/notification-component/template-show', [
            'template' => $template,
        ]);
    }
    
    /**
     * Show the form for editing the specified template.
     */
    public function edit(NotificationTemplate $template)
    {
        return Inertia::render('admin/notification-component/template-edit', [
            'template' => $template,
        ]);
    }
    
    /**
     * Update the specified template in storage.
     */
    public function update(Request $request, NotificationTemplate $template)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'required|string',
            'placeholders' => 'nullable|array',
            'is_active' => 'boolean',
        ]);
        
        $template->update([
            'name' => $request->name,
            'title' => $request->title,
            'body' => $request->body,
            'type' => $request->type,
            'placeholders' => $request->placeholders,
            'is_active' => $request->is_active,
        ]);
        
        return redirect()->route('admin.notification.templates')
            ->with('success', 'Template updated successfully.');
    }
    
    /**
     * Remove the specified template from storage.
     */
    public function destroy(NotificationTemplate $template)
    {
        // Check if template is used by any triggers
        if ($template->triggers()->count() > 0) {
            return redirect()->route('admin.notification.templates')
                ->with('error', 'Cannot delete template that is used by triggers.');
        }
        
        $template->delete();
        
        return redirect()->route('admin.notification.templates')
            ->with('success', 'Template deleted successfully.');
    }
} 