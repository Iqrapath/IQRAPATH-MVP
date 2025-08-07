import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { X, Clock, Calendar, Globe } from 'lucide-react';

interface DayOfWeek {
    id: number;
    name: string;
    is_selected: boolean;
}

interface AvailabilityModalProps {
    isOpen: boolean;
    onClose: () => void;
    available_days: DayOfWeek[];
    preferred_teaching_hours: string;
    available_time: string;
    time_zone: string;
    onSave: (data: {
        available_days: number[];
        preferred_teaching_hours: string;
        available_time: string;
        time_zone: string;
    }) => void;
    processing?: boolean;
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

const timeZones = [
    'GMT+0 (UTC)',
    'GMT+1 (Nigeria)',
    'GMT+2 (Egypt)',
    'GMT+3 (Saudi Arabia)',
    'GMT+4 (UAE)',
    'GMT+5 (Pakistan)',
    'GMT+6 (Bangladesh)',
    'GMT+7 (Thailand)',
    'GMT+8 (Malaysia)',
    'GMT+9 (Japan)',
    'GMT+10 (Australia)',
    'GMT-5 (EST)',
    'GMT-6 (CST)',
    'GMT-7 (MST)',
    'GMT-8 (PST)',
];

const availabilityTypes = [
    'Part-Time',
    'Full-Time',
];

// Helper function to parse time range string
const parseTimeRange = (timeRange: string) => {
    if (!timeRange) return { from: '09:00', to: '17:00' };
    
    const match = timeRange.match(/(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})/);
    if (match) {
        return { from: match[1], to: match[2] };
    }
    
    return { from: '09:00', to: '17:00' };
};

// Helper function to format time range
const formatTimeRange = (from: string, to: string) => {
    return `${from} - ${to}`;
};

