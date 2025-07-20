import { Button } from "@/components/ui/button";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";

interface Booking {
  id: number;
  booking_uuid: string;
  student_name: string;
  student_avatar: string | null;
  teacher_name: string;
  subject_name: string;
  booking_date: string;
  status: string;
  created_at: string;
}

interface RecentBookingsProps {
  bookings: Booking[];
  totalCount: number;
}

export function RecentBookings({ bookings, totalCount }: RecentBookingsProps) {
  // Show only the first 3 bookings
  const displayBookings = bookings.slice(0, 3);

  // Helper function to generate booking description
  const getBookingDescription = (booking: Booking) => {
    switch(booking.status) {
      case 'pending':
        return `requested a ${booking.subject_name} session`;
      case 'approved':
        return `booked a ${booking.subject_name} session`;
      case 'completed':
        return `completed a ${booking.subject_name} session`;
      case 'cancelled':
        return `cancelled a ${booking.subject_name} session`;
      case 'rescheduled':
        return `rescheduled ${booking.subject_name} class`;
      default:
        return `booked a ${booking.subject_name} session`;
    }
  };

  return (
    <div className="rounded-2xl border bg-white p-6">
      <div className="mb-2">
        <div className="flex items-center gap-2">
          <h2 className="text-lg font-medium">Recent Bookings</h2>
        </div>
        <p className="text-sm text-muted-foreground mt-1 mb-6">
          You have {totalCount} Booking{totalCount !== 1 ? 's' : ''}
        </p>
      </div>

      <div className="space-y-6">
        {displayBookings.map((booking) => (
          <div key={booking.id} className="flex items-center gap-3">
            <Avatar className="h-10 w-10 border-2 border-white">
              <AvatarImage src={booking.student_avatar || undefined} />
              <AvatarFallback>{booking.student_name.substring(0, 2).toUpperCase()}</AvatarFallback>
            </Avatar>
            <div className="flex-1">
              <div className="font-medium">{booking.student_name} {getBookingDescription(booking)}</div>
            </div>
          </div>
        ))}
      </div>

      <div className="mt-6 text-center">
        <Button variant="link" className="text-teal-600 p-0 h-auto">
          View All Bookings
        </Button>
      </div>
    </div>
  );
} 