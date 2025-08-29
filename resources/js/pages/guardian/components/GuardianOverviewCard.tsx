import React from 'react';
import { Mail } from 'lucide-react';
import { GraduationIcon } from '@/components/icons/graduation-icon';
import { GuardianIcon } from '@/components/icons/guardian-icon';
import { CalendarCheckIcon } from '@/components/icons/calender-check-icon';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';

interface GuardianOverviewCardProps {
    guardianName: string;
    email: string;
    registeredChildren: number;
    activePlan: string;
}

export default function GuardianOverviewCard({ guardianName, email, registeredChildren, activePlan }: GuardianOverviewCardProps) {
    return (
        <div className="rounded-[28px] bg-white shadow-sm border border-gray-100 p-6 md:p-8 max-w-4xl mx-auto">
            {/* Row 1 */}
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-3 flex-1">
                    <div className="text-[#2c7870] flex items-center justify-center">
                        <GuardianIcon className="w-8 h-8" />
                    </div>
                    <div className="text-gray-900"><span className="font-semibold">Guardian:</span> {guardianName}</div>
                </div>
                <div className="hidden md:block w-12 h-px bg-gray-300/60 mx-4"></div>
                <div className="flex items-center gap-3 flex-1">
                    <div className="text-[#2c7870] flex items-center justify-center">
                        <Mail className="w-8 h-8" />
                    </div>
                    <div className="text-gray-900"><span className="font-semibold">Email:</span> {email}</div>
                </div>
            </div>

            <div className="my-4 h-px bg-gray-200"></div>

            {/* Row 2 */}
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-3 flex-1">
                    <div className="text-[#2c7870] flex items-center justify-center">
                        <GraduationIcon className="w-8 h-8" />
                    </div>
                    <div className="text-gray-900"><span className="font-semibold">Registered Children:</span> {registeredChildren}</div>
                </div>
                <div className="hidden md:block w-12 h-px bg-gray-300/60 mx-4"></div>
                <div className="flex items-center gap-3 flex-1">
                    <div className="text-[#2c7870] flex items-center justify-center">
                        <CalendarCheckIcon className="w-8 h-8" />
                    </div>
                    <div className="text-gray-900"><span className="font-semibold">Active Plan:</span> {activePlan}</div>
                </div>
            </div>

            {/* Actions */}
            <div className="mt-6 flex items-center justify-between">
                <button
                    className="text-[#2c7870] hover:text-[#236158] font-medium"
                    onClick={() => window.location.href = '/guardian/children'}>
                    View Details
                </button>
                <Link href={route('guardian.children.create')}>
                    <Button className="bg-[#2c7870] hover:bg-[#236158] text-white rounded-full px-6 py-2">
                        Add New Child
                    </Button>
                </Link>
            </div>
        </div>
    );
}


