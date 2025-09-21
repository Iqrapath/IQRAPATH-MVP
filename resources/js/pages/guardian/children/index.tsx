import { Head, Link } from '@inertiajs/react';
import GuardianLayout from '@/layouts/guardian/guardian-layout';
import { Breadcrumb } from '@/components/ui/breadcrumb';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { GuardianIcon } from '@/components/icons/guardian-icon';
import { GraduationIcon } from '@/components/icons/graduation-icon';
import { MoreVertical, Edit, Calendar, CheckCircle, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import TopRatedTeachers from '../components/TopRatedTeachers';
import { SessionIcon } from '@/components/icons/session-icon';
import { ProgressCheckIcon } from '@/components/icons/progress-check-icon';

const breadcrumbs = [
    { title: "Dashboard", href: route("guardian.dashboard") },
    { title: "View Children Details", href: route("guardian.children.index"), className: "text-[#338078]" }
];

interface ChildRow {
    id: number;
    name: string;
    age?: string;
    status?: string;
    subjects: string[] | string;
}

interface PageProps {
    guardian: {
        name: string;
        email: string;
    };
    children: ChildRow[];
    topRatedTeachers: Array<{
        id: number;
        name: string;
        subjects: string;
        location: string;
        rating: number;
        price: string;
        avatarUrl: string;
    }>;
}

// Helper function to format subjects
const formatSubjects = (subjects: string[] | string): string => {
    if (Array.isArray(subjects)) {
        return subjects.join(', ');
    }

    if (typeof subjects === 'string') {
        // Try to parse JSON if it's a JSON string
        try {
            const parsed = JSON.parse(subjects);
            if (Array.isArray(parsed)) {
                return parsed.join(', ');
            }
            return parsed;
        } catch {
            // If it's not JSON, return as is
            return subjects;
        }
    }

    return '-';
};

export default function ChildrenIndex({ guardian, children, topRatedTeachers }: PageProps) {
    const [openDropdown, setOpenDropdown] = useState<number | null>(null);

    return (
        <GuardianLayout pageTitle="Children Details">
            <Head title="Children Details" />

            <div className="max-w-6xl mx-auto p-6">
                <div className="mb-6">
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>

                {/* Guardian Information Section */}
                <div className="mb-8">
                    <div className="flex items-center gap-3 mb-4">
                        <div className="flex items-center gap-2">
                            <GuardianIcon className="w-5 h-5 text-[#2c7870]" />
                            <span className="text-xl font-semibold text-gray-800">Guardian:</span>
                            <span className="text-xl text-gray-600">{guardian.name}</span>
                        </div>
                    </div>
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <GraduationIcon className="w-5 h-5 text-[#2c7870]" />
                            <span className="text-lg font-medium text-gray-800">Registered Children:</span>
                            <span className="text-lg text-gray-600">{children.length}</span>
                        </div>
                        <Link href={route('guardian.children.create')}>
                            <Button className="bg-[#2c7870] hover:bg-[#236158] text-white rounded-full px-6 py-2">
                                Add New Child
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Children Table */}
                <div className="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
                    <div className="grid grid-cols-12 px-6 py-4 text-gray-500 text-sm font-medium bg-gray-50">
                        <div className="col-span-4">Child</div>
                        <div className="col-span-2">Age</div>
                        <div className="col-span-3">Subjects</div>
                        <div className="col-span-2">Status</div>
                        <div className="col-span-1 text-right">Action</div>
                    </div>
                    <div className="divide-y divide-gray-100">
                        {children.map((child) => (
                            <div key={child.id} className="grid grid-cols-12 items-center px-6 py-6 hover:bg-gray-50">
                                <div className="col-span-4">
                                    <div className="text-gray-800 font-medium">{child.name}</div>
                                </div>
                                <div className="col-span-2">
                                    <div className="text-gray-600">{child.age || '-'}</div>
                                </div>
                                <div className="col-span-3">
                                    <div className="text-gray-600">{formatSubjects(child.subjects)}</div>
                                </div>
                                <div className="col-span-2">
                                    {child.status?.toLowerCase() === 'active' ? (
                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    ) : (
                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            {child.status || 'Pending'}
                                        </span>
                                    )}
                                </div>
                                <div className="col-span-1 text-right">
                                    <DropdownMenu open={openDropdown === child.id} onOpenChange={(open) => setOpenDropdown(open ? child.id : null)}>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                                                <MoreVertical className="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end" className="w-48">
                                            <Link href={route('guardian.children.edit', child.id)}>
                                                <DropdownMenuItem className="flex items-center justify-between">
                                                    Edit Profile
                                                    <Edit className="h-5 w-5" />
                                                </DropdownMenuItem>
                                            </Link>
                                            <DropdownMenuItem className="flex items-center justify-between">
                                                Book Session
                                                <SessionIcon className="h-5 w-5" />
                                            </DropdownMenuItem>
                                            <Link href={route('guardian.children.progress', child.id)}>
                                                <DropdownMenuItem className="flex items-center justify-between">
                                                    View Progress
                                                    <ProgressCheckIcon className="h-5 w-5" />
                                                </DropdownMenuItem>
                                            </Link>
                                            <DropdownMenuItem className="flex items-center justify-between">
                                                Delete
                                                <Trash2 className="h-5 w-5 text-red-600" />
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </div>
                        ))}
                        {children.length === 0 && (
                            <div className="px-6 py-12 text-center text-gray-500">
                                <GraduationIcon className="w-12 h-12 mx-auto mb-4 text-gray-300" />
                                <p className="text-lg font-medium">No children registered yet</p>
                                <p className="text-sm">Click "Add New Child" to get started</p>
                            </div>
                        )}
                    </div>
                </div>

                {/* View All Subscriptions Link */}
                <div className="mt-6">
                    <Link
                        href="#"
                        className="text-[#2c7870] hover:text-[#236158] font-medium text-sm"
                    >
                        View All Subscriptions
                    </Link>
                </div>

                {/* Top rated teachers */}
                <div className="mt-6 md:mt-8">
                    <TopRatedTeachers teachers={topRatedTeachers || []} />
                </div>
            </div>
        </GuardianLayout>
    );
}