export default function AvailabilityModal({ 
    isOpen, 
    onClose, 
    available_days, 
    preferred_teaching_hours, 
    available_time, 
    time_zone,
    onSave, 
    processing = false 
}: AvailabilityModalProps) {
    const parsedTime = parseTimeRange(preferred_teaching_hours);
    
    const [formData, setFormData] = useState({
        available_days: available_days.filter(d => d.is_selected).map(d => d.id),
        preferred_teaching_hours: preferred_teaching_hours || '',
        available_time: available_time || '',
        time_zone: time_zone || '',
    });

    const [timeFrom, setTimeFrom] = useState(parsedTime.from);
    const [timeTo, setTimeTo] = useState(parsedTime.to);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const timeRange = formatTimeRange(timeFrom, timeTo);
        onSave({
            ...formData,
            preferred_teaching_hours: timeRange,
        });
    };

    const handleDayChange = (dayId: number, checked: boolean) => {
        setFormData(prev => ({
            ...prev,
            available_days: checked 
                ? [...prev.available_days, dayId]
                : prev.available_days.filter(id => id !== dayId)
        }));
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black/20 backdrop-blur-sm flex items-center justify-center z-50">
            <div className="bg-white rounded-xl p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                {/* Header */}
                <div className="flex justify-between items-start mb-6">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-900">Availability & Time Zone</h2>
                        <p className="text-sm text-gray-600 mt-1">Set your availability and time zone preferences</p>
                    </div>
                    <button
                        onClick={onClose}
                        className="text-gray-500 hover:text-gray-700"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Available Days */}
                    <div>
                        <Label className="text-sm font-medium text-gray-700 mb-4 block">
                            Available Days
                        </Label>
                        <div className="grid grid-cols-5 gap-4 mb-4">
                            {/* First row - Monday to Friday */}
                            {daysOfWeek.slice(0, 5).map((day) => (
                                <div key={day.id} className="flex items-center space-x-3">
                                    <Checkbox
                                        id={`modal-day-${day.id}`}
                                        checked={formData.available_days.includes(day.id)}
                                        onCheckedChange={(checked) => 
                                            handleDayChange(day.id, checked as boolean)
                                        }
                                        className="data-[state=checked]:bg-green-600 data-[state=checked]:border-green-600"
                                    />
                                    <label
                                        htmlFor={`modal-day-${day.id}`}
                                        className="text-sm font-medium text-gray-900 cursor-pointer"
                                    >
                                        {day.name}
                                    </label>
                                </div>
                            ))}
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            {/* Second row - Saturday and Sunday */}
                            {daysOfWeek.slice(5, 7).map((day) => (
                                <div key={day.id} className="flex items-center space-x-3">
                                    <Checkbox
                                        id={`modal-day-${day.id}`}
                                        checked={formData.available_days.includes(day.id)}
                                        onCheckedChange={(checked) => 
                                            handleDayChange(day.id, checked as boolean)
                                        }
                                        className="data-[state=checked]:bg-green-600 data-[state=checked]:border-green-600"
                                    />
                                    <label
                                        htmlFor={`modal-day-${day.id}`}
                                        className="text-sm font-medium text-gray-900 cursor-pointer"
                                    >
                                        {day.name}
                                    </label>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Preferred Teaching Hours */}
                    <div>
                        <Label className="text-sm font-medium text-gray-700 mb-3 block">
                            Preferred Teaching Hours
                        </Label>
                        <div className="flex items-center gap-4">
                            <div className="flex-1">
                                <Label htmlFor="time-from" className="text-xs text-gray-600 block mb-1">
                                    From
                                </Label>
                                <div className="relative">
                                    <Clock className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-500" />
                                    <Input
                                        id="time-from"
                                        type="time"
                                        value={timeFrom}
                                        onChange={(e) => setTimeFrom(e.target.value)}
                                        className="pl-10 bg-gray-50 border-gray-200 rounded-lg"
                                    />
                                </div>
                            </div>
                            <div className="flex items-center">
                                <span className="text-gray-500 text-sm">to</span>
                            </div>
                            <div className="flex-1">
                                <Label htmlFor="time-to" className="text-xs text-gray-600 block mb-1">
                                    To
                                </Label>
                                <div className="relative">
                                    <Clock className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-500" />
                                    <Input
                                        id="time-to"
                                        type="time"
                                        value={timeTo}
                                        onChange={(e) => setTimeTo(e.target.value)}
                                        className="pl-10 bg-gray-50 border-gray-200 rounded-lg"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Available Time */}
                    <div>
                        <Label htmlFor="available_time" className="text-sm font-medium text-gray-700">
                            Available Time
                        </Label>
                        <div className="relative mt-1">
                            <Calendar className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-500" />
                            <Select
                                value={formData.available_time}
                                onValueChange={(value) => setFormData({ ...formData, available_time: value })}
                            >
                                <SelectTrigger className="pl-10 bg-gray-50 border-gray-200 rounded-lg">
                                    <SelectValue placeholder="Select availability type" />
                                </SelectTrigger>
                                <SelectContent>
                                    {availabilityTypes.map((type) => (
                                        <SelectItem key={type} value={type}>
                                            {type}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    {/* Time Zone */}
                    <div>
                        <Label htmlFor="time_zone" className="text-sm font-medium text-gray-700">
                            Time Zone
                        </Label>
                        <div className="relative mt-1">
                            <Globe className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-500" />
                            <Select
                                value={formData.time_zone}
                                onValueChange={(value) => setFormData({ ...formData, time_zone: value })}
                            >
                                <SelectTrigger className="pl-10 bg-gray-50 border-gray-200 rounded-lg">
                                    <SelectValue placeholder="Select time zone" />
                                </SelectTrigger>
                                <SelectContent>
                                    {timeZones.map((tz) => (
                                        <SelectItem key={tz} value={tz}>
                                            {tz}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    {/* Save Button */}
                    <div className="flex justify-end pt-4">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-green-600 text-white hover:bg-green-700 rounded-lg px-6 py-2"
                        >
                            {processing ? 'Saving...' : 'Save and Continue'}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    );
}
