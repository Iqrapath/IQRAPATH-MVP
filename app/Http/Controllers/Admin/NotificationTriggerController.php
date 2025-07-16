<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use App\Models\NotificationTrigger;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationTriggerController extends Controller
{
    /**
     * Display a listing of notification triggers.
     */
    public function index(Request $request)
    {
        $triggers = NotificationTrigger::with('template')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                $query->where('is_active', $status === 'active');
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
            
        // Format pagination data to match what the frontend expects
        $formattedTriggers = [
            'data' => $triggers->items(),
            'links' => [
                'prev' => $triggers->previousPageUrl(),
                'next' => $triggers->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $triggers->currentPage(),
                'from' => $triggers->firstItem() ?? 0,
                'last_page' => $triggers->lastPage(),
                'links' => $triggers->linkCollection()->toArray(),
                'path' => $triggers->path(),
                'per_page' => $triggers->perPage(),
                'to' => $triggers->lastItem() ?? 0,
                'total' => $triggers->total(),
            ],
        ];

        return Inertia::render('admin/notification-component/triggers', [
            'triggers' => $formattedTriggers,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new trigger.
     */
    public function create()
    {
        $templates = NotificationTemplate::active()->get();
        
        return Inertia::render('admin/notification-component/trigger-create', [
            'templates' => $templates,
            'events' => [
                'user.registered' => 'User Registered',
                'payment.processed' => 'Payment Processed',
                'subscription.expiring' => 'Subscription Expiring',
                'session.scheduled' => 'Session Scheduled',
            ],
        ]);
    }

    /**
     * Store a newly created trigger in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'event' => 'required|string',
            'template_id' => 'required|exists:notification_templates,id',
            'conditions' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        NotificationTrigger::create([
            'name' => $request->name,
            'event' => $request->event,
            'template_id' => $request->template_id,
            'conditions' => $request->conditions ?? [],
            'is_active' => $request->is_active ?? true,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.notification.triggers')
            ->with('success', 'Trigger created successfully.');
    }
    
    /**
     * Display the specified trigger.
     */
    public function show(NotificationTrigger $trigger)
    {
        return Inertia::render('admin/notification-component/trigger-show', [
            'trigger' => $trigger->load('template'),
        ]);
    }
    
    /**
     * Show the form for editing the specified trigger.
     */
    public function edit(NotificationTrigger $trigger)
    {
        $templates = NotificationTemplate::active()->get();
        
        return Inertia::render('admin/notification-component/trigger-edit', [
            'trigger' => $trigger,
            'templates' => $templates,
            'events' => [
                'user.registered' => 'User Registered',
                'payment.processed' => 'Payment Processed',
                'subscription.expiring' => 'Subscription Expiring',
                'session.scheduled' => 'Session Scheduled',
            ],
        ]);
    }
    
    /**
     * Update the specified trigger in storage.
     */
    public function update(Request $request, NotificationTrigger $trigger)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'event' => 'required|string',
            'template_id' => 'required|exists:notification_templates,id',
            'conditions' => 'nullable|array',
            'is_active' => 'boolean',
        ]);
        
        $trigger->update([
            'name' => $request->name,
            'event' => $request->event,
            'template_id' => $request->template_id,
            'conditions' => $request->conditions,
            'is_active' => $request->is_active,
        ]);
        
        return redirect()->route('admin.notification.triggers')
            ->with('success', 'Trigger updated successfully.');
    }
    
    /**
     * Remove the specified trigger from storage.
     */
    public function destroy(NotificationTrigger $trigger)
    {
        $trigger->delete();
        
        return redirect()->route('admin.notification.triggers')
            ->with('success', 'Trigger deleted successfully.');
    }
} 