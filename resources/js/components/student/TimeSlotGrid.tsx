import React from 'react';
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
    selectedDate: Date | null;
    availabilities: TeacherAvailability[];
    selectedAvailabilityIds: number[];
    onTimeSlotSelect: (availabilityId: number) => void;
    onClearAll?: () => void;
}

export default function TimeSlotGrid({ 
    selectedDate, 
    availabilities, 
    selectedAvailabilityIds, 
    onTimeSlotSelect,
    onClearAll 
}: TimeSlotGridProps) {
    // Get available time slots for the selected date
    const getAvailableTimeSlots = () => {
        if (!selectedDate || !availabilities) return [];
        
        const selectedDayOfWeek = selectedDate.getDay();
        return availabilities.filter(availability => 
            availability.day_of_week === selectedDayOfWeek && 
            availability.is_active
        );
    };

    const availableTimeSlots = getAvailableTimeSlots();

    return (
        <div className="bg-white rounded-2xl p-6 border border-[#E8E8E8]">
            <div className="flex items-center justify-between mb-6">
                <h3 className="text-lg font-semibold text-[#212121]">
                    Select time slots
                    {selectedAvailabilityIds.length > 0 && (
                        <span className="text-sm font-normal text-[#4F4F4F] ml-2">
                            ({selectedAvailabilityIds.length} selected)
                        </span>
                    )}
                </h3>
                {selectedAvailabilityIds.length > 0 && onClearAll && (
                    <button
                        onClick={onClearAll}
                        className="text-sm text-[#828282] hover:text-[#2C7870] transition-colors"
                    >
                        Clear all
                    </button>
                )}
            </div>
            
            {selectedDate ? (
                availableTimeSlots.length > 0 ? (
                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        {availableTimeSlots.map((availability) => {
                            const isSelected = selectedAvailabilityIds.includes(availability.id);
                            return (
                                <button
                                    key={availability.id}
                                    onClick={() => onTimeSlotSelect(availability.id)}
                                    className={`
                                        px-4 py-3 rounded-lg text-sm font-medium transition-all duration-200 border relative
                                        ${isSelected
                                            ? 'bg-[#2C7870] text-white border-[#2C7870]'
                                            : 'bg-[#F8F9FA] text-[#4F4F4F] hover:bg-[#E8F5F4] hover:text-[#2C7870] border-[#E8E8E8] hover:border-[#2C7870]'
                                        }
                                    `}
                                >
                                    {availability.formatted_time}
                                    {isSelected && (
                                        <div className="absolute -top-1 -right-1 w-4 h-4 bg-[#6FCF97] rounded-full flex items-center justify-center">
                                            <svg className="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 8 8">
                                                <path d="M6.564 1.75l-3.59 3.612-1.538-1.55L0 4.247l2.974 2.99L8 2.193z"/>
                                            </svg>
                                        </div>
                                    )}
                                </button>
                            );
                        })}
                    </div>
                ) : (
                    <div className="text-center py-8 text-[#828282]">
                        <Clock className="w-8 h-8 mx-auto mb-2 text-[#BDBDBD]" />
                        <p>No available time slots for this date</p>
                    </div>
                )
            ) : (
                <div className="text-center py-8 text-[#828282]">
                    <Clock className="w-8 h-8 mx-auto mb-2 text-[#BDBDBD]" />
                    <p>Please select a date to see available time slots</p>
                </div>
            )}
        </div>
    );
}
