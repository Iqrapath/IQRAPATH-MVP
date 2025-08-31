import React, { useState } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

interface TeacherTabCalendarProps {
  teacherId: number;
  availabilityData?: {
    availability_type?: string;
    time_slots?: Array<{
      start_time: string;
      end_time: string;
      day_of_week: number;
      color?: string;
      label?: string;
      is_active?: boolean;
      time_zone?: string;
    }>;
  };
}

export default function TeacherTabCalendar({ teacherId, availabilityData }: TeacherTabCalendarProps) {
  const [currentDate, setCurrentDate] = useState(new Date());
  const [selectedDay, setSelectedDay] = useState(new Date().getDate());
  
  const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ];
  
  const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  
  // Generate calendar days for the current month
  const generateCalendarDays = () => {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDate = new Date(firstDay);
    startDate.setDate(startDate.getDate() - firstDay.getDay());
    
    const days = [];
    const currentDateToShow = new Date(startDate);
    
    // Generate 6 weeks (42 days) to ensure full month coverage
    for (let week = 0; week < 6; week++) {
      for (let day = 0; day < 7; day++) {
        const isCurrentMonth = currentDateToShow.getMonth() === month;
        const isToday = currentDateToShow.toDateString() === new Date().toDateString();
        days.push({
          date: currentDateToShow.getDate(),
          isCurrentMonth,
          isToday,
          fullDate: new Date(currentDateToShow)
        });
        currentDateToShow.setDate(currentDateToShow.getDate() + 1);
      }
      // Break if we've covered the whole month
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
  
  // Color palette for time slots
  const slotColors = ['#22C55E', '#3B82F6', '#F97316', '#F59E0B', '#8B5CF6', '#EC4899'];
  
  // Process database time slots
  const processTimeSlots = () => {
    if (availabilityData?.time_slots && availabilityData.time_slots.length > 0) {
      return availabilityData.time_slots.map((slot, index) => {
        // Convert 24h to 12h format
        const formatTime = (timeStr: string) => {
          const time = new Date(`1970-01-01T${timeStr}`);
          return time.toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit',
            hour12: true 
          });
        };

        return {
          time: formatTime(slot.start_time),
          endTime: formatTime(slot.end_time),
          color: slot.color || slotColors[index % slotColors.length],
          available: slot.is_active !== false,
          dayOfWeek: slot.day_of_week,
          rawStartTime: slot.start_time,
          rawEndTime: slot.end_time
        };
      });
    }
    
    // Return empty array if no data
    return [];
  };

  const allTimeSlots = processTimeSlots();
  const availabilityType = availabilityData?.availability_type;
  
  // Filter time slots for the selected day (0 = Sunday, 1 = Monday, etc.)
  const getSelectedDayOfWeek = () => {
    const date = new Date(currentDate.getFullYear(), currentDate.getMonth(), selectedDay);
    return date.getDay();
  };
  
  // Get time slots for the selected day
  const timeSlots = allTimeSlots.filter(slot => {
    // If slot has a specific day, match it; otherwise show all slots
    return slot.dayOfWeek === undefined || slot.dayOfWeek === getSelectedDayOfWeek();
  });

  return (
    <div className="bg-white border border-gray-100 rounded-2xl p-4">
      {/* Compact Calendar Header */}
      <div className="flex items-center justify-between mb-4">
        <button 
          onClick={() => navigateMonth('prev')}
          className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
        >
          <ChevronLeft className="w-4 h-4 text-gray-500" />
        </button>
        <h3 className="text-base font-semibold text-gray-900">
          {months[currentDate.getMonth()]} {currentDate.getFullYear()}
        </h3>
        <button 
          onClick={() => navigateMonth('next')}
          className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
        >
          <ChevronRight className="w-4 h-4 text-gray-500" />
        </button>
      </div>

      {/* Compact Calendar Grid */}
      <div className="bg-gray-50 rounded-xl p-3 mb-4">
        {/* Days of week header */}
        <div className="grid grid-cols-7 gap-1 mb-2">
          {daysOfWeek.map(day => (
            <div key={day} className="text-center text-xs font-medium text-gray-500 py-1">
              {day.slice(0, 3)}
            </div>
          ))}
        </div>
        
        {/* Calendar days - Full month */}
        <div className="grid grid-cols-7 gap-1">
          {calendarDays.map((day, index) => {
            const isSelected = day.isCurrentMonth && day.date === selectedDay;
            const hasAvailability = day.isCurrentMonth && allTimeSlots.some(slot => 
              slot.dayOfWeek === day.fullDate.getDay() && slot.available
            );
            
            return (
              <div
                key={index}
                onClick={() => day.isCurrentMonth && setSelectedDay(day.date)}
                className={`
                  text-center py-1.5 text-xs rounded-full cursor-pointer transition-all duration-200 relative min-h-[28px] flex items-center justify-center
                  ${day.isCurrentMonth 
                    ? isSelected 
                      ? 'bg-[#2C7870] text-white' 
                      : hasAvailability
                        ? 'bg-[#2C7870]/10 text-[#2C7870] hover:bg-[#2C7870]/20 font-medium'
                        : 'text-gray-900 hover:bg-gray-100'
                    : 'text-gray-300'
                  }
                  ${day.isToday && day.isCurrentMonth && !isSelected 
                    ? 'ring-1 ring-[#2C7870] ring-opacity-50 rounded-full' 
                    : ''
                  }
                `}
              >
                {day.date}
                {hasAvailability && !isSelected && (
                  <div className="absolute bottom-0.5 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-[#2C7870] rounded-full"></div>
                )}
              </div>
            );
          })}
        </div>
      </div>

      {/* Availability Section */}
      <div>
        <div className="flex items-center gap-2 mb-3">
          <h4 className="text-sm font-medium text-gray-900">Availability:</h4>
          <span className="text-sm text-gray-600">{availabilityType}</span>
        </div>
        
        {/* Selected day indicator */}
        <div className="mb-3">
          <div className="flex items-center gap-2 text-gray-900">
            <span className="text-lg font-semibold">{selectedDay}</span>
            <span className="text-sm">{months[currentDate.getMonth()]}</span>
          </div>
        </div>

        {/* Time slots display */}
        {timeSlots.length > 0 ? (
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
            {timeSlots.map((slot, index) => (
              <div key={index} className="flex flex-col items-center p-2 bg-gray-50 rounded-lg">
                <div 
                  className={`w-2 h-2 rounded-full mb-1 ${
                    slot.available ? '' : 'opacity-50'
                  }`}
                  style={{ backgroundColor: slot.color }}
                />
                <div className="text-center">
                  <div className="text-xs font-medium text-gray-900">{slot.time}</div>
                  <div className="text-xs text-gray-500">{slot.endTime}</div>
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className="text-center py-4 text-sm text-gray-500">
            No availability scheduled for this day
          </div>
        )}
      </div>
    </div>
  );
}


