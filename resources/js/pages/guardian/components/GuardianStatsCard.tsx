import React, { ReactNode } from 'react';
import StatPill from './StatPill';

interface StatItem {
    title: string;
    value: number | string;
    icon: ReactNode;
    gradient: string;
}

interface GuardianStatsCardProps {
    headerAction?: ReactNode;
    stats: StatItem[];
}

export default function GuardianStatsCard({ headerAction, stats }: GuardianStatsCardProps) {
    return (
        <div className="rounded-[32px] bg-white p-6 md:p-6 shadow-xl border border-gray-100">
            <div className="flex items-center justify-between mb-4 md:mb-4">
                <h2 className="text-xl font-bold text-gray-800">Your Stats</h2>
                <div>{headerAction}</div>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                {stats.map((s, i) => (
                    <StatPill key={i} title={s.title} value={s.value} icon={s.icon} gradient={s.gradient} />
                ))}
            </div>
            <div>
                <button 
                className="text-[#2c7870] hover:text-[#236158] font-medium"
                onClick={() => window.location.href = '/guardian/children'}
                >View Details</button>
            </div>
        </div>
    );
}


