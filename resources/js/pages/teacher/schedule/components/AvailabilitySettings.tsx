import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { usePage } from '@inertiajs/react';
import { toast } from 'sonner';
import axios from 'axios';

interface DayAvailability {
    day: string;
    enabled: boolean;
    fromTime: string;
    toTime: string;
}

interface AvailabilityData {
    holiday_mode: boolean;
    available_days: string[];
    day_schedules: DayAvailability[];
}

export default function AvailabilitySettings() {
    const pageProps = usePage().props as any;
    const user = pageProps.auth?.user || pageProps.user;

    // Set up axios with CSRF token
    useEffect(() => {
        const getCookie = (name: string) => {
            const match = document.cookie.match(new RegExp('(^|; )' + name.replace(/([.$?*|{}()\[\]\\\/\+^])/g, '\\$1') + '=([^;]*)'));
            return match ? decodeURIComponent(match[2]) : undefined;
        };

        const setupAxios = async () => {
            // Try meta first
            let csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            // Fallback to cookie
            if (!csrf) csrf = getCookie('XSRF-TOKEN') || '';

            // If still missing, request Sanctum CSRF cookie then read again
            if (!csrf) {
                await fetch('/sanctum/csrf-cookie', { method: 'GET', credentials: 'same-origin' });
                csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || getCookie('XSRF-TOKEN') || '';
            }

            if (csrf) {
                axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf;
                axios.defaults.headers.common['X-XSRF-TOKEN'] = csrf;
                axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
                axios.defaults.headers.common['Accept'] = 'application/json';
            }
        };

        setupAxios();
    }, []);
    const [holidayMode, setHolidayMode] = useState(false);
    const [selectedDays, setSelectedDays] = useState<string[]>(['Mon', 'Wed', 'Thu']);
    const [dayAvailabilities, setDayAvailabilities] = useState<DayAvailability[]>([
        { day: 'Monday', enabled: true, fromTime: '', toTime: '' },
        { day: 'Tuesday', enabled: false, fromTime: '', toTime: '' },
        { day: 'Wednesday', enabled: true, fromTime: '', toTime: '' },
        { day: 'Thursday', enabled: true, fromTime: '', toTime: '' },
        { day: 'Friday', enabled: false, fromTime: '', toTime: '' },
        { day: 'Saturday', enabled: false, fromTime: '', toTime: '' },
        { day: 'Sunday', enabled: false, fromTime: '', toTime: '' }
    ]);
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);

    // Check if user exists
    if (!user || !user.id) {
        return (
            <Card>
                <CardContent className="p-6">
                    <div className="text-center text-gray-500">
                        <p>Unable to load user information. Please refresh the page.</p>
                    </div>
                </CardContent>
            </Card>
        );
    }

    const daysOfWeek = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const timeSlots = [
        '12:00 AM', '1:00 AM', '2:00 AM', '3:00 AM', '4:00 AM', '5:00 AM',
        '6:00 AM', '7:00 AM', '8:00 AM', '9:00 AM', '10:00 AM', '11:00 AM',
        '12:00 PM', '1:00 PM', '2:00 PM', '3:00 PM', '4:00 PM', '5:00 PM',
        '6:00 PM', '7:00 PM', '8:00 PM', '9:00 PM', '10:00 PM', '11:00 PM'
    ];

    // Fetch availability data from database
    useEffect(() => {
        if (!user?.id) return;

        const fetchAvailability = async () => {
            setLoading(true);
            try {
                const response = await axios.get(`/teacher/availability/${user.id}`, {
                    withCredentials: true
                });
                const data: AvailabilityData = response.data;
                
                setHolidayMode(data.holiday_mode || false);

                
                // Ensure available_days is always an array
                const availableDays = Array.isArray(data.available_days) 
                    ? data.available_days 
                    : ['Mon', 'Wed', 'Thu'];
                setSelectedDays(availableDays);
                
                // Default day schedules with empty time values
                const defaultDaySchedules = [
                    { day: 'Monday', enabled: true, fromTime: '', toTime: '' },
                    { day: 'Tuesday', enabled: false, fromTime: '', toTime: '' },
                    { day: 'Wednesday', enabled: true, fromTime: '', toTime: '' },
                    { day: 'Thursday', enabled: true, fromTime: '', toTime: '' },
                    { day: 'Friday', enabled: false, fromTime: '', toTime: '' },
                    { day: 'Saturday', enabled: false, fromTime: '', toTime: '' },
                    { day: 'Sunday', enabled: false, fromTime: '', toTime: '' }
                ];
                
                const finalDaySchedules = data.day_schedules || defaultDaySchedules;
                setDayAvailabilities(finalDaySchedules);
            } catch (error: any) {
                console.error('Error fetching availability:', error);
                // Keep default values if fetch fails
            } finally {
                setLoading(false);
            }
        };

        fetchAvailability();
    }, [user?.id]);

    const handleDayToggle = (day: string) => {
        const dayName = day === 'Mon' ? 'Monday' : 
                       day === 'Tue' ? 'Tuesday' : 
                       day === 'Wed' ? 'Wednesday' : 
                       day === 'Thu' ? 'Thursday' : 
                       day === 'Fri' ? 'Friday' : 
                       day === 'Sat' ? 'Saturday' : 
                       day === 'Sun' ? 'Sunday' : day;

        if (selectedDays.includes(day)) {
            // Remove from selected days
            setSelectedDays(selectedDays.filter(d => d !== day));
            // Also disable in day availabilities
            setDayAvailabilities(prev => 
                prev.map(dayAvail => 
                    dayAvail.day === dayName 
                        ? { ...dayAvail, enabled: false, fromTime: '', toTime: '' }
                        : dayAvail
                )
            );
        } else {
            // Add to selected days
            setSelectedDays([...selectedDays, day]);
            // Also enable in day availabilities
            setDayAvailabilities(prev => 
                prev.map(dayAvail => 
                    dayAvail.day === dayName 
                        ? { ...dayAvail, enabled: true }
                        : dayAvail
                )
            );
        }
    };

    const handleDayAvailabilityToggle = (day: string) => {
        const dayAbbrev = day === 'Monday' ? 'Mon' : 
                         day === 'Tuesday' ? 'Tue' : 
                         day === 'Wednesday' ? 'Wed' : 
                         day === 'Thursday' ? 'Thu' : 
                         day === 'Friday' ? 'Fri' : 
                         day === 'Saturday' ? 'Sat' : 
                         day === 'Sunday' ? 'Sun' : day;

        setDayAvailabilities(prev => {
            const updated = prev.map(dayAvail => 
                dayAvail.day === day 
                    ? { ...dayAvail, enabled: !dayAvail.enabled }
                    : dayAvail
            );
            
            // Find the updated day to check its new enabled state
            const updatedDay = updated.find(dayAvail => dayAvail.day === day);
            
            // Synchronize with selectedDays
            if (updatedDay) {
                if (updatedDay.enabled) {
                    // Add to selected days if not already there
                    if (!selectedDays.includes(dayAbbrev)) {
                        setSelectedDays([...selectedDays, dayAbbrev]);
                    }
                } else {
                    // Remove from selected days and clear times
                    setSelectedDays(selectedDays.filter(d => d !== dayAbbrev));
                    // Clear times for this day
                    return updated.map(dayAvail => 
                        dayAvail.day === day 
                            ? { ...dayAvail, fromTime: '', toTime: '' }
                            : dayAvail
                    );
                }
            }
            
            return updated;
        });
    };

    const handleTimeChange = (day: string, type: 'fromTime' | 'toTime', value: string) => {
        setDayAvailabilities(prev => 
            prev.map(dayAvail => 
                dayAvail.day === day 
                    ? { ...dayAvail, [type]: value }
                    : dayAvail
            )
        );
    };

    const handleSaveChanges = async () => {
        if (!user?.id) {
            toast.error('Unable to save availability settings');
            return;
        }

        setSaving(true);
        try {
            const availabilityData = {
                holiday_mode: holidayMode,
                available_days: selectedDays,
                day_schedules: dayAvailabilities
            };

            const response = await axios.post(`/teacher/availability/${user.id}`, availabilityData, {
                withCredentials: true
            });
            
            // Show success toast notification
            toast.success('Availability settings saved successfully!', {
                description: `Your teaching schedule has been updated for ${selectedDays.length} day(s)`,
                duration: 4000,
            });

            // Create a database notification for the user
            try {
                await axios.post('/api/notifications', {
                    title: 'Availability Settings Updated',
                    message: `Your teaching availability has been updated. You're now available on ${selectedDays.join(', ')}${holidayMode ? ' (Holiday mode enabled)' : ''}.`,
                    type: 'AvailabilityUpdatedNotification',
                    level: 'success',
                    action_text: 'View Schedule',
                    action_url: '/teacher/schedule',
                    recipient_id: user.id
                });
                
                // Trigger a manual refresh of notifications after a short delay
                // This ensures the notification appears even if WebSockets are not working
                setTimeout(() => {
                    // Dispatch a custom event to refresh notifications
                    window.dispatchEvent(new CustomEvent('refresh-notifications'));
                }, 1000);
                
            } catch (notificationError) {
                // Don't show error to user for notification failure
            }
            
        } catch (error: any) {
            
            // Show error toast notification
            const errorMessage = error.response?.data?.message || 'Failed to save availability settings';
            toast.error('Failed to save availability settings', {
                description: errorMessage,
                duration: 5000,
            });
        } finally {
            setSaving(false);
        }
    };

    return (
        <Card className="bg-transparent border-none shadow-none">
            <CardHeader>
                <CardTitle className="text-lg text-gray-900">Manage your teaching availability</CardTitle>
            </CardHeader>
            <CardContent className="space-y-6">
                {/* Holiday Mode */}
                <div className="flex items-center">
                    <div>
                        <label className="text-sm font-medium text-gray-900 pr-6">Holiday Mode</label>
                    </div>
                    <Switch
                        checked={holidayMode}
                        onCheckedChange={setHolidayMode}
                    />
                </div>

                {/* Select Available Days */}
                <div className="space-y-3">
                    <div>
                        <h3 className="text-sm font-medium text-gray-900">Select Available Days</h3>
                        <p className="text-xs text-gray-500">Select Days you will be available for student</p>
                    </div>
                    <div className="flex space-x-2">
                        {daysOfWeek.map((day) => (
                            <Button
                                key={day}
                                variant={selectedDays.includes(day) ? "default" : "outline"}
                                onClick={() => handleDayToggle(day)}
                                className={`px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 ${
                                    selectedDays.includes(day)
                                        ? 'bg-[#338078] text-white border-[#338078] hover:bg-[#338078]/80'
                                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                }`}
                            >
                                {day}
                            </Button>
                        ))}
                    </div>
                </div>

                {/* Select Available Hours */}
                <div className="space-y-4">
                    <div>
                        <h3 className="text-sm font-medium text-gray-900">Select Available Hours</h3>
                        <p className="text-xs text-gray-500">Set which hours you want to be active</p>
                    </div>
                    
                    <div className="space-y-4">
                        {dayAvailabilities.map((dayAvail) => (
                            <div key={dayAvail.day} className="space-y-3">
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id={dayAvail.day}
                                        checked={dayAvail.enabled}
                                        onCheckedChange={() => handleDayAvailabilityToggle(dayAvail.day)}
                                        className="data-[state=checked]:bg-[#338078] data-[state=checked]:border-[#338078]"
                                    />
                                    <label 
                                        htmlFor={dayAvail.day}
                                        className="text-sm font-medium text-gray-900"
                                    >
                                        {dayAvail.day}
                                    </label>
                                </div>
                                
                                {dayAvail.enabled && (
                                    <div className="flex space-x-4 ml-6">
                                        <div className="flex-1">
                                            <label className="block text-xs text-gray-500 mb-1">From</label>
                                            <Select
                                                value={dayAvail.fromTime}
                                                onValueChange={(value) => handleTimeChange(dayAvail.day, 'fromTime', value)}
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Select one option..." />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {timeSlots.map((time) => (
                                                        <SelectItem key={time} value={time}>
                                                            {time}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="flex-1">
                                            <label className="block text-xs text-gray-500 mb-1">To</label>
                                            <Select
                                                value={dayAvail.toTime}
                                                onValueChange={(value) => handleTimeChange(dayAvail.day, 'toTime', value)}
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Select one option..." />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {timeSlots.map((time) => (
                                                        <SelectItem key={time} value={time}>
                                                            {time}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                </div>

                {/* Save Changes Button */}
                <div className="flex justify-start pt-4">
                    <Button 
                        onClick={handleSaveChanges}
                        disabled={saving || loading}
                        className="bg-[#338078] hover:bg-[#338078]/80 text-white px-6 py-2 rounded-full disabled:opacity-50"
                    >
                        {saving ? 'Saving...' : 'Save Changes'}
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}
