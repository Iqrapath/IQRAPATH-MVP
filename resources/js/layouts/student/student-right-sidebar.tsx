import { cn } from '@/lib/utils';
import { ReactNode } from 'react';

interface StudentRightSidebarProps {
    children?: ReactNode;
    className?: string;
}

export default function StudentRightSidebar({ 
    children,
    className
}: StudentRightSidebarProps) {
    const defaultContent = (
        <div className="rounded-lg bg-teal-600 p-6 text-white">
            <div className="flex justify-center">
                <img 
                    src="/assets/images/quran.png" 
                    alt="Quran" 
                    className="w-40 h-auto"
                />
            </div>
            <h3 className="text-xl font-semibold mb-2">My Learning Progress</h3>
            <p className="text-sm mb-4">You've completed 65% of your current course.</p>
            
            <div className="w-full bg-teal-700 rounded-full h-2.5 mb-4">
                <div className="bg-white h-2.5 rounded-full" style={{ width: '65%' }}></div>
            </div>
            
            <div className="space-y-3">
                <div className="bg-teal-700 rounded p-2">
                    <div className="flex justify-between items-center">
                        <span className="font-medium">Next Session</span>
                        <span className="text-xs">Today, 3:00 PM</span>
                    </div>
                    <div className="text-xs mt-1">Quran Memorization with Ustadh Ahmad</div>
                </div>
                
                <div className="bg-teal-700 rounded p-2">
                    <div className="flex justify-between items-center">
                        <span className="font-medium">Homework Due</span>
                        <span className="text-xs">Tomorrow</span>
                    </div>
                    <div className="text-xs mt-1">Memorize Surah Al-Mulk, Verses 1-10</div>
                </div>
            </div>
            
            <div className="mt-4 text-center">
                <button className="bg-white text-teal-700 px-4 py-2 rounded text-sm font-medium">
                    View Full Schedule
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