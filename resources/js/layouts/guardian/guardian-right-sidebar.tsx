import { cn } from '@/lib/utils';
import { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { X } from 'lucide-react';

interface GuardianRightSidebarProps {
    children?: ReactNode;
    className?: string;
    isMobile?: boolean;
    onClose?: () => void;
}

export default function GuardianRightSidebar({ 
    children,
    className,
    isMobile = false,
    onClose
}: GuardianRightSidebarProps) {
    const defaultContent = (
        <div className="rounded-lg bg-teal-600 p-6 text-white">
            <div className="flex justify-center">
                <img 
                    src="/assets/images/quran.png" 
                    alt="Quran" 
                    className="w-40 h-auto"
                />
            </div>
            <h3 className="text-xl font-semibold mb-2">Children's Progress</h3>
            <p className="text-sm mb-4">Your children are making excellent progress in their studies.</p>
            
            <div className="space-y-3">
                <div className="bg-teal-700 rounded p-2">
                    <div className="flex justify-between items-center">
                        <span className="font-medium">Ahmed</span>
                        <span className="text-xs">75% Complete</span>
                    </div>
                    <div className="w-full bg-teal-800 rounded-full h-1.5 mt-2">
                        <div className="bg-white h-1.5 rounded-full" style={{ width: '75%' }}></div>
                    </div>
                    <div className="text-xs mt-1">Quran Memorization - Juz' 29</div>
                </div>
                
                <div className="bg-teal-700 rounded p-2">
                    <div className="flex justify-between items-center">
                        <span className="font-medium">Fatima</span>
                        <span className="text-xs">60% Complete</span>
                    </div>
                    <div className="w-full bg-teal-800 rounded-full h-1.5 mt-2">
                        <div className="bg-white h-1.5 rounded-full" style={{ width: '60%' }}></div>
                    </div>
                    <div className="text-xs mt-1">Tajweed Rules - Level 2</div>
                </div>
            </div>
            
            <div className="mt-4">
                <h4 className="font-medium mb-2">Upcoming Sessions</h4>
                <div className="bg-teal-700 rounded p-2 mb-2">
                    <div className="flex justify-between items-center">
                        <span className="font-medium">Ahmed</span>
                        <span className="text-xs">Today, 4:00 PM</span>
                    </div>
                    <div className="text-xs mt-1">With Ustadh Ibrahim</div>
                </div>
                <div className="bg-teal-700 rounded p-2">
                    <div className="flex justify-between items-center">
                        <span className="font-medium">Fatima</span>
                        <span className="text-xs">Tomorrow, 10:00 AM</span>
                    </div>
                    <div className="text-xs mt-1">With Ustadha Aisha</div>
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
        <div className={cn(
            "w-72 p-4",
            isMobile && "bg-white shadow-xl h-full",
            className
        )}>
            {isMobile && (
                <div className="flex justify-between items-center mb-4">
                    <h3 className="text-lg font-medium">Details</h3>
                    <Button variant="ghost" size="sm" className="p-1 h-auto" onClick={onClose}>
                        <X className="h-4 w-4" />
                    </Button>
                </div>
            )}
            {children || defaultContent}
        </div>
    );
} 