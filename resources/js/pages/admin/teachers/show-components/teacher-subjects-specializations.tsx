import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Edit, X, Plus } from 'lucide-react';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';

interface TeacherProfile {
  id: number;
  subjects?: any[];
  experience_years?: number;
  teaching_type?: string;
  teaching_mode?: string;
  languages?: string[];
  qualification?: string;
}

interface TeacherAvailability {
  id?: number;
  day_of_week?: number; // 0-6 (Sunday-Saturday)
  day_name: string; // Day name from controller
  time_range: string; // Time range from controller 
  start_time?: string;
  end_time?: string;
  is_active: boolean;
}

interface Props {
  profile: TeacherProfile | null;
  availabilities: TeacherAvailability[];
  teacherId: number;
}

export default function TeacherSubjectsSpecializations({ profile, availabilities, teacherId }: Props) {
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [formData, setFormData] = useState({
    subjects: [] as string[],
    languages: [] as string[],
    teaching_mode: '',
    teaching_type: '',
    experience_years: '',
    qualification: '',
    availability: {
      monday: { enabled: false, from: '', to: '' },
      tuesday: { enabled: false, from: '', to: '' },
      wednesday: { enabled: false, from: '', to: '' },
      thursday: { enabled: false, from: '', to: '' },
      friday: { enabled: false, from: '', to: '' },
      saturday: { enabled: false, from: '', to: '' },
      sunday: { enabled: false, from: '', to: '' },
    }
  });
  const [newSubject, setNewSubject] = useState('');
  const [newLanguage, setNewLanguage] = useState('');
  const subjectsList = Array.isArray(profile?.subjects) ? profile.subjects.map(subject => subject.name || subject.template?.name || 'Unknown Subject').join(', ') : 'No subjects assigned';
  const experience = (() => {
    if (!profile?.experience_years) return 'No experience specified';
    
    const years = profile.experience_years;
    const mode = profile.teaching_mode || '';
    const qualification = profile.qualification || '';
    
    // Build the experience string: "(experience) years in Experience teaching (teaching mode) and (qualification)"
    let experienceText = `${years} years in Experience teaching`;
    
    if (mode) {
      experienceText += ` ${mode}`;
    }
    
    if (qualification) {
      experienceText += ` and ${qualification}`;
    }
    
    return experienceText;
  })();
  const teachingType = profile?.teaching_type || 'Not specified';
  const teachingMode = profile?.teaching_mode || 'Not specified';
  const languagesList = Array.isArray(profile?.languages) ? profile.languages.join(', ') : 'Not specified';

  // Convert 12-hour time to 24-hour format for comparison
  const convertTo24Hour = (time12h: string): string => {
    if (!time12h) return '';
    try {
      const [time, period] = time12h.split(' ');
      const [hours, minutes] = time.split(':');
      let hour24 = parseInt(hours);
      
      if (period === 'PM' && hour24 !== 12) {
        hour24 += 12;
      } else if (period === 'AM' && hour24 === 12) {
        hour24 = 0;
      }
      
      return `${hour24.toString().padStart(2, '0')}:${minutes}`;
    } catch {
      return '';
    }
  };

  // Convert 24-hour time to 12-hour format for display
  const convertTo12Hour = (time24h: string): string => {
    if (!time24h) return '';
    try {
      const [hours, minutes] = time24h.split(':');
      const hour24 = parseInt(hours);
      const period = hour24 >= 12 ? 'PM' : 'AM';
      const hour12 = hour24 === 0 ? 12 : hour24 > 12 ? hour24 - 12 : hour24;
      
      return `${hour12.toString().padStart(2, '0')}:${minutes} ${period}`;
    } catch {
      return '';
    }
  };

  // Validate that end time is after start time
  const isValidTimeRange = (startTime: string, endTime: string): boolean => {
    if (!startTime || !endTime) return true; // Allow empty times
    
    const start24 = convertTo24Hour(startTime);
    const end24 = convertTo24Hour(endTime);
    
    if (!start24 || !end24) return true;
    
    return start24 !== end24; // Ensure times are different
  };

  const addSubject = () => {
    if (newSubject.trim() && !formData.subjects.includes(newSubject.trim())) {
      setFormData(prev => ({
        ...prev,
        subjects: [...prev.subjects, newSubject.trim()]
      }));
      setNewSubject('');
    }
  };

  const removeSubject = (subject: string) => {
    setFormData(prev => ({
      ...prev,
      subjects: prev.subjects.filter(s => s !== subject)
    }));
  };

  const addLanguage = () => {
    if (newLanguage.trim() && !formData.languages.includes(newLanguage.trim())) {
      setFormData(prev => ({
        ...prev,
        languages: [...prev.languages, newLanguage.trim()]
      }));
      setNewLanguage('');
    }
  };

  const removeLanguage = (language: string) => {
    setFormData(prev => ({
      ...prev,
      languages: prev.languages.filter(l => l !== language)
    }));
  };

  const handleAvailabilityChange = (day: string, field: 'enabled' | 'from' | 'to', value: boolean | string) => {
    setFormData(prev => ({
      ...prev,
      availability: {
        ...prev.availability,
        [day]: {
          ...prev.availability[day as keyof typeof prev.availability],
          [field]: value
        }
      }
    }));
  };

  const handleSave = async () => {
    setIsLoading(true);
    try {
      // Prepare form data with file upload support
      const submitData = new FormData();
      
      // Add all form fields
      submitData.append('subjects', JSON.stringify(formData.subjects));
      submitData.append('languages', JSON.stringify(formData.languages));
      submitData.append('teaching_mode', formData.teaching_mode);
      submitData.append('teaching_type', formData.teaching_type);
      submitData.append('experience_years', formData.experience_years);
      submitData.append('qualification', formData.qualification);
      submitData.append('availability', JSON.stringify(formData.availability));
      
      // Add Laravel's _method field for PATCH request
      submitData.append('_method', 'PATCH');

      await router.post(`/admin/teachers/${teacherId}/subjects-specializations`, submitData, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
          toast.success('Subjects & specializations updated successfully');
          setIsEditModalOpen(false);
        },
        onError: (errors) => {
          const errorMessage = Object.values(errors).flat().join(', ');
          toast.error(errorMessage || 'Failed to update subjects & specializations');
        }
      });
    } catch (error) {
      toast.error('Failed to update subjects & specializations');
    } finally {
      setIsLoading(false);
    }
  };

  const openEditModal = () => {
    // Initialize availability from existing data
    const initialAvailability = {
      sunday: { enabled: false, from: '', to: '' },
      monday: { enabled: false, from: '', to: '' },
      tuesday: { enabled: false, from: '', to: '' },
      wednesday: { enabled: false, from: '', to: '' },
      thursday: { enabled: false, from: '', to: '' },
      friday: { enabled: false, from: '', to: '' },
      saturday: { enabled: false, from: '', to: '' },
    };

    // Map existing availabilities to the form structure
    availabilities?.forEach(availability => {
      if (availability.is_active && availability.day_name && availability.time_range) {
        // Convert day name to lowercase for our form structure
        const dayName = availability.day_name.toLowerCase();
        
        // Parse time_range (format: "HH:mm:ss - HH:mm:ss") to display format
        const [startTime, endTime] = availability.time_range.split(' - ') || ['', ''];
        
        const formatTime = (timeStr: string) => {
          if (!timeStr) return '';
          
          try {
            // Handle datetime format like "2025-08-22 18:00:00" - extract time part
            let timeOnly = timeStr;
            if (timeStr.includes(' ')) {
              timeOnly = timeStr.split(' ')[1]; // Get time part from datetime
            }
            
            // Handle HH:mm:ss format - extract hour and minute
            const [hour, minute] = timeOnly.split(':');
            const hourNum = parseInt(hour);
            const minuteNum = parseInt(minute);
            
            // Skip if it's 00:00:00 (midnight/blank time)
            if (hourNum === 0 && minuteNum === 0) return '';
            
            // Convert to 12-hour format with AM/PM
            const ampm = hourNum >= 12 ? 'PM' : 'AM';
            const displayHour = hourNum === 0 ? 12 : hourNum > 12 ? hourNum - 12 : hourNum;
            const formattedHour = displayHour.toString().padStart(2, '0');
            const formattedMinute = minute.padStart(2, '0');
            
            return `${formattedHour}:${formattedMinute} ${ampm}`;
          } catch {
            return '';
          }
        };
        
        if (initialAvailability[dayName as keyof typeof initialAvailability]) {
          const fromTime = formatTime(startTime);
          const toTime = formatTime(endTime);
          
          // Only enable the day if both times are valid (not 00:00:00)
          if (fromTime && toTime) {
            initialAvailability[dayName as keyof typeof initialAvailability] = {
              enabled: true,
              from: fromTime,
              to: toTime
            };
          }
        }
      }
    });

    // Initialize form data with current values
    setFormData({
      subjects: Array.isArray(profile?.subjects) ? profile.subjects.map(s => s.name || s.template?.name || 'Unknown Subject') : [],
      languages: Array.isArray(profile?.languages) ? profile.languages : [],
      teaching_mode: profile?.teaching_mode || '',
      teaching_type: profile?.teaching_type || '',
      experience_years: profile?.experience_years?.toString() || '',
      qualification: profile?.qualification || '',
      availability: initialAvailability
    });
    
    setIsEditModalOpen(true);
  };

  // Format availabilities with intelligent grouping
  const formatAvailabilities = () => {
    if (!availabilities || availabilities.length === 0) {
      return 'No availability set';
    }

    const activeAvailabilities = availabilities.filter(av => av.is_active);
    if (activeAvailabilities.length === 0) {
      return 'No availability set';
    }

    // Convert day names to numbers for sorting
    const dayOrder: Record<string, number> = { 'Sunday': 0, 'Monday': 1, 'Tuesday': 2, 'Wednesday': 3, 'Thursday': 4, 'Friday': 5, 'Saturday': 6 };
    
    // Sort by day order and format times
    const sortedAvailabilities = activeAvailabilities
      .map(av => ({
        ...av,
        dayIndex: dayOrder[av.day_name] || 0,
        formattedTime: formatTimeRange(av.time_range)
      }))
      .sort((a, b) => a.dayIndex - b.dayIndex);

    // Group by time ranges
    const timeGroups = new Map<string, string[]>();
    
    sortedAvailabilities.forEach(av => {
      const timeKey = av.formattedTime;
      if (!timeGroups.has(timeKey)) {
        timeGroups.set(timeKey, []);
      }
      timeGroups.get(timeKey)!.push(av.day_name);
    });

    // Format grouped availabilities
    const formattedGroups: string[] = [];
    
    timeGroups.forEach((days, timeRange) => {
      const dayString = formatDayRange(days);
      formattedGroups.push(`${dayString}: ${timeRange}`);
    });

    return formattedGroups.join('\n');
  };

  // Format time range from database format to display format
  const formatTimeRange = (timeRange: string): string => {
    if (!timeRange) return '';
    
    try {
      const [startTime, endTime] = timeRange.split(' - ');
      
      // Extract time from datetime format and convert to 12-hour
      const formatSingleTime = (timeStr: string): string => {
        let timeOnly = timeStr;
        if (timeStr.includes(' ')) {
          timeOnly = timeStr.split(' ')[1]; // Get time part from datetime
        }
        
        const [hour, minute] = timeOnly.split(':');
        const hourNum = parseInt(hour);
        const ampm = hourNum >= 12 ? 'PM' : 'AM';
        const displayHour = hourNum === 0 ? 12 : hourNum > 12 ? hourNum - 12 : hourNum;
        
        return `${displayHour}:${minute}${ampm}`;
      };

      const start = formatSingleTime(startTime);
      const end = formatSingleTime(endTime);
      
      return `${start} - ${end}`;
    } catch {
      return timeRange;
    }
  };

  // Format day ranges (e.g., ["Monday", "Tuesday", "Wednesday"] becomes "Mon-Wed")
  const formatDayRange = (days: string[]): string => {
    if (!days || days.length === 0) return '';
    
    // Filter out invalid days and ensure they are strings
    const validDays = days.filter(day => day && typeof day === 'string' && day.trim() !== '');
    if (validDays.length === 0) return '';
    
    if (validDays.length === 1) return validDays[0].substring(0, 3); // Mon, Tue, etc.

    // Sort days by order
    const dayOrder: Record<string, number> = { 'Sunday': 0, 'Monday': 1, 'Tuesday': 2, 'Wednesday': 3, 'Thursday': 4, 'Friday': 5, 'Saturday': 6 };
    const sortedDays = [...validDays].sort((a, b) => (dayOrder[a] || 0) - (dayOrder[b] || 0));

    // Check if days are consecutive
    const isConsecutive = (dayList: string[]): boolean => {
      for (let i = 1; i < dayList.length; i++) {
        const prevIndex = dayOrder[dayList[i - 1]] || 0;
        const currentIndex = dayOrder[dayList[i]] || 0;
        if (currentIndex !== prevIndex + 1) {
          return false;
        }
      }
      return true;
    };

    if (sortedDays.length >= 3 && isConsecutive(sortedDays)) {
      // Format as range: Mon-Fri
      return `${sortedDays[0].substring(0, 3)}-${sortedDays[sortedDays.length - 1].substring(0, 3)}`;
    } else {
      // Format as list: Mon, Wed, Fri
      return sortedDays.map(day => day.substring(0, 3)).join(', ');
    }
  };

  return (
    <>
      <Card className="mb-8 shadow-sm">
        <CardContent className="p-6">
          <div className="flex-1">
            <h3 className="text-lg font-bold text-gray-800 mb-4">Subjects & Specializations</h3>
            <div className="space-y-3 text-base">
              <div className="flex">
                <span className="font-medium text-gray-700 w-45">Subjects Taught:</span>
                <span className="text-gray-600">{subjectsList}</span>
              </div>
              <div className="flex">
                <span className="font-medium text-gray-700 w-45">Teaching Experience:</span>
                <span className="text-gray-600">{experience}</span>
              </div>
              <div className="flex">
                <span className="font-medium text-gray-700 w-45">Availability Schedule:</span>
                <div className="text-gray-600">
                  {formatAvailabilities().split('\n').map((line, index) => (
                    <div key={index} className="mb-1">
                      {line.startsWith('-') ? (
                        <span className="text-sm">{line}</span>
                      ) : (
                        <span className="text-sm font-medium">{line}</span>
                      )}
                    </div>
                  ))}
                </div>
              </div>
              <div className="flex">
                <span className="font-medium text-gray-700 w-45">Teaching Type:</span>
                <span className="text-gray-600">{teachingType}</span>
              </div>
              <div className="flex">
                <span className="font-medium text-gray-700 w-45">Teaching Mode:</span>
                <span className="text-gray-600">{teachingMode}</span>
              </div>
              <div className="flex">
                <span className="font-medium text-gray-700 w-45">Languages Spoken:</span>
                <span className="text-gray-600">{languagesList}</span>
              </div>
            </div>
            <div className="flex justify-end mt-4">
              <Button 
                variant="link" 
                className="text-sm p-0 h-auto text-teal-600 hover:text-teal-700 cursor-pointer"
                onClick={openEditModal}
              >
                Edit
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Edit Subjects & Specializations Modal */}
    <Dialog open={isEditModalOpen} onOpenChange={setIsEditModalOpen}>
      <DialogContent className="sm:max-w-[600px] max-h-[80vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="text-xl font-semibold text-gray-900">
            Teacher Subjects & Experience
          </DialogTitle>
          <DialogDescription>
            Update teacher subjects, languages, teaching preferences, and availability schedule.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-6 py-4">
          {/* Subjects Taught */}
          <div className="space-y-3">
            <Label className="text-sm font-medium text-gray-700">Subjects Taught</Label>
            <div className="space-y-2">
              {/* Add new subject input */}
              <div className="flex gap-2">
                <Input
                  placeholder="Enter subject name (e.g., Hifz, Hadith, Tajweed)"
                  value={newSubject}
                  onChange={(e) => setNewSubject(e.target.value)}
                  onKeyPress={(e) => {
                    if (e.key === 'Enter') {
                      e.preventDefault();
                      addSubject();
                    }
                  }}
                  className="flex-1"
                />
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={addSubject}
                  disabled={!newSubject.trim()}
                >
                  <Plus className="h-4 w-4" />
                </Button>
              </div>
              
              {/* Display selected subjects */}
              {formData.subjects.length > 0 && (
                <div className="flex flex-wrap gap-2 mt-2">
                  {formData.subjects.map((subject) => (
                    <span
                      key={subject}
                      className="inline-flex items-center gap-1 px-2 py-1 bg-teal-100 text-teal-700 rounded-md text-sm"
                    >
                      {subject}
                      <button
                        type="button"
                        onClick={() => removeSubject(subject)}
                        className="hover:text-teal-900"
                      >
                        <X className="h-3 w-3" />
                      </button>
                    </span>
                  ))}
                </div>
              )}
            </div>
          </div>

          {/* Languages Spoken */}
          <div className="space-y-3">
            <Label className="text-sm font-medium text-gray-700">Languages Spoken</Label>
            <div className="space-y-2">
              {/* Add new language input */}
              <div className="flex gap-2">
                <Input
                  placeholder="Enter language (e.g., English, Arabic, Urdu)"
                  value={newLanguage}
                  onChange={(e) => setNewLanguage(e.target.value)}
                  onKeyPress={(e) => {
                    if (e.key === 'Enter') {
                      e.preventDefault();
                      addLanguage();
                    }
                  }}
                  className="flex-1"
                />
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={addLanguage}
                  disabled={!newLanguage.trim()}
                >
                  <Plus className="h-4 w-4" />
                </Button>
              </div>
              
              {/* Display selected languages */}
              {formData.languages.length > 0 && (
                <div className="flex flex-wrap gap-2 mt-2">
                  {formData.languages.map((language) => (
                    <span
                      key={language}
                      className="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 rounded-md text-sm"
                    >
                      {language}
                      <button
                        type="button"
                        onClick={() => removeLanguage(language)}
                        className="hover:text-blue-900"
                      >
                        <X className="h-3 w-3" />
                      </button>
                    </span>
                  ))}
                </div>
              )}
            </div>
          </div>

          {/* Teaching Mode */}
          <div className="space-y-3">
            <Label className="text-sm font-medium text-gray-700">Teaching Mode</Label>
            <div className="text-xs text-gray-500 mb-2">Max 6 flexibility for full-time, 3 flexibility for part-time</div>
            <div className="space-y-2">
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="full-time"
                  checked={formData.teaching_mode === 'full-time'}
                  onCheckedChange={(checked) => checked && setFormData(prev => ({ ...prev, teaching_mode: 'full-time' }))}
                />
                <Label htmlFor="full-time" className="text-sm font-normal">Full-Time</Label>
              </div>
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="part-time"
                  checked={formData.teaching_mode === 'part-time'}
                  onCheckedChange={(checked) => checked && setFormData(prev => ({ ...prev, teaching_mode: 'part-time' }))}
                />
                <Label htmlFor="part-time" className="text-sm font-normal">Part-Time</Label>
              </div>
            </div>
          </div>

          {/* Teaching Type */}
          <div className="space-y-3">
            <Label className="text-sm font-medium text-gray-700">Teaching Type</Label>
            <div className="space-y-2">
              {['Online', 'In-person', 'Both'].map((type) => (
                <div key={type} className="flex items-center space-x-2">
                  <Checkbox
                    id={type.toLowerCase()}
                    checked={formData.teaching_type === type.toLowerCase()}
                    onCheckedChange={(checked) => checked && setFormData(prev => ({ ...prev, teaching_type: type.toLowerCase() }))}
                  />
                  <Label htmlFor={type.toLowerCase()} className="text-sm font-normal">
                    {type}
                  </Label>
                </div>
              ))}
            </div>
          </div>

          {/* Years of Experience and Qualification */}
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label className="text-sm font-medium text-gray-700">Years of Experience</Label>
              <Select value={formData.experience_years} onValueChange={(value) => setFormData(prev => ({ ...prev, experience_years: value }))}>
                <SelectTrigger>
                  <SelectValue placeholder="Select one option..." />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="1">1 Year</SelectItem>
                  <SelectItem value="2">2 Years</SelectItem>
                  <SelectItem value="3">3 Years</SelectItem>
                  <SelectItem value="5">5 Years</SelectItem>
                  <SelectItem value="10">10+ Years</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label className="text-sm font-medium text-gray-700">Qualification</Label>
              <Select value={formData.qualification} onValueChange={(value) => setFormData(prev => ({ ...prev, qualification: value }))}>
                <SelectTrigger>
                  <SelectValue placeholder="Select one option..." />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="hifz">Hifz</SelectItem>
                  <SelectItem value="ijazah">Ijazah</SelectItem>
                  <SelectItem value="alim">Alim</SelectItem>
                  <SelectItem value="bachelor">Bachelor's Degree</SelectItem>
                  <SelectItem value="master">Master's Degree</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>



          {/* Preferred Teaching Times */}
          <div className="space-y-3">
            <Label className="text-sm font-medium text-gray-700">Preferred Teaching Times</Label>
            <div className="text-xs text-gray-500 mb-3">A current time zone is essential to coordinate lessons with international students.</div>
            
            {/* Days of the week */}
            {Object.entries(formData.availability).map(([day, schedule]) => (
              <div key={day} className="space-y-2">
                <div className="flex items-center space-x-2">
                  <Checkbox
                    id={day}
                    checked={schedule.enabled}
                    onCheckedChange={(checked) => handleAvailabilityChange(day, 'enabled', checked as boolean)}
                  />
                  <Label htmlFor={day} className="text-sm font-normal capitalize">
                    {day}
                  </Label>
                </div>
                
                {schedule.enabled && (
                  <div className="ml-6 grid grid-cols-2 gap-2">
                    <div>
                      <Label className="text-xs text-gray-500">From</Label>
                      <Input
                        type="time"
                        value={schedule.from ? convertTo24Hour(schedule.from) : ''}
                        onChange={(e) => {
                          const time24 = e.target.value;
                          const time12 = convertTo12Hour(time24);
                          
                          // Validate that the time is different from end time
                          if (schedule.to && time12 === schedule.to) {
                            toast.error('Start time cannot be the same as end time');
                            return;
                          }
                          
                          handleAvailabilityChange(day, 'from', time12);
                        }}
                        className={`h-8 text-xs ${
                          schedule.from && schedule.to && !isValidTimeRange(schedule.from, schedule.to) 
                            ? 'border-red-500' 
                            : ''
                        }`}
                      />
                    </div>
                    <div>
                      <Label className="text-xs text-gray-500">To</Label>
                      <Input
                        type="time"
                        value={schedule.to ? convertTo24Hour(schedule.to) : ''}
                        onChange={(e) => {
                          const time24 = e.target.value;
                          const time12 = convertTo12Hour(time24);
                          
                          // Validate that the time is different from start time
                          if (schedule.from && time12 === schedule.from) {
                            toast.error('End time cannot be the same as start time');
                            return;
                          }
                          
                          handleAvailabilityChange(day, 'to', time12);
                        }}
                        className={`h-8 text-xs ${
                          schedule.from && schedule.to && !isValidTimeRange(schedule.from, schedule.to) 
                            ? 'border-red-500' 
                            : ''
                        }`}
                      />
                      {schedule.from && schedule.to && !isValidTimeRange(schedule.from, schedule.to) && (
                        <p className="text-xs text-red-500 mt-1">End time must be different from start time</p>
                      )}
                    </div>
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>

        <DialogFooter className="flex justify-end space-x-3 pt-6">
          <Button
            variant="outline"
            onClick={() => setIsEditModalOpen(false)}
            disabled={isLoading}
            className="px-6"
          >
            Cancel
          </Button>
          <Button
            onClick={handleSave}
            disabled={isLoading}
            className="px-6 bg-teal-600 hover:bg-teal-700 text-white"
          >
            {isLoading ? 'Saving...' : 'Save and Continue'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
    </>
  );
}
