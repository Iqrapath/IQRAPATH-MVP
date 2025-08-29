import { ChevronDown } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface WeeklyAttendanceData {
    monday: number;
    tuesday: number;
    wednesday: number;
    thursday: number;
    friday: number;
    saturday: number;
    sunday: number;
}

interface WeeklyClassAttendanceProps {
    attendanceData: WeeklyAttendanceData;
    totalSessions: number;
    attendedSessions: number;
}

const daysOfWeek = [
    { key: 'monday', label: 'Mon' },
    { key: 'tuesday', label: 'Tue' },
    { key: 'wednesday', label: 'Wed' },
    { key: 'thursday', label: 'Thu' },
    { key: 'friday', label: 'Fri' },
    { key: 'saturday', label: 'Sat' },
    { key: 'sunday', label: 'Sun' },
];

const getBarColor = (percentage: number) => {
    if (percentage >= 80) return 'bg-green-400'; // Light green for high attendance
    if (percentage >= 50) return 'bg-red-300';   // Light red/pink for medium attendance
    return 'bg-gray-300';                        // Light gray for low/no attendance
};

export default function WeeklyClassAttendance({ attendanceData, totalSessions, attendedSessions }: WeeklyClassAttendanceProps) {
    const maxHeight = 120; // Maximum height for 100%

    return (
        <div className="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
            {/* Header */}
            <div className="flex justify-between items-center mb-6">
                <h3 className="text-lg font-semibold text-gray-900">
                    Weekly Class Attendance
                </h3>
                <Button variant="outline" size="sm" className="text-gray-700 bg-gray-50 border-gray-200">
                    This Week
                    <ChevronDown className="w-4 h-4 ml-1" />
                </Button>
            </div>

            {/* Chart Container */}
            <div className="relative h-32 mb-4">
                {/* Y-axis labels */}
                <div className="absolute left-0 top-0 h-full flex flex-col justify-between text-xs text-gray-500">
                    <span>100%</span>
                    <span>80%</span>
                    <span>60%</span>
                    <span>40%</span>
                    <span>20%</span>
                    <span>0%</span>
                </div>

                {/* Grid lines */}
                <div className="absolute left-8 right-0 top-0 h-full">
                    {[0, 20, 40, 60, 80, 100].map((percentage) => (
                        <div
                            key={percentage}
                            className="absolute w-full border-t border-gray-200"
                            style={{ top: `${100 - percentage}%` }}
                        />
                    ))}
                </div>

                {/* Bars */}
                <div className="absolute left-8 right-0 bottom-0 h-full flex items-end justify-between gap-2">
                    {daysOfWeek.map((day) => {
                        const percentage = attendanceData[day.key as keyof WeeklyAttendanceData];
                        const height = (percentage / 100) * maxHeight;
                        
                        return (
                            <div key={day.key} className="flex-1 flex flex-col items-center">
                                <div
                                    className={`w-full rounded-t-sm ${getBarColor(percentage)} transition-all duration-300`}
                                    style={{ height: `${height}px` }}
                                />
                                <span className="text-xs text-gray-600 mt-2">
                                    {day.label}
                                </span>
                            </div>
                        );
                    })}
                </div>
            </div>

            {/* Footer */}
            <div className="flex items-center text-sm text-gray-600">
                <div className="w-2 h-2 bg-green-500 rounded-full mr-2" />
                <span>
                    {attendedSessions}/{totalSessions} sessions attended this week. Keep it up!
                </span>
            </div>
        </div>
    );
}
