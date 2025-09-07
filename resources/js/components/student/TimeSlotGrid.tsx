import React, { useState } from 'react';
import { Clock } from 'lucide-react';

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

interface TimeSlotGridProps {
    selectedDates: Date[];
    availabilities: TeacherAvailability[];
    selectedAvailabilityIds: number[];
    onTimeSlotSelect: (availabilityId: number) => void;
    onClearAll?: () => void;
    isReschedule?: boolean;
    requiredSlots?: number;
}

export default function TimeSlotGrid({ 
    selectedDates, 
    availabilities, 
    selectedAvailabilityIds, 
    onTimeSlotSelect,
    onClearAll,
    isReschedule = false,
    requiredSlots
}: TimeSlotGridProps) {
    const [selectedTimeFilters, setSelectedTimeFilters] = useState(['Morning', 'Afternoon']);

    // Get available time slots for all selected dates
    const getAvailableTimeSlots = () => {
        if (selectedDates.length === 0 || !availabilities) return [];
        
        const selectedDaysOfWeek = selectedDates.map(date => date.getDay());
        return availabilities.filter(availability => 
            selectedDaysOfWeek.includes(availability.day_of_week) && 
            availability.is_active
        );
    };

    const availableTimeSlots = getAvailableTimeSlots();

    // Filter time slots based on selected time filters
    const getFilteredTimeSlots = () => {
        return availableTimeSlots.filter(slot => {
            const hour = parseInt(slot.start_time.split(':')[0]);
            
            if (selectedTimeFilters.includes('Morning') && hour >= 6 && hour < 12) return true;
            if (selectedTimeFilters.includes('Afternoon') && hour >= 12 && hour < 18) return true;
            if (selectedTimeFilters.includes('Evening') && hour >= 18 && hour < 24) return true;
            
            return false;
        });
    };

    const filteredTimeSlots = getFilteredTimeSlots();

    // Calculate progress for reschedule mode
    const progressPercentage = requiredSlots ? (selectedAvailabilityIds.length / requiredSlots) * 100 : 0;
    const isComplete = requiredSlots ? selectedAvailabilityIds.length >= requiredSlots : false;

    // Handle time filter toggle
    const toggleTimeFilter = (filter: string) => {
        setSelectedTimeFilters(prev => 
            prev.includes(filter) 
                ? prev.filter(f => f !== filter)
                : [...prev, filter]
        );
    };

    return (
        <div className="space-y-6">
            {/* Time Filters */}
            <div className="flex items-center space-x-6">
                <span className="text-sm font-medium text-gray-700">Time of day:</span>
                {['Morning', 'Afternoon', 'Evening'].map((filter) => (
                    <label key={filter} className="flex items-center space-x-2 cursor-pointer">
                        <input
                            type="checkbox"
                            checked={selectedTimeFilters.includes(filter)}
                            onChange={() => toggleTimeFilter(filter)}
                            className="w-4 h-4 text-[#2c7870] border-gray-300 rounded focus:ring-[#2c7870] focus:ring-2 accent-[#2c7870]"
                        />
                        <span className="text-sm text-gray-700">{filter}</span>
                    </label>
                ))}
            </div>

            {/* Time Slot Grid */}
            {selectedDates.length > 0 ? (
                filteredTimeSlots.length > 0 ? (
                    <div className="flex flex-wrap gap-3">
                        {filteredTimeSlots
                            .sort((a, b) => a.start_time.localeCompare(b.start_time))
                            .map((availability) => {
                                const isSelected = selectedAvailabilityIds.includes(availability.id);
                                return (
                                    <button
                                        key={availability.id}
                                        onClick={() => onTimeSlotSelect(availability.id)}
                                        className={`
                                             rounded-lg text-sm font-medium transition-all duration-200 border min-w-[80px] h-16 flex flex-col justify-center items-center
                                            ${isSelected
                                                ? 'bg-[#2c7870] text-white border-[#2c7870]'
                                                : 'bg-[#F8F9FA] text-[#4F4F4F] hover:bg-gray-50 border-[#E8E8E8] hover:border-[#2c7870]'
                                            }
                                        `}
                                    >
                                        <div className="text-center">
                                            <div className="font-medium">{availability.formatted_time.split(' - ')[0]}</div>
                                            <div className="text-xs">-</div>
                                            <div className="font-medium">{availability.formatted_time.split(' - ')[1]}</div>
                                        </div>
                                    </button>
                                );
                            })}
                    </div>
                ) : (
                    <div className="text-center py-8 text-gray-500">
                        <Clock className="w-12 h-12 mx-auto mb-4 text-gray-300" />
                        <p className="text-lg font-medium mb-2">No available time slots</p>
                        <p className="text-sm">No time slots available for the selected filters.</p>
                    </div>
                )
            ) : (
                <div className="text-center py-8 text-gray-500">
                    <Clock className="w-12 h-12 mx-auto mb-4 text-gray-300" />
                    <p className="text-lg font-medium mb-2">Select dates first</p>
                    <p className="text-sm">Please select one or more dates to see available time slots.</p>
                </div>
            )}
        </div>
    );
}
