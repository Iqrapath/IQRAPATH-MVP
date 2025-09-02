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
    selectedDate: Date | null;
    onDateSelect: (date: Date) => void;
}

export default function BookingCalendar({ availabilities, selectedDate, onDateSelect }: BookingCalendarProps) {
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

    return (
        <div>
            <h3 className="text-lg font-bold text-xl text-[#212121] mb-6">Select Date & Time</h3>

            <div className="bg-white rounded-2xl p-6 border border-[#E8E8E8]">

                {/* Calendar Header */}
                <div className="flex items-center justify-between mb-6">
                    <button
                        onClick={() => navigateMonth('prev')}
                        className="p-2 hover:bg-[#F8F9FA] rounded-lg transition-colors"
                    >
                        <ChevronLeft className="w-5 h-5 text-[#4F4F4F]" />
                    </button>

                    <h4 className="text-lg font-bold text-xl text-[#212121]">
                        {months[currentDate.getMonth()]} {currentDate.getFullYear()}
                    </h4>

                    <button
                        onClick={() => navigateMonth('next')}
                        className="p-2 hover:bg-[#F8F9FA] rounded-lg transition-colors"
                    >
                        <ChevronRight className="w-5 h-5 text-[#4F4F4F]" />
                    </button>
                </div>

                {/* Calendar Grid */}
                <div className="mb-6">
                    {/* Days of week header */}
                    <div className="grid grid-cols-7 gap-2 mb-3">
                        {daysOfWeek.map(day => (
                            <div key={day} className="text-center text-sm font-medium text-[#828282] py-2">
                                {day}
                            </div>
                        ))}
                    </div>

                    {/* Calendar days */}
                    <div className="grid grid-cols-7 gap-2">
                        {calendarDays.map((day, index) => {
                            const isSelected = selectedDate?.toDateString() === day.fullDate.toDateString();
                            const canSelect = day.isCurrentMonth && !day.isPast && day.hasAvailability;

                            return (
                                <button
                                    key={index}
                                    onClick={() => {
                                        if (canSelect) {
                                            onDateSelect(day.fullDate);
                                        }
                                    }}
                                    disabled={!canSelect}
                                    className={`
                                    h-12 rounded-lg text-sm font-medium transition-all duration-200 relative
                                    ${day.isCurrentMonth
                                            ? isSelected
                                                ? 'bg-[#2C7870] text-white'
                                                : day.hasAvailability
                                                    ? 'text-[#212121] hover:bg-[#F0F8F7] hover:text-[#2C7870] cursor-pointer'
                                                    : 'text-[#BDBDBD] cursor-not-allowed'
                                            : 'text-[#E0E0E0] cursor-not-allowed'
                                        }
                                    ${day.isToday && day.isCurrentMonth && !isSelected
                                            ? 'ring-2 ring-[#2C7870] ring-opacity-30'
                                            : ''
                                        }
                                `}
                                >
                                    {day.date}
                                    {day.hasAvailability && day.isCurrentMonth && !isSelected && (
                                        <div className="absolute bottom-1 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-[#2C7870] rounded-full"></div>
                                    )}
                                </button>
                            );
                        })}
                    </div>
                </div>
            </div>
        </div>
    );
}
