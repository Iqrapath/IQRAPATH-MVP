import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { StatCard } from '@/pages/admin/dashboard-component/stat-card';
import { ReactNode } from 'react';
import { Calendar } from 'lucide-react';
import { Link, router } from '@inertiajs/react';

interface StatsOverviewProps {
  stats: Array<{
    title: string;
    value: string | number;
    icon: ReactNode;
    gradient: string;
  }>;
}

export function StatsOverview({ stats }: StatsOverviewProps) {
  return (
    <Card className="bg-white border-1 rounded-3xl shadow-sm mb-6">
      <CardContent>
        {/* Card Header */}
        <div className="flex justify-between items-center mb-8 -mt-2">
          <h2 className="text-xl font-bold text-gray-800">Your Stats</h2>
          <Button
            onClick={() => router.visit('/admin/verification')}
            className="bg-[#2c7870] hover:bg-[#236158] text-white text-sm font-medium rounded-full h-9 px-4"
          >
            Approve New Teachers
          </Button>
        </div>

        {/* Stats Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
          {stats.map((stat, index) => (
            <StatCard
              key={index}
              title={stat.title}
              value={stat.value}
              icon={stat.icon}
              gradient={stat.gradient}
            />
          ))}
        </div>

        {/* View Profiles Link */}
        <div>
          <Button
            variant="ghost"
            className="text-[#2c7870] hover:text-[#2c7870] hover:bg-transparent p-0 h-auto text-sm font-normal"
            asChild
          >
            <Link href={route("admin.user-management.index")}>
              View Profiles
            </Link>
          </Button>
        </div>
      </CardContent>
    </Card>
  );
}