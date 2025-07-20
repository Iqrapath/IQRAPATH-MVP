import { Head } from '@inertiajs/react';
import { type AdminProfile } from '@/types';
import AdminLayout from '@/layouts/admin/admin-layout';
import { StatsOverview } from '@/pages/admin/dashboard-component/stats-overview';
import { RevenueSummary } from '@/pages/admin/dashboard-component/revenue-summary';
import { RecentStudents } from '@/pages/admin/dashboard-component/recent-students';
import { RecentBookings } from '@/pages/admin/dashboard-component/recent-bookings';
import { StudentIcon } from '@/components/icons/student-icon';
import { CalendarIcon } from '@/components/icons/calender-icon';
import { PendingIcon } from '@/components/icons/pending-icon';

interface AdminDashboardProps {
    adminProfile: AdminProfile;
    stats: {
        teacherCount: number;
        studentCount: number;
        activeSubscriptionCount: number;
        pendingVerificationCount: number;
    };
    revenueData: {
        year: number;
        months: Array<{
            month: string;
            revenue: number;
        }>;
        currentMonthRevenue: number;
        totalRevenue: number;
    };
    recentStudents: Array<{
        id: number;
        name: string;
        avatar: string | null;
        email: string;
        created_at: string;
    }>;
    recentBookings: Array<{
        id: number;
        booking_uuid: string;
        student_name: string;
        student_avatar: string | null;
        teacher_name: string;
        subject_name: string;
        booking_date: string;
        status: string;
        created_at: string;
    }>;
    pendingVerifications: Array<{
        id: number;
        teacher_id: number;
        teacher_name: string;
        teacher_avatar: string | null;
        teacher_email: string;
        submitted_at: string | null;
        docs_status: string;
        document_count: number;
    }>;
}

export default function AdminDashboard({ 
    adminProfile, 
    stats, 
    revenueData, 
    recentStudents, 
    recentBookings,
    pendingVerifications 
}: AdminDashboardProps) {
    // Create stats array for the StatsOverview component using actual data
    const statsData = [
        {
            title: 'Total Teachers:',
            value: stats.teacherCount.toLocaleString(),
            icon: <StudentIcon className="text-teal-600 w-8 h-8" />,
            gradient: 'from-white to-white'
        },
        {
            title: 'Active Students:',
            value: stats.studentCount.toLocaleString(),
            icon: <StudentIcon className="text-teal-600 w-8 h-8" />,
            gradient: 'from-[#E9FFFD]/100 to-[#E9FFFD]/10'
        },
        {
            title: 'Active Subscriptions:',
            value: stats.activeSubscriptionCount.toLocaleString(),
            icon: <CalendarIcon className="text-teal-600 w-8 h-8" />,
            gradient: 'from-[#C0B7E8]/100 to-[#FFF9E9]/1'
        },
        {
            title: 'Pending Verifications:',
            value: stats.pendingVerificationCount.toLocaleString(),
            icon: <PendingIcon className="text-teal-600 w-8 h-8" />,
            gradient: 'from-[#FFF9E9]/100 to-[#FFF9E9]/10'
        }
    ];

    return (
        <AdminLayout pageTitle="Admin Dashboard" showRightSidebar={false}>
            <Head title="Admin Dashboard" />
            
            <div className="py-4 px-2 max-w-[1400px] mx-auto">
                <h1 className="text-3xl font-bold mb-6">Overview</h1>
                
                {/* Stats Overview */}
                <div className="mb-6">
                    <StatsOverview 
                        stats={statsData}
                    />
                </div>
                
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    {/* Left Column - Revenue Summary (wider) */}
                    <div className="lg:col-span-8">
                        <RevenueSummary
                            year={revenueData.year}
                            months={revenueData.months}
                            currentMonthRevenue={revenueData.currentMonthRevenue}
                            totalRevenue={revenueData.totalRevenue}
                        />
                    </div>
                    
                    {/* Right Column - Recent Students and Bookings (narrower) */}
                    <div className="lg:col-span-4 flex flex-col gap-6">
                        {/* Recent Students */}
                        <RecentStudents 
                            students={recentStudents} 
                            totalCount={stats.studentCount}
                        />
                        
                        {/* Recent Bookings */}
                        <RecentBookings 
                            bookings={recentBookings}
                            totalCount={recentBookings.length}
                        />
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
} 