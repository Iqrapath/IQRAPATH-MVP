import { type ReactNode } from 'react';

interface StatPillProps {
    title: string;
    value: number | string;
    icon: ReactNode;
    gradient: string; // classes for tailwind gradient e.g. from-purple-50 to-blue-50
}

export default function StatPill({ title, value, icon, gradient }: StatPillProps) {
    return (
        <div className={`bg-gradient-to-l ${gradient} rounded-full py-5 px-8 relative h-24`}> 
            <div className="absolute left-8 top-1/2 -translate-y-1/2">
                <div className="text-[#2c7870] mb-1">
                    {icon}
                </div>
                <div className="text-sm text-gray-800 font-medium mt-2">{title}</div>
            </div>
            <div className="absolute right-10 top-1/2 -translate-y-1/2 text-[#2c7870] text-2xl font-semibold">
                {value}
            </div>
        </div>
    );
}


