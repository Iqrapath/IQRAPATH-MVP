import React from 'react';
import { Calendar } from 'lucide-react';

interface ClassItem {
    id: number;
    title: string;
    teacher: string;
    date: string; // formatted date string
    time: string; // formatted time range
    status: 'Confirmed' | 'Pending';
    imageUrl: string;
}

interface UpcomingClassesProps {
    classes: ClassItem[];
}

export default function UpcomingClasses({ classes }: UpcomingClassesProps) {
    return (
        <div className="rounded-[28px] bg-white shadow-sm border border-gray-100 p-6 md:p-8 max-w-6xl mx-auto">
            {/* Header */}
            <div className="flex items-center justify-between mb-6">
                <h3 className="text-2xl font-semibold text-gray-900">Upcoming Class</h3>
                <button className="text-[#2c7870] hover:text-[#236158] font-medium">View ALL Class</button>
            </div>

            {classes.length > 0 ? (
                <div className="divide-y divide-gray-100">
                    {classes.map((cls) => (
                        <div key={cls.id} className="py-5 flex items-center">
                            {/* thumbnail */}
                            <img src={cls.imageUrl} alt="class" className="w-20 h-20 rounded-2xl object-cover mr-4" />

                            <div className="flex-1">
                                <div className="text-lg font-semibold text-gray-900 mb-1">{cls.title}</div>
                                <div className="text-sm text-gray-500 mb-3">By {cls.teacher}</div>

                                <div className="flex items-center gap-3 text-sm">
                                    <span className="bg-[#f4faf9] text-[#2c7870] rounded-full px-3 py-1">{cls.date}</span>
                                    <span className="bg-[#f4faf9] text-[#2c7870] rounded-full px-3 py-1">{cls.time}</span>
                                    <span className={`rounded-full px-3 py-1 ${cls.status === 'Confirmed' ? 'bg-teal-50 text-teal-700' : 'bg-amber-50 text-amber-700'}`}>{cls.status}</span>
                                </div>
                            </div>

                            <button className="bg-[#2c7870] hover:bg-[#236158] text-white rounded-full py-2 px-5">Start Session</button>
                        </div>
                    ))}
                </div>
            ) : (
                <div className="text-center py-12">
                    <div className="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <Calendar className="w-12 h-12 text-gray-400" />
                    </div>
                    <h4 className="text-lg font-medium text-gray-900 mb-2">No Upcoming Classes</h4>
                    <p className="text-gray-500 mb-4">You don't have any scheduled classes at the moment.</p>
                    <button className="bg-[#2c7870] hover:bg-[#236158] text-white rounded-full py-2 px-6">
                        Browse Teachers
                    </button>
                </div>
            )}
        </div>
    );
}


