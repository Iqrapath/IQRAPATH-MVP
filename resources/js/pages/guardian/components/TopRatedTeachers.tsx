import React from 'react';
import { GraduationCap } from 'lucide-react';
import TeacherCard from './TeacherCard';

interface TeacherItem {
    id: number;
    name: string;
    subjects: string;
    location: string;
    rating: number;
    price: string;
    avatarUrl: string;
}

interface TopRatedTeachersProps {
    teachers: TeacherItem[];
}

export default function TopRatedTeachers({ teachers }: TopRatedTeachersProps) {
    return (
        <div className="max-w-6xl mx-auto scrollbar-hide scrollbar-thin scrollbar-thumb-teal-600"
            style={{
                scrollbarWidth: 'none',
                msOverflowStyle: 'none',
            }}
        >
            <div className="flex items-center justify-between mb-4">
                <h3 className="text-xl font-semibold text-gray-900">Top Rated Teachers for You</h3>
            </div>

            {teachers.length > 0 ? (
                <div className="overflow-x-auto pb-4"
                    style={{
                        scrollbarWidth: 'none',
                        msOverflowStyle: 'none',
                    }}
                >
                    <div className="flex gap-6 min-w-max">
                        {teachers.map(t => (
                            <div key={t.id} className="flex-shrink-0 w-80">
                                <TeacherCard {...t} />
                            </div>
                        ))}
                    </div>
                </div>
            ) : (
                <div className="text-center py-12 bg-white rounded-3xl border border-gray-100">
                    <div className="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <GraduationCap className="w-12 h-12 text-gray-400" />
                    </div>
                    <h4 className="text-lg font-medium text-gray-900 mb-2">No Teachers Available</h4>
                    <p className="text-gray-500">We're working on finding the best teachers for you.</p>
                </div>
            )}
        </div>
    );
}


