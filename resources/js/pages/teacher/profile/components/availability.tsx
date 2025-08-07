import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Edit } from 'lucide-react';
import { useForm } from '@inertiajs/react';
import { toast } from 'sonner';
import AvailabilityModal from '../modals/availability-modal';

interface DayOfWeek {
    id: number;
    name: string;
    is_selected: boolean;
}

interface AvailabilityProps {
    available_days: DayOfWeek[];
    preferred_teaching_hours: string;
    available_time: string;
    time_zone: string;
}

const daysOfWeek = [
    { id: 1, name: 'Monday' },
    { id: 2, name: 'Tuesday' },
    { id: 3, name: 'Wednesday' },
    { id: 4, name: 'Thursday' },
    { id: 5, name: 'Friday' },
    { id: 6, name: 'Saturday' },
    { id: 7, name: 'Sunday' },
];

export default function Availability({ 
    available_days, 
    preferred_teaching_hours, 
    available_time, 
    time_zone 
}: AvailabilityProps) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [currentDays, setCurrentDays] = useState(available_days);
    const [currentHours, setCurrentHours] = useState(preferred_teaching_hours);
    const [currentTime, setCurrentTime] = useState(available_time);
    const [currentTimezone, setCurrentTimezone] = useState(time_zone);

    const { data, setData, put, processing } = useForm({
        available_days: available_days.filter(d => d.is_selected).map(d => d.id),
        preferred_teaching_hours: preferred_teaching_hours || '',
        available_time: available_time || '',
        time_zone: time_zone || '',
    });

    const handleSave = (formData: {
        available_days: number[];
        preferred_teaching_hours: string;
        available_time: string;
        time_zone: string;
    }) => {
        setData(formData);
        put(route('teacher.profile.update-availability'), {
            preserveScroll: true,
            onSuccess: () => {
                setIsModalOpen(false);
                // Update the local state immediately for optimistic UI update
                setCurrentDays(daysOfWeek.map(day => ({
                    ...day,
                    is_selected: formData.available_days.includes(day.id)
                })));
                setCurrentHours(formData.preferred_teaching_hours);
                setCurrentTime(formData.available_time);
                setCurrentTimezone(formData.time_zone);
                // Show success toast
                toast.success('Availability updated successfully!', {
                    description: 'Your availability information has been saved.',
                });
            },
            onError: (errors) => {
                // Show error toast
                toast.error('Failed to update availability', {
                    description: Object.values(errors).flat().join(', '),
                });
            },
        });
    };

    return (
        <>
            <div className="bg-white rounded-xl shadow-md border">
                <div className="p-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-6">Availability & Time Zone</h3>
                    
                    {/* Available Days Section */}
                    <div className="mb-8">
                        <label className="text-sm font-medium text-gray-600 mb-4 block">Available Days:</label>
                        <div className="grid grid-cols-5 gap-4">
                            {/* First row - Monday to Friday */}
                            {daysOfWeek.slice(0, 5).map((day) => {
                                const isSelected = currentDays.some(d => d.id === day.id && d.is_selected);
                                return (
                                    <div key={day.id} className="flex items-center space-x-3">
                                        <Checkbox
                                            id={`day-${day.id}`}
                                            checked={isSelected}
                                            disabled
                                            className="data-[state=checked]:bg-green-600 data-[state=checked]:border-green-600"
                                        />
                                        <label
                                            htmlFor={`day-${day.id}`}
                                            className="text-sm font-medium text-gray-900 cursor-pointer"
                                        >
                                            {day.name}
                                        </label>
                                    </div>
                                );
                            })}
                        </div>
                        <div className="grid grid-cols-2 gap-4 mt-4">
                            {/* Second row - Saturday and Sunday */}
                            {daysOfWeek.slice(5, 7).map((day) => {
                                const isSelected = currentDays.some(d => d.id === day.id && d.is_selected);
                                return (
                                    <div key={day.id} className="flex items-center space-x-3">
                                        <Checkbox
                                            id={`day-${day.id}`}
                                            checked={isSelected}
                                            disabled
                                            className="data-[state=checked]:bg-green-600 data-[state=checked]:border-green-600"
                                        />
                                        <label
                                            htmlFor={`day-${day.id}`}
                                            className="text-sm font-medium text-gray-900 cursor-pointer"
                                        >
                                            {day.name}
                                        </label>
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    {/* Availability Details Section */}
                    <div className="space-y-6">
                        {/* Preferred Teaching Hours */}
                        <div>
                            <label className="text-sm font-medium text-gray-600 block mb-2">Preferred Teaching Hours:</label>
                            <p className="text-base font-semibold text-gray-900">
                                {currentHours || 'Not specified'}
                            </p>
                        </div>

                        {/* Available Time */}
                        <div>
                            <label className="text-sm font-medium text-gray-600 block mb-2">Available Time:</label>
                            <p className="text-base font-semibold text-gray-900">
                                {currentTime || 'Not specified'}
                            </p>
                        </div>

                        {/* Time Zone */}
                        <div>
                            <label className="text-sm font-medium text-gray-600 block mb-2">Time Zone:</label>
                            <p className="text-base font-semibold text-gray-900">
                                {currentTimezone || 'Not specified'}
                            </p>
                        </div>
                    </div>

                    {/* Edit Button */}
                    <div className="flex justify-end mt-6 pt-4 border-t border-gray-100">
                        <Button
                            variant="outline"
                            size="sm"
                            className="text-green-600 border-green-600 hover:bg-green-50"
                            onClick={() => setIsModalOpen(true)}
                        >
                            <Edit className="h-4 w-4 mr-2" />
                            Edit
                        </Button>
                    </div>
                </div>
            </div>

            {/* Availability Modal */}
            <AvailabilityModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                available_days={available_days}
                preferred_teaching_hours={preferred_teaching_hours}
                available_time={available_time}
                time_zone={time_zone}
                onSave={handleSave}
                processing={processing}
            />
        </>
    );
}
