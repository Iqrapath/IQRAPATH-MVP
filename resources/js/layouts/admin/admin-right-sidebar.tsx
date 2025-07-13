import { cn } from '@/lib/utils';
import { ReactNode } from 'react';

interface AdminRightSidebarProps {
    children?: ReactNode;
    className?: string;
}

export default function AdminRightSidebar({ 
    children,
    className
}: AdminRightSidebarProps) {
    const defaultContent = (
        <div className="rounded-lg bg-teal-600 p-6 text-white">
            <div className="flex justify-center">
                <img 
                    src="/assets/images/quran.png" 
                    alt="Quran" 
                    className="w-40 h-auto"
                />
            </div>
            <h3 className="text-xl font-semibold mb-2">Full Quran Memorization</h3>
            <p className="text-sm mb-4">A comprehensive memorization program for students aiming to memorize the entire Quran.</p>
            <div className="bg-teal-700 rounded p-2 text-center">
                ₦90,000 / Month
            </div>
            <div className="mt-4">
                <h4 className="font-medium mb-2">Plan Features</h4>
                <ul className="space-y-1 text-sm">
                    <li className="flex items-center">• Daily Quran Sessions</li>
                    <li className="flex items-center">• Weekly Assessments</li>
                    <li className="flex items-center">• Final Certificate on Completion</li>
                    <li className="flex items-center">• Personalized Learning Plan</li>
                </ul>
            </div>
        </div>
    );

    return (
        <div className={cn("w-72 p-4", className)}>
            {children || defaultContent}
        </div>
    );
} 