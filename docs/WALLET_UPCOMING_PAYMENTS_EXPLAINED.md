# Wallet "Upcoming Payments Due" - Data Source Explanation

## What Populates "Upcoming Payments Due"?

The "Upcoming Payments Due" section in the student wallet page displays **upcoming bookings that require payment**.

### Data Source

Located in: `app/Http/Controllers/Student/PaymentController.php` (line 35-62)

```php
$upcomingPayments = $user->studentBookings()
    ->whereIn('status', ['pending', 'approved', 'upcoming'])
    ->where('booking_date', '>=', now())
    ->with(['teacher', 'subject'])
    ->latest()
    ->take(5)
    ->get()
    ->map(function ($booking) {
        // Calculate amount based on hourly rate and duration
        $hourlyRate = $booking->hourly_rate_ngn ?? 0;
        $durationHours = ($booking->duration_minutes ?? 60) / 60;
        $totalAmount = $hourlyRate * $durationHours;
        
        return [
            'id' => $booking->id,
            'amount' => $totalAmount,
            'amountSecondary' => $totalAmount / 1500, // NGN to USD conversion
            'currency' => 'NGN',
            'secondaryCurrency' => 'USD',
            'teacherName' => $booking->teacher->name ?? 'Unknown',
            'subjectName' => $booking->subject->name ?? 'Unknown Subject',
            'dueDate' => $booking->booking_date ?? now(),
            'startTime' => $booking->start_time ?? null,
            'status' => $booking->status,
        ];
    });
```

### Criteria for Upcoming Payments

A booking appears in "Upcoming Payments Due" if:

1. **Status**: Must be one of:
   - `pending` - Booking awaiting approval
   - `approved` - Booking approved by teacher
   - `upcoming` - Scheduled session coming up

2. **Date**: `booking_date >= now()` (future bookings only)

3. **Limit**: Shows maximum of 5 upcoming payments

4. **Sorted**: Latest bookings first

### Calculated Fields

- **Amount**: `hourly_rate_ngn × (duration_minutes / 60)`
- **Secondary Amount**: `amount / 1500` (approximate NGN to USD)
- **Teacher Name**: From `bookings.teacher` relationship
- **Subject Name**: From `bookings.subject` relationship
- **Due Date**: The `booking_date` field
- **Status**: Current booking status

### Database Tables Involved

1. **bookings** - Main booking records
   - `student_id` - Links to user
   - `teacher_id` - Links to teacher
   - `subject_id` - Links to subject
   - `booking_date` - When session is scheduled
   - `start_time` - Session start time
   - `duration_minutes` - Session length
   - `hourly_rate_ngn` - Cost per hour
   - `status` - Booking status

2. **users** - Teacher information
   - `name` - Teacher's name

3. **subjects** - Subject information
   - `name` - Subject name

### Example Data Flow

```
Student creates booking:
├── Booking created with status: 'pending'
├── booking_date: '2025-11-05'
├── hourly_rate_ngn: 5000
├── duration_minutes: 60
└── Appears in "Upcoming Payments Due"

Teacher approves:
├── Status changes to: 'approved'
└── Still appears in "Upcoming Payments Due"

Student pays:
├── Status changes to: 'confirmed' or 'paid'
└── Removed from "Upcoming Payments Due"

Session date passes:
├── booking_date < now()
└── Removed from "Upcoming Payments Due"
```

### Why It Might Be Empty

"Upcoming Payments Due" will show "No upcoming payments" if:

1. **No bookings exist** - Student hasn't booked any sessions
2. **All bookings are past** - All `booking_date < now()`
3. **Wrong status** - Bookings have status other than pending/approved/upcoming
4. **Already paid** - Bookings marked as 'confirmed' or 'paid'
5. **Cancelled** - Bookings with status 'cancelled'

### Testing Data

To populate "Upcoming Payments Due", you need:

```php
// Create a booking
$booking = Booking::create([
    'student_id' => $student->id,
    'teacher_id' => $teacher->id,
    'subject_id' => $subject->id,
    'booking_date' => now()->addDays(3), // Future date
    'start_time' => '10:00:00',
    'duration_minutes' => 60,
    'hourly_rate_ngn' => 5000,
    'status' => 'approved', // Must be pending/approved/upcoming
]);
```

### Frontend Display

Located in: `resources/js/pages/student/wallet/components/WalletBalance.tsx` (line 280-330)

The component displays:
- Amount in both NGN and USD
- Teacher name
- Due date
- Status badge
- "Pay Now" button

### Pay Now Action

When student clicks "Pay Now":
```typescript
const handlePayNow = (paymentId: number) => {
    router.visit(`/student/payments/${paymentId}`);
};
```

This navigates to the payment page for that specific booking.

## Summary

**"Upcoming Payments Due"** shows future bookings (pending/approved/upcoming status) that need payment, calculated from the bookings table with teacher hourly rates and session duration. It's essentially a reminder of sessions the student has booked but not yet paid for or attended.
