import { cn } from '@/lib/utils';
import { ReactNode } from 'react';

interface TeacherRightSidebarProps {
    children?: ReactNode;
    className?: string;
}

export default function TeacherRightSidebar({ 
    children,
    className
}: TeacherRightSidebarProps) {
    const defaultContent = (
        <div className="rounded-lg bg-teal-600 p-6 text-white">
            <div className="flex justify-center">
                <img 
                    src="/assets/images/quran.png" 
                    alt="Quran" 
                    className="w-40 h-auto"
                />
            </div>
            <h3 className="text-xl font-semibold mb-2">Upcoming Sessions</h3>
            <p className="text-sm mb-4">You have 3 teaching sessions scheduled for today.</p>
            
            <div className="space-y-3">
                <div className="bg-teal-700 rounded p-2">
                    <div className="flex justify-between items-center">
                        <span className="font-medium">Ahmed Hassan</span>
                        <span className="text-xs">10:00 AM</span>
                    </div>
                    <div className="text-xs mt-1">Quran Memorization - Surah Al-Baqarah</div>
                </div>
                
                <div className="bg-teal-700 rounded p-2">
                    <div className="flex justify-between items-center">
                        <span className="font-medium">Fatima Ali</span>
                        <span className="text-xs">1:30 PM</span>
                    </div>
                    <div className="text-xs mt-1">Tajweed Rules - Advanced</div>
                </div>
                
                <div className="bg-teal-700 rounded p-2">
                    <div className="flex justify-between items-center">
                        <span className="font-medium">Ibrahim Khan</span>
                        <span className="text-xs">4:00 PM</span>
                    </div>
                    <div className="text-xs mt-1">Quran Memorization - Surah Yaseen</div>
                </div>
            </div>
            
            <div className="mt-4 text-center">
                <button className="bg-white text-teal-700 px-4 py-2 rounded text-sm font-medium">
                    View All Sessions
                </button>
            </div>
        </div>
    );

    return (
        <div className={cn("w-72 p-4", className)}>
            {children || defaultContent}
        </div>
    );
} 