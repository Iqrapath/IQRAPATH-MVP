<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Subject;
use App\Models\SubjectTemplates;
use App\Models\User;
use App\Services\NotificationService;
use App\Notifications\BookingCancelledNotification;
use App\Notifications\BookingApprovedNotification;
use App\Notifications\BookingRescheduledNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Display a listing of bookings.
     */
    public function index(Request $request): Response
    {
        $query = Booking::with(['student', 'teacher.teacherAvailabilities', 'subject.template', 'createdBy', 'approvedBy', 'cancelledBy'])
            ->orderBy('booking_date', 'desc')
            ->orderBy('start_time', 'desc');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', function ($studentQuery) use ($search) {
                    $studentQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhereHas('teacher', function ($teacherQuery) use ($search) {
                    $teacherQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            });
        }

        if ($request->filled('status') && $request->get('status') !== 'all') {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('subject_id') && $request->get('subject_id') !== 'all') {
            $query->whereHas('subject.template', function($q) use ($request) {
                $q->where('id', $request->get('subject_id'));
            });
        }

        if ($request->filled('date') && $request->get('date') !== 'all') {
            $dateFilter = $request->get('date');
            $today = now()->toDateString();
            
            switch ($dateFilter) {
                case 'today':
                    $query->whereDate('booking_date', $today);
                    break;
                case 'tomorrow':
                    $query->whereDate('booking_date', now()->addDay()->toDateString());
                    break;
                case 'this_week':
                    $query->whereBetween('booking_date', [
                        now()->startOfWeek()->toDateString(),
                        now()->endOfWeek()->toDateString()
                    ]);
                    break;
                case 'next_week':
                    $query->whereBetween('booking_date', [
                        now()->addWeek()->startOfWeek()->toDateString(),
                        now()->addWeek()->endOfWeek()->toDateString()
                    ]);
                    break;
                case 'this_month':
                    $query->whereMonth('booking_date', now()->month)
                          ->whereYear('booking_date', now()->year);
                    break;
            }
        }

        if ($request->filled('date_from')) {
            $query->where('booking_date', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('booking_date', '<=', $request->get('date_to'));
        }

        $bookings = $query->paginate(15)->withQueryString();

        // Get filter options - get unique subject templates
        $subjects = SubjectTemplates::where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
        
        // Get status options
        $statusOptions = [
            'all' => 'All Statuses',
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'upcoming' => 'Upcoming',
            'completed' => 'Completed',
            'missed' => 'Missed',
            'cancelled' => 'Cancelled',
        ];

        // Get statistics
        $stats = [
            'total' => Booking::count(),
            'pending' => Booking::where('status', 'pending')->count(),
            'upcoming' => Booking::where('status', 'upcoming')->count(),
            'completed' => Booking::where('status', 'completed')->count(),
            'cancelled' => Booking::where('status', 'cancelled')->count(),
            'missed' => Booking::where('status', 'missed')->count(),
        ];

        return Inertia::render('admin/bookings/index', [
            'bookings' => $bookings,
            'subjects' => $subjects,
            'statusOptions' => $statusOptions,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'subject_id', 'rating', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Display the specified booking.
     */
    public function show(Booking $booking): Response
    {
        $booking->load([
            'student.studentProfile',
            'teacher.teacherProfile',
            'subject',
            'createdBy',
            'approvedBy',
            'cancelledBy',
            'bookingNotes',
            'history',
            'teachingSession'
        ]);

        return Inertia::render('admin/bookings/show', [
            'booking' => $booking,
        ]);
    }

    /**
     * Update the specified booking status.
     */
    public function updateStatus(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected,upcoming,completed,missed,cancelled',
            'notes' => 'nullable|string|max:1000',
            'notify_parties' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($booking, $validated, $request) {
            $oldStatus = $booking->status;
            
            $booking->update([
                'status' => $validated['status'],
                'approved_by_id' => $validated['status'] === 'approved' ? $request->user()->id : $booking->approved_by_id,
                'approved_at' => $validated['status'] === 'approved' ? now() : $booking->approved_at,
                'cancelled_by_id' => in_array($validated['status'], ['cancelled', 'rejected']) ? $request->user()->id : $booking->cancelled_by_id,
                'cancelled_at' => in_array($validated['status'], ['cancelled', 'rejected']) ? now() : $booking->cancelled_at,
            ]);

            // Add to booking history
            $booking->history()->create([
                'action' => $validated['status'],
                'performed_by_id' => $request->user()->id,
                'notes' => $validated['notes'] ?? "Status changed from {$oldStatus} to {$validated['status']}",
            ]);

            // Add notes if provided
            if (!empty($validated['notes'])) {
                $booking->bookingNotes()->create([
                    'content' => $validated['notes'],
                    'user_id' => $request->user()->id,
                    'note_type' => 'admin_note',
                ]);
            }

            // Send notifications if requested (default to true)
            $notifyParties = $validated['notify_parties'] ?? true;
            
            if ($notifyParties) {
                // Load relationships for notifications
                $booking->load(['student', 'teacher', 'subject.template']);
                
                try {
                    if ($validated['status'] === 'approved') {
                        // Notify both student and teacher about approval
                        $booking->student->notify(new BookingApprovedNotification($booking));
                        $booking->teacher->notify(new BookingApprovedNotification($booking));
                    } elseif ($validated['status'] === 'cancelled') {
                        // Notify both student and teacher about cancellation
                        $booking->student->notify(new BookingCancelledNotification($booking, $validated['notes'] ?? '', true));
                        $booking->teacher->notify(new BookingCancelledNotification($booking, $validated['notes'] ?? '', true));
                    }
                } catch (\Exception $e) {
                    // Log email error but don't fail the transaction
                    \Log::warning('Email notification failed: ' . $e->getMessage());
                }
            }
        });

        return redirect()->back()
            ->with('success', 'Booking status updated successfully.');
    }

    /**
     * Bulk update booking statuses.
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'booking_ids' => 'required|array|min:1',
            'booking_ids.*' => 'integer|exists:bookings,id',
            'status' => 'required|in:pending,approved,rejected,upcoming,completed,missed,cancelled',
            'notes' => 'nullable|string|max:1000',
        ]);

        $updatedCount = 0;

        DB::transaction(function () use ($validated, $request, &$updatedCount) {
            foreach ($validated['booking_ids'] as $bookingId) {
                $booking = Booking::findOrFail($bookingId);
                $oldStatus = $booking->status;
                
                $booking->update([
                    'status' => $validated['status'],
                    'approved_by_id' => $validated['status'] === 'approved' ? $request->user()->id : $booking->approved_by_id,
                    'approved_at' => $validated['status'] === 'approved' ? now() : $booking->approved_at,
                    'cancelled_by_id' => in_array($validated['status'], ['cancelled', 'rejected']) ? $request->user()->id : $booking->cancelled_by_id,
                    'cancelled_at' => in_array($validated['status'], ['cancelled', 'rejected']) ? now() : $booking->cancelled_at,
                ]);

                // Add to booking history
                $booking->history()->create([
                    'action' => $validated['status'],
                    'performed_by_id' => $request->user()->id,
                    'notes' => $validated['notes'] ?? "Bulk status change from {$oldStatus} to {$validated['status']}",
                ]);

                $updatedCount++;
            }
        });

        return redirect()->back()
            ->with('success', "Successfully updated {$updatedCount} booking(s).");
    }

    /**
     * Delete the specified booking.
     */
    public function destroy(Booking $booking)
    {
        DB::transaction(function () use ($booking) {
            // Delete related records
            $booking->bookingNotes()->delete();
            $booking->history()->delete();
            $booking->notifications()->delete();
            
            // Delete the booking
            $booking->delete();
        });

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Booking deleted successfully.');
    }

    /**
     * Get available teachers for reassignment.
     */
    public function getAvailableTeachers(Request $request, Booking $booking)
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $booking);

        // Get teachers who teach the same subject and are available
        $subjectId = $booking->subject_id;
        $currentTeacherId = $booking->teacher_id;

        // Get teachers who can teach this subject
        $teachers = User::where('role', 'teacher')
            ->where('id', '!=', $currentTeacherId) // Exclude current teacher
            ->whereHas('teacherProfile.subjects', function ($query) use ($subjectId) {
                $query->where('subject_template_id', $subjectId);
            })
            ->with(['teacherAvailabilities' => function ($query) {
                $query->where('is_active', true);
            }])
            ->select('id', 'name', 'email')
            ->get()
            ->map(function ($teacher) {
                return [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'email' => $teacher->email,
                    'is_available' => !$teacher->teacherAvailabilities->first()?->holiday_mode ?? true,
                ];
            });

        return response()->json([
            'teachers' => $teachers,
            'current_teacher' => $booking->teacher->name,
            'subject' => $booking->subject->template->name,
        ]);
    }

    /**
     * Get available days for rescheduling.
     */
    public function getAvailableDays(Request $request, Booking $booking)
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $booking);

        $teacherId = $booking->teacher_id;
        $availableDays = [];

        // Get teacher's availability
        $availability = \App\Models\TeacherAvailability::where('teacher_id', $teacherId)
            ->where('is_active', true)
            ->where('holiday_mode', false)
            ->first();

        if (!$availability) {
            return response()->json([
                'available_days' => [],
                'message' => 'Teacher availability not found'
            ]);
        }

        // Get available days from teacher's schedule
        $daySchedules = $availability->getDaySchedulesArray();
        $enabledDays = collect($daySchedules)
            ->where('enabled', true)
            ->pluck('day')
            ->toArray();

        if (empty($enabledDays)) {
            return response()->json([
                'available_days' => [],
                'message' => 'Teacher has no available days'
            ]);
        }

        // Generate next 30 days and filter by available days
        $today = \Carbon\Carbon::today();
        for ($i = 1; $i <= 30; $i++) {
            $date = $today->copy()->addDays($i);
            $dayName = $date->format('l'); // Monday, Tuesday, etc.
            
            if (in_array($dayName, $enabledDays)) {
                // Check if teacher has any bookings on this day
                $hasBookings = Booking::where('teacher_id', $teacherId)
                    ->where('booking_date', $date->format('Y-m-d'))
                    ->where('id', '!=', $booking->id) // Exclude current booking
                    ->exists();

                // Only include days where teacher has some availability (not fully booked)
                if (!$hasBookings || $this->hasAvailableSlotsOnDate($teacherId, $date->format('Y-m-d'), $booking)) {
                    $availableDays[] = [
                        'value' => $date->format('Y-m-d'),
                        'label' => $date->format('M d, Y'),
                        'day_name' => $dayName,
                        'has_conflicts' => $hasBookings
                    ];
                }
            }
        }

        return response()->json([
            'available_days' => $availableDays,
            'teacher_name' => $booking->teacher->name
        ]);
    }

    /**
     * Check if teacher has available slots on a specific date.
     */
    private function hasAvailableSlotsOnDate($teacherId, $date, $excludeBooking)
    {
        $dayOfWeek = \Carbon\Carbon::parse($date)->dayOfWeek;
        $dayName = \Carbon\Carbon::parse($date)->format('l');

        $availability = \App\Models\TeacherAvailability::where('teacher_id', $teacherId)
            ->where('is_active', true)
            ->whereJsonContains('available_days', $dayName)
            ->first();

        if (!$availability) {
            return false;
        }

        $daySchedules = $availability->getDaySchedulesArray();
        $daySchedule = collect($daySchedules)->firstWhere('day', $dayName);

        if (!$daySchedule || !$daySchedule['enabled']) {
            return false;
        }

        // Check if there are any free slots
        $startTime = \Carbon\Carbon::createFromFormat('H:i', $daySchedule['fromTime']);
        $endTime = \Carbon\Carbon::createFromFormat('H:i', $daySchedule['toTime']);
        $duration = $excludeBooking->duration_minutes;

        $currentTime = $startTime->copy();
        while ($currentTime->addMinutes($duration)->lte($endTime)) {
            $slotStart = $currentTime->copy()->subMinutes($duration);
            $slotEnd = $currentTime->copy();

            // Check if this slot is free
            $conflict = Booking::where('teacher_id', $teacherId)
                ->where('booking_date', $date)
                ->where('id', '!=', $excludeBooking->id)
                ->where(function ($query) use ($slotStart, $slotEnd) {
                    $query->whereBetween('start_time', [$slotStart->format('H:i:s'), $slotEnd->format('H:i:s')])
                          ->orWhereBetween('end_time', [$slotStart->format('H:i:s'), $slotEnd->format('H:i:s')])
                          ->orWhere(function ($q) use ($slotStart, $slotEnd) {
                              $q->where('start_time', '<=', $slotStart->format('H:i:s'))
                                ->where('end_time', '>=', $slotEnd->format('H:i:s'));
                          });
                })
                ->exists();

            if (!$conflict) {
                return true; // Found at least one available slot
            }
        }

        return false;
    }

    /**
     * Get available time slots for rescheduling.
     */
    public function getAvailableSlots(Request $request, Booking $booking)
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $booking);

        $request->validate([
            'date' => 'required|date|after:today',
        ]);

        $selectedDate = $request->get('date');
        $teacherId = $booking->teacher_id;

        // Get teacher availability for the selected date
        $dayOfWeek = \Carbon\Carbon::parse($selectedDate)->dayOfWeek;
        $dayName = \Carbon\Carbon::parse($selectedDate)->format('l'); // Monday, Tuesday, etc.

        $availability = \App\Models\TeacherAvailability::where('teacher_id', $teacherId)
            ->where('is_active', true)
            ->whereJsonContains('available_days', $dayName)
            ->first();

        if (!$availability) {
            return response()->json([
                'available_slots' => [],
                'message' => 'Teacher is not available on this day'
            ]);
        }

        // Get day schedule
        $daySchedules = $availability->getDaySchedulesArray();
        $daySchedule = collect($daySchedules)->firstWhere('day', $dayName);

        if (!$daySchedule || !$daySchedule['enabled']) {
            return response()->json([
                'available_slots' => [],
                'message' => 'Teacher is not available on this day'
            ]);
        }

        // Generate time slots based on teacher's availability
        $startTime = \Carbon\Carbon::createFromFormat('H:i', $daySchedule['fromTime']);
        $endTime = \Carbon\Carbon::createFromFormat('H:i', $daySchedule['toTime']);
        $duration = $booking->duration_minutes;

        $availableSlots = [];
        $currentTime = $startTime->copy();

        while ($currentTime->addMinutes($duration)->lte($endTime)) {
            $slotStart = $currentTime->copy()->subMinutes($duration);
            $slotEnd = $currentTime->copy();

            // Check if this slot conflicts with existing bookings
            $conflict = Booking::where('teacher_id', $teacherId)
                ->where('booking_date', $selectedDate)
                ->where('id', '!=', $booking->id) // Exclude current booking
                ->where(function ($query) use ($slotStart, $slotEnd) {
                    $query->whereBetween('start_time', [$slotStart->format('H:i:s'), $slotEnd->format('H:i:s')])
                          ->orWhereBetween('end_time', [$slotStart->format('H:i:s'), $slotEnd->format('H:i:s')])
                          ->orWhere(function ($q) use ($slotStart, $slotEnd) {
                              $q->where('start_time', '<=', $slotStart->format('H:i:s'))
                                ->where('end_time', '>=', $slotEnd->format('H:i:s'));
                          });
                })
                ->exists();

            if (!$conflict) {
                $availableSlots[] = [
                    'value' => $slotStart->format('H:i'),
                    'label' => $slotStart->format('g:i A') . ' - ' . $slotEnd->format('g:i A'),
                    'start_time' => $slotStart->format('H:i'),
                    'end_time' => $slotEnd->format('H:i'),
                ];
            }

            $currentTime->addMinutes(30); // 30-minute intervals
        }

        return response()->json([
            'available_slots' => $availableSlots,
            'teacher_name' => $booking->teacher->name,
            'date' => \Carbon\Carbon::parse($selectedDate)->format('M d, Y'),
        ]);
    }

    /**
     * Reassign a booking to a different teacher.
     */
    public function reassign(Request $request, Booking $booking)
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $booking);

        $validated = $request->validate([
            'new_teacher_id' => 'required|exists:users,id',
            'notify_parties' => 'nullable|boolean',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($booking, $validated, $request) {
            $oldTeacherId = $booking->teacher_id;
            $newTeacherId = $validated['new_teacher_id'];
            
            // Update booking
            $booking->update([
                'teacher_id' => $newTeacherId,
            ]);

            // Add to booking history
            $booking->history()->create([
                'action' => 'teacher_reassigned',
                'performed_by_id' => $request->user()->id,
                'notes' => "Booking reassigned from teacher ID {$oldTeacherId} to teacher ID {$newTeacherId}. Admin note: " . ($validated['admin_note'] ?? 'No note provided'),
            ]);

            // Add admin note if provided
            if (!empty($validated['admin_note'])) {
                $booking->bookingNotes()->create([
                    'content' => $validated['admin_note'],
                    'user_id' => $request->user()->id,
                    'note_type' => 'reassignment_note',
                ]);
            }

            // Send notifications if requested (default to true)
            $notifyParties = $validated['notify_parties'] ?? true;
            
            if ($notifyParties) {
                // Load relationships for notifications
                $booking->load(['student', 'teacher', 'subject.template']);
                
                try {
                    // Notify both old and new teachers
                    $oldTeacher = User::find($oldTeacherId);
                    $oldTeacher->notify(new \App\Notifications\BookingReassignedNotification($booking, 'removed', $validated['admin_note']));
                    $booking->teacher->notify(new \App\Notifications\BookingReassignedNotification($booking, 'assigned', $validated['admin_note']));
                    
                    // Notify student
                    $booking->student->notify(new \App\Notifications\BookingReassignedNotification($booking, 'student', $validated['admin_note']));
                } catch (\Exception $e) {
                    // Log email error but don't fail the transaction
                    \Log::warning('Email notification failed: ' . $e->getMessage());
                }
            }
        });

        return redirect()->back()
            ->with('success', 'Booking reassigned successfully.');
    }

    /**
     * Reschedule a booking.
     */
    public function reschedule(Request $request, Booking $booking)
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $booking);

        $validated = $request->validate([
            'new_date' => 'required|date|after:today',
            'new_time' => 'required|date_format:H:i',
            'notify_parties' => 'nullable|boolean',
            'reason' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($booking, $validated, $request) {
            $oldDate = $booking->booking_date;
            $oldTime = $booking->start_time;
            
            // Calculate end time based on duration
            $startTime = \Carbon\Carbon::createFromFormat('H:i', $validated['new_time']);
            $endTime = $startTime->copy()->addMinutes($booking->duration_minutes);
            
            // Update booking
            $booking->update([
                'booking_date' => $validated['new_date'],
                'start_time' => $validated['new_time'],
                'end_time' => $endTime->format('H:i:s'),
            ]);

            // Add to booking history
            $booking->history()->create([
                'action' => 'rescheduled',
                'performed_by_id' => $request->user()->id,
                'notes' => "Booking rescheduled from {$oldDate} {$oldTime} to {$validated['new_date']} {$validated['new_time']}. Reason: " . ($validated['reason'] ?? 'No reason provided'),
            ]);

            // Add notes if provided
            if (!empty($validated['reason'])) {
                $booking->bookingNotes()->create([
                    'content' => $validated['reason'],
                    'user_id' => $request->user()->id,
                    'note_type' => 'reschedule_note',
                ]);
            }

            // Send notifications if requested (default to true)
            $notifyParties = $validated['notify_parties'] ?? true;
            
            if ($notifyParties) {
                // Load relationships for notifications
                $booking->load(['student', 'teacher', 'subject.template']);
                
                try {
                    $booking->student->notify(new BookingRescheduledNotification($booking, $oldDate, $oldTime, $validated['reason']));
                    $booking->teacher->notify(new BookingRescheduledNotification($booking, $oldDate, $oldTime, $validated['reason']));
                } catch (\Exception $e) {
                    // Log email error but don't fail the transaction
                    \Log::warning('Email notification failed: ' . $e->getMessage());
                }
            }
        });

        return redirect()->back()
            ->with('success', 'Booking rescheduled successfully.');
    }

    /**
     * Get booking statistics for dashboard.
     */
    public function getStats()
    {
        $stats = [
            'total' => Booking::count(),
            'pending' => Booking::where('status', 'pending')->count(),
            'upcoming' => Booking::where('status', 'upcoming')->count(),
            'completed' => Booking::where('status', 'completed')->count(),
            'cancelled' => Booking::where('status', 'cancelled')->count(),
            'missed' => Booking::where('status', 'missed')->count(),
            'today' => Booking::whereDate('booking_date', today())->count(),
            'this_week' => Booking::whereBetween('booking_date', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => Booking::whereMonth('booking_date', now()->month)->count(),
        ];

        return response()->json($stats);
    }
}
