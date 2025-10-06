import React, { useState } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

interface TeacherAvailability {
    id: number;
    day_of_week: number;
    start_time: string;
    end_time: string;
    is_active: boolean;
    time_zone?: string;
    formatted_time: string;
    availability_type?: string;
}

interface BookingCalendarProps {
    availabilities: TeacherAvailability[];
    selectedDates: Date[];
    onDateSelect: (date: Date) => void;
}

export default function BookingCalendar({ availabilities, selectedDates, onDateSelect }: BookingCalendarProps) {
    const [currentDate, setCurrentDate] = useState(new Date());

    const months = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    // Check if teacher has availability on a specific day of week
    const hasAvailabilityOnDay = (dayOfWeek: number) => {
        return availabilities.some(availability =>
            availability.day_of_week === dayOfWeek && availability.is_active
        );
    };

    // Generate calendar days for the current month
    const generateCalendarDays = () => {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        const firstDay = new Date(year, month, 1);
        const startDate = new Date(firstDay);
        startDate.setDate(startDate.getDate() - firstDay.getDay());

        const days = [];
        const currentDateToShow = new Date(startDate);

        for (let week = 0; week < 6; week++) {
            for (let day = 0; day < 7; day++) {
                const isCurrentMonth = currentDateToShow.getMonth() === month;
                const isToday = currentDateToShow.toDateString() === new Date().toDateString();
                const isPast = currentDateToShow < new Date() && !isToday;
                const dayOfWeek = currentDateToShow.getDay();
                const hasAvailability = hasAvailabilityOnDay(dayOfWeek);

                days.push({
                    date: currentDateToShow.getDate(),
                    isCurrentMonth,
                    isToday,
                    isPast,
                    hasAvailability,
                    dayOfWeek,
                    fullDate: new Date(currentDateToShow)
                });
                currentDateToShow.setDate(currentDateToShow.getDate() + 1);
            }
            if (currentDateToShow.getMonth() !== month && week >= 4) break;
        }

        return days;
    };

    const calendarDays = generateCalendarDays();

    // Navigate months
    const navigateMonth = (direction: 'prev' | 'next') => {
        const newDate = new Date(currentDate);
        if (direction === 'prev') {
            newDate.setMonth(newDate.getMonth() - 1);
        } else {
            newDate.setMonth(newDate.getMonth() + 1);
        }
        setCurrentDate(newDate);
    };

    // Find next month with availability
    const findNextMonthWithAvailability = () => {
        const checkDate = new Date(currentDate);
        for (let i = 0; i < 12; i++) {
            checkDate.setMonth(checkDate.getMonth() + 1);
            const year = checkDate.getFullYear();
            const month = checkDate.getMonth();
            const firstDay = new Date(year, month, 1);
            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - firstDay.getDay());

            const days = [];
            const currentDateToShow = new Date(startDate);

            for (let week = 0; week < 6; week++) {
                for (let day = 0; day < 7; day++) {
                    const isCurrentMonth = currentDateToShow.getMonth() === month;
                    const dayOfWeek = currentDateToShow.getDay();
                    const hasAvailability = hasAvailabilityOnDay(dayOfWeek);

                    if (isCurrentMonth && hasAvailability) {
                        return checkDate;
                    }
                    currentDateToShow.setDate(currentDateToShow.getDate() + 1);
                }
                if (currentDateToShow.getMonth() !== month && week >= 4) break;
            }
        }
        return null;
    };

    return (
        <div className="space-y-4">
            {/* Month Navigation */}
            <div className="flex items-center justify-between">
                <button
                    onClick={() => navigateMonth('prev')}
                    className="w-8 h-8 bg-gray-100 hover:bg-gray-200 rounded-lg flex items-center justify-center transition-colors"
                >
                    <ChevronLeft className="w-4 h-4 text-gray-600" />
                </button>

                <h3 className="text-lg font-bold text-gray-900">
                    {months[currentDate.getMonth()]} {currentDate.getFullYear()}
                </h3>

                <button
                    onClick={() => navigateMonth('next')}
                    className="w-8 h-8 bg-gray-100 hover:bg-gray-200 rounded-lg flex items-center justify-center transition-colors"
                >
                    <ChevronRight className="w-4 h-4 text-gray-600" />
                </button>
            </div>

            {/* Month Labels - All 12 months with horizontal scroll */}
            <div className="overflow-x-auto scrollbar-hide px-4">
                <div className="flex space-x-2 pb-2 min-w-max">
                    {months.map((month, index) => (
                        <button
                            key={month}
                            onClick={() => {
                                const newDate = new Date(currentDate);
                                newDate.setMonth(index);
                                setCurrentDate(newDate);
                            }}
                            className={`px-3 py-1 rounded-lg text-sm font-medium transition-colors whitespace-nowrap ${
                                index === currentDate.getMonth()
                                    ? 'bg-[#2c7870] text-white'
                                    : 'text-gray-500 hover:text-[#2c7870] hover:bg-gray-100'
                            }`}
                        >
                            {month}
                        </button>
                    ))}
                </div>
            </div>

            {/* Day Numbers - Only show days with teacher availability */}
            {calendarDays.filter(day => day.isCurrentMonth && day.hasAvailability).length > 0 ? (
                <div className="flex justify-center space-x-4">
                    {calendarDays
                        .filter(day => day.isCurrentMonth && day.hasAvailability)
                        .slice(0, 7)
                        .map((day, index) => {
                            const isSelected = selectedDates.some(date => date.toDateString() === day.fullDate.toDateString());
                            const canSelect = !day.isPast;

                            return (
                                <button
                                    key={index}
                                    onClick={() => {
                                        if (canSelect) {
                                            onDateSelect(day.fullDate);
                                        }
                                    }}
                                    disabled={!canSelect}
                                    className={`w-8 h-8 rounded-full text-sm font-medium transition-colors ${
                                        isSelected
                                            ? 'bg-[#2c7870] text-white'
                                            : canSelect
                                                ? 'text-gray-900 hover:bg-gray-100'
                                                : 'text-gray-400 cursor-not-allowed'
                                    }`}
                                >
                                    {day.date}
                                </button>
                            );
                        })}
                </div>
            ) : (
                <div className="text-center py-4 text-gray-500">
                    <p className="text-sm mb-2">No available days this month</p>
                    {findNextMonthWithAvailability() && (
                        <button
                            onClick={() => setCurrentDate(findNextMonthWithAvailability()!)}
                            className="text-sm text-[#2c7870] hover:text-[#236158] font-medium"
                        >
                            Go to next available month →
                        </button>
                    )}
                </div>
            )}

            {/* Days of Week Labels - Only show days with availability */}
            {calendarDays.filter(day => day.isCurrentMonth && day.hasAvailability).length > 0 && (
                <div className="flex justify-center space-x-4">
                    {calendarDays
                        .filter(day => day.isCurrentMonth && day.hasAvailability)
                        .slice(0, 7)
                        .map((day, index) => (
                            <span key={index} className="text-xs text-gray-500 w-8 text-center">
                                {daysOfWeek[day.dayOfWeek]}
                            </span>
                        ))}
                </div>
            )}
        </div>
    );
}
