import { type ReactNode } from 'react';
import { Link } from '@inertiajs/react';

interface StatPillProps {
    title: string;
    value: number | string;
    icon: ReactNode;
    gradient: string; // classes for tailwind gradient e.g. from-purple-50 to-blue-50
    href?: string; // Optional link for navigation
    onClick?: () => void; // Optional click handler
}

export default function StatPill({ title, value, icon, gradient, href, onClick }: StatPillProps) {
    const content = (
        <div className="absolute left-8 top-1/2 -translate-y-1/2">
            <div className="text-[#2c7870] mb-1">
                {icon}
            </div>
            <div className="text-sm text-gray-800 font-medium mt-2">{title}</div>
        </div>
    );

    const valueContent = (
        <div className="absolute right-10 top-1/2 -translate-y-1/2 text-[#2c7870] text-2xl font-semibold">
            {value}
        </div>
    );

    const baseClasses = `bg-gradient-to-l ${gradient} rounded-full py-5 px-8 relative h-24`;
    const interactiveClasses = href || onClick ? 'cursor-pointer hover:shadow-md transition-all hover:scale-105' : '';
    
    if (href) {
        return (
            <Link href={href} className={`${baseClasses} ${interactiveClasses} block`}>
                {content}
                {valueContent}
            </Link>
        );
    }
    
    if (onClick) {
        return (
            <div 
                className={`${baseClasses} ${interactiveClasses}`}
                onClick={onClick}
                role="button"
                tabIndex={0}
                onKeyDown={(e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        onClick();
                    }
                }}
            >
                {content}
                {valueContent}
            </div>
        );
    }

    return (
        <div className={baseClasses}>
            {content}
            {valueContent}
        </div>
    );
}
