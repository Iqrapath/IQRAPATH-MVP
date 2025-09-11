import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { TeacherStatCard } from './TeacherStatCard';
import { ReactNode } from 'react';
import { Calendar } from 'lucide-react';
import { Link, router } from '@inertiajs/react';
import PendingIcon from '@/components/icons/pending-icon';
import { StudentIcon } from '@/components/icons/student-icon';

interface TeacherStatsOverviewProps {
  stats: {
    activeStudents: number;
    upcomingSessions: number;
    pendingRequests: number;
  };
}

export function TeacherStatsOverview({ stats }: TeacherStatsOverviewProps) {
  const statsData = [
    {
      title: "Active Students:",
      value: stats.activeStudents,
      icon: <StudentIcon className="w-8 h-8" />,
      gradient: "from-teal-50 to-teal-100"
    },
    {
      title: "Upcoming Sessions:",
      value: stats.upcomingSessions,
      icon: <Calendar className="w-6 h-6" />,
      gradient: "from-purple-50 to-purple-100"
    },
    {
      title: "Pending Request",
      value: stats.pendingRequests > 0 ? stats.pendingRequests : "-",
      icon: <PendingIcon className="w-8 h-8" />,
      gradient: "from-yellow-50 to-yellow-100"
    }
  ];

  return (
    <Card className="bg-white border-1 rounded-3xl shadow-sm mb-6">
      <CardContent>
        {/* Card Header */}
        <div className="flex justify-between items-center mb-8 -mt-2">
          <h2 className="text-xl font-bold text-gray-800">Your Stats</h2>
          <Button
            onClick={() => router.visit('/teacher/students')}
            className="bg-[#2c7870] hover:bg-[#236158] text-white text-sm font-medium rounded-full h-9 px-4"
          >
            Find Student
          </Button>
        </div>

        {/* Stats Grid */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
          {statsData.map((stat, index) => (
            <TeacherStatCard
              key={index}
              title={stat.title}
              value={stat.value}
              icon={stat.icon}
              gradient={stat.gradient}
            />
          ))}
        </div>

        {/* View Details Link */}
        <div>
          <Button
            variant="ghost"
            className="text-[#2c7870] hover:text-[#2c7870] hover:bg-transparent p-0 h-auto text-sm font-normal"
            asChild
          >
            <Link href="/teacher/sessions">
              View Details
            </Link>
          </Button>
        </div>
      </CardContent>
    </Card>
  );
}
