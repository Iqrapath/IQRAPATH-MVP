import React from 'react';
import { Card, CardContent } from '@/components/ui/card';

interface TeachingSession {
  id: number;
  session_date: string;
  start_time: string;
  end_time: string;
  status: string;
  student: {
    id: number;
    name: string;
  };
  subject: {
    id: number;
    name: string;
  };
}

interface Props {
  totalSessions: number;
  averageRating: number;
  totalReviews: number;
  upcomingSessions: TeachingSession[];
}

export default function TeacherPerformanceStats({ 
  totalSessions, 
  averageRating, 
  totalReviews, 
  upcomingSessions 
}: Props) {
  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    const month = date.toLocaleDateString('en-US', { month: 'short' });
    const day = date.getDate();
    return `${month} ${day}`;
  };

  const formatTime = (timeString: string) => {
    const time = new Date(`2000-01-01T${timeString}`);
    return time.toLocaleTimeString('en-US', { 
      hour: 'numeric', 
      minute: '2-digit',
      hour12: true 
    });
  };

  return (
    <Card className="mb-8 shadow-sm">
      <CardContent className="p-6">
        <h2 className="text-lg font-bold mb-4">Performance Stats</h2>
        
        {/* Performance Metrics */}
        <div className="space-y-2 mb-6">
          <div className="flex">
            <span className="text-gray-700 w-48">Total Sessions Taught:</span>
            <span className="text-gray-600">{totalSessions}</span>
          </div>
          <div className="flex">
            <span className="text-gray-700 w-48">Average Rating:</span>
            <span className="text-gray-600">{averageRating.toFixed(1)}</span>
          </div>
          <div className="flex">
            <span className="text-gray-700 w-48">Total Reviews:</span>
            <span className="text-gray-600">{totalReviews}</span>
          </div>
        </div>

        {/* Upcoming Sessions */}
        <div>
          <div className="text-gray-700 mb-2">Upcoming Sessions:</div>
          <div className="space-y-1">
            {upcomingSessions.length > 0 ? (
              upcomingSessions.map((session) => (
                <div key={session.id} className="text-gray-600">
                  - {formatDate(session.session_date)}, {formatTime(session.start_time)} - {session.student.name} ({session.subject.name})
                </div>
              ))
            ) : (
              <div className="text-gray-500 italic">No upcoming sessions</div>
            )}
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
