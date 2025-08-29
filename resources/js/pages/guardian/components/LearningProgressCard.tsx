import React from 'react';

interface SubjectItem {
    label: string;
    status: string;
    dotColor: 'yellow' | 'green';
}

interface LearningProgressCardProps {
    juzName: string;
    percent: number; // 0-100
    subjects: SubjectItem[];
}

function Donut({ percent }: { percent: number }) {
    const radius = 60;
    const circumference = 2 * Math.PI * radius;
    const offset = circumference - (percent / 100) * circumference;
    return (
        <svg width="180" height="180" viewBox="0 0 180 180">
            <circle
                cx="90"
                cy="90"
                r={radius}
                fill="transparent"
                stroke="#eef2f3"
                strokeWidth="18"
            />
            <circle
                cx="90"
                cy="90"
                r={radius}
                fill="transparent"
                stroke="url(#grad)"
                strokeWidth="18"
                strokeDasharray={circumference}
                strokeDashoffset={offset}
                strokeLinecap="round"
                transform="rotate(-90 90 90)"
            />
            <defs>
                <linearGradient id="grad" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stopColor="#2c7870" />
                    <stop offset="100%" stopColor="#e5ddb0" />
                </linearGradient>
            </defs>
        </svg>
    );
}

export default function LearningProgressCard({ juzName, percent, subjects }: LearningProgressCardProps) {
    return (
        <div className="rounded-[28px] bg-white shadow-sm border border-gray-100 p-6 md:p-8 max-w-6xl mx-auto">
            <div className="flex items-end justify-end">
                <button className="text-[#2c7870] hover:text-[#236158] font-medium">View Progress</button>
            </div>

            <div className="mt-4 flex flex-col md:flex-row items-center gap-8">
                <div className="text-2xl font-semibold text-[#1b1548]">{juzName}</div>
                {/* Donut */}
                <div className="relative">
                    <Donut percent={percent} />
                    <div className="absolute inset-0 flex flex-col items-center justify-center">
                        <div className="text-gray-500 text-sm">Completed</div>
                        <div className="text-2xl font-bold">{percent}%</div>
                    </div>
                </div>

                {/* Subjects */}
                <div className="flex-1">
                    <div className="text-2xl font-semibold text-[#1b1548] mb-3">Subjects:</div>
                    <ul className="space-y-3">
                        {subjects.map((s, i) => (
                            <li key={i} className="flex items-center gap-3 text-gray-800">
                                <span className="w-1.5 h-1.5 rounded-full bg-[#1b1548]"></span>
                                <span className="flex-1">
                                    {s.label}: {s.status}
                                </span>
                                <span className={`w-3.5 h-3.5 rounded-full ${s.dotColor === 'yellow' ? 'bg-yellow-400' : 'bg-green-500'}`}></span>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </div>
    );
}


