import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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
import { Edit, X } from 'lucide-react';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';

interface StudentProfile {
    subjects_of_interest?: string[] | null;
    preferred_learning_times?: string[] | null;
    learning_goals?: string | null;
    teaching_mode?: string | null;
    additional_notes?: string | null;
    age_group?: string | null;
}

interface Props {
    profile: StudentProfile | null;
    studentId: number;
    options?: {
        subjects: string[];
        ageGroups: string[];
        timeSlots: string[];
    };
}

export default function StudentLearningPreferences({ profile, studentId, options }: Props) {

    const [isEditModalOpen, setIsEditModalOpen] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [formData, setFormData] = useState({
        subjects_of_interest: [] as string[],
        teaching_mode: '',
        student_age_group: '',
        preferred_learning_times: {
            monday: { enabled: false, from: '', to: '' },
            tuesday: { enabled: false, from: '', to: '' },
            wednesday: { enabled: false, from: '', to: '' },
            thursday: { enabled: false, from: '', to: '' },
            friday: { enabled: false, from: '', to: '' },
            saturday: { enabled: false, from: '', to: '' },
            sunday: { enabled: false, from: '', to: '' },
        },
        additional_notes: ''
    });

    const availableSubjects = options?.subjects || [];
    const ageGroups = options?.ageGroups || [];
    const timeSlots = options?.timeSlots || [];

    const handleSubjectChange = (subject: string, checked: boolean) => {
        setFormData(prev => ({
            ...prev,
            subjects_of_interest: checked
                ? [...prev.subjects_of_interest, subject]
                : prev.subjects_of_interest.filter(s => s !== subject)
        }));
    };

    const handleTeachingModeChange = (mode: string, checked: boolean) => {
        if (checked) {
            setFormData(prev => ({ ...prev, teaching_mode: mode }));
        }
    };

    const handleAgeGroupChange = (ageGroup: string) => {
        setFormData(prev => ({ ...prev, student_age_group: ageGroup }));
    };

    const handleTimeChange = (day: string, field: 'enabled' | 'from' | 'to', value: boolean | string) => {
        setFormData(prev => ({
            ...prev,
            preferred_learning_times: {
                ...prev.preferred_learning_times,
                [day]: {
                    ...prev.preferred_learning_times[day as keyof typeof prev.preferred_learning_times],
                    [field]: value
                }
            }
        }));
    };

    const handleSave = async () => {
        setIsLoading(true);
        try {
            // Prepare data for submission
            const submitData = {
                subjects_of_interest: formData.subjects_of_interest,
                teaching_mode: formData.teaching_mode,
                student_age_group: formData.student_age_group,
                preferred_learning_times: formData.preferred_learning_times,
                additional_notes: formData.additional_notes,
            };

            await router.put(`/admin/students/${studentId}/learning-preferences`, submitData, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Learning preferences updated successfully');
                    setIsEditModalOpen(false);
                },
                onError: (errors) => {
                    const errorMessage = Object.values(errors).flat().join(', ');
                    toast.error(errorMessage || 'Failed to update learning preferences');
                }
            });
        } catch (error) {
            toast.error('Failed to update learning preferences');
        } finally {
            setIsLoading(false);
        }
    };

    const openEditModal = () => {
        // Initialize form data with current values
        setFormData({
            subjects_of_interest: Array.isArray(profile?.subjects_of_interest) ? profile.subjects_of_interest : [],
            teaching_mode: profile?.teaching_mode || '',
            student_age_group: profile?.age_group || '',
            preferred_learning_times: {
                monday: { enabled: false, from: '', to: '' },
                tuesday: { enabled: false, from: '', to: '' },
                wednesday: { enabled: false, from: '', to: '' },
                thursday: { enabled: false, from: '', to: '' },
                friday: { enabled: false, from: '', to: '' },
                saturday: { enabled: false, from: '', to: '' },
                sunday: { enabled: false, from: '', to: '' },
            },
            additional_notes: profile?.additional_notes || ''
        });

        // Initialize preferred learning times from profile data
        if (profile?.preferred_learning_times && Array.isArray(profile.preferred_learning_times)) {
            // Parse the preferred learning times from the profile
            // This assumes the data is stored in a specific format
            // You may need to adjust this based on your actual data structure
        }

        setIsEditModalOpen(true);
    };

    const getSubjectsDisplay = () => {
        if (!profile?.subjects_of_interest || !Array.isArray(profile.subjects_of_interest) || profile.subjects_of_interest.length === 0) {
            return 'No subjects selected';
        }
        return profile.subjects_of_interest.join(', ');
    };

    const getTeachingModeDisplay = () => {
        if (!profile?.teaching_mode) {
            return 'Not specified';
        }
        return profile.teaching_mode === 'full-time' ? 'Full-Time' : 'Part-Time';
    };

    const getLearningTimesDisplay = () => {
        if (!profile?.preferred_learning_times || !Array.isArray(profile.preferred_learning_times) || profile.preferred_learning_times.length === 0) {
            return 'No preferred times set';
        }
        return 'Custom schedule configured';
    };

    // Guard clause for when profile is not available
    if (!profile) {
        return (
            <Card className="mb-8 shadow-sm">
                <CardContent className="p-6">
                    <div className="text-center text-gray-500">
                        No learning preferences data available
                    </div>
                </CardContent>
            </Card>
        );
    }

    // Guard clause for when required options are not available
    if (!options?.subjects || !options?.ageGroups || !options?.timeSlots) {
        return (
            <Card className="mb-8 shadow-sm">
                <CardContent className="p-6">
                    <div className="text-center text-gray-500">
                        Learning preferences configuration not available
                    </div>
                </CardContent>
            </Card>
        );
    }

    return (
        <>
            <Card className="mb-8 shadow-sm">
                <CardContent className="p-6">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-lg font-bold text-gray-800">Learning Preferences</h3>
                        <Button 
                            variant="link" 
                            className="text-sm p-0 h-auto text-teal-600 hover:text-teal-700 cursor-pointer"
                            onClick={openEditModal}
                        >
                            Edit
                        </Button>
                    </div>
                    
                    <div className="space-y-4">
                        <div className="flex">
                            <span className="font-medium text-gray-700 w-48">Preferred Subjects:</span>
                            <span className="text-gray-600">{getSubjectsDisplay()}</span>
                        </div>
                        <div className="flex">
                            <span className="font-medium text-gray-700 w-48">Teaching Mode:</span>
                            <span className="text-gray-600">{getTeachingModeDisplay()}</span>
                        </div>
                        <div className="flex">
                            <span className="font-medium text-gray-700 w-48">Preferred Learning Times:</span>
                            <span className="text-gray-600">{getLearningTimesDisplay()}</span>
                        </div>
                        <div className="flex">
                            <span className="font-medium text-gray-700 w-48">Additional Notes:</span>
                            <span className="text-gray-600">
                                {profile?.additional_notes || 'No additional notes provided'}
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Learning Preferences Modal */}
            <Dialog open={isEditModalOpen} onOpenChange={setIsEditModalOpen}>
                <DialogContent className="sm:max-w-[600px] max-h-[80vh] overflow-y-auto">
                    <DialogHeader>
                        <div className="flex items-center justify-between">
                            <DialogTitle className="text-xl font-bold text-gray-800">
                                Learning Preferences
                            </DialogTitle>
                            <button
                                onClick={() => setIsEditModalOpen(false)}
                                className="text-gray-500 hover:text-gray-700"
                            >
                                <X className="h-5 w-5" />
                            </button>
                        </div>
                        <DialogDescription className="text-gray-600">
                            Update the student's learning preferences and schedule.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-6 py-4">
                        {/* Preferred Subjects */}
                        <div className="space-y-3">
                            <Label className="text-sm font-medium text-gray-700">Preferred Subjects</Label>
                            <div className="grid grid-cols-2 gap-3">
                                {availableSubjects.map((subject) => (
                                    <div key={subject} className="flex items-center space-x-2">
                                        <Checkbox
                                            id={subject.toLowerCase()}
                                            checked={formData.subjects_of_interest.includes(subject)}
                                            onCheckedChange={(checked) => 
                                                handleSubjectChange(subject, checked as boolean)
                                            }
                                        />
                                        <Label htmlFor={subject.toLowerCase()} className="text-sm font-normal">
                                            {subject}
                                        </Label>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Teaching Mode */}
                        <div className="space-y-3">
                            <Label className="text-sm font-medium text-gray-700">Teaching Mode</Label>
                            <div className="text-xs text-gray-500 mb-2">
                                Max 6 hrs/day for full-time, 3 hrs/day for part-time
                            </div>
                            <div className="space-y-2">
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="full-time"
                                        checked={formData.teaching_mode === 'full-time'}
                                        onCheckedChange={(checked) => 
                                            handleTeachingModeChange('full-time', checked as boolean)
                                        }
                                    />
                                    <Label htmlFor="full-time" className="text-sm font-normal">Full-Time</Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="part-time"
                                        checked={formData.teaching_mode === 'part-time'}
                                        onCheckedChange={(checked) => 
                                            handleTeachingModeChange('part-time', checked as boolean)
                                        }
                                    />
                                    <Label htmlFor="part-time" className="text-sm font-normal">Part-Time</Label>
                                </div>
                            </div>
                        </div>

                        {/* Student Age Group */}
                        <div className="space-y-3">
                            <Label className="text-sm font-medium text-gray-700">Student Age Group</Label>
                            <Select value={formData.student_age_group} onValueChange={handleAgeGroupChange}>
                                <SelectTrigger className="bg-gray-50 border-gray-200">
                                    <SelectValue placeholder="Select age group" />
                                </SelectTrigger>
                                <SelectContent>
                                    {ageGroups.map((ageGroup) => (
                                        <SelectItem key={ageGroup} value={ageGroup}>
                                            {ageGroup}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        {/* Preferred Learning Times */}
                        <div className="space-y-3">
                            <Label className="text-sm font-medium text-gray-700">Preferred Learning Times</Label>
                            <div className="text-xs text-gray-500 mb-3">
                                A correct time zone is essential to coordinate lessons with international students.
                            </div>
                            
                            {Object.entries(formData.preferred_learning_times).map(([day, schedule]) => (
                                <div key={day} className="space-y-2">
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id={day}
                                            checked={schedule.enabled}
                                            onCheckedChange={(checked) => 
                                                handleTimeChange(day, 'enabled', checked as boolean)
                                            }
                                        />
                                        <Label htmlFor={day} className="text-sm font-normal capitalize">
                                            {day}
                                        </Label>
                                    </div>
                                    
                                    {schedule.enabled && (
                                        <div className="ml-6 grid grid-cols-2 gap-2">
                                            <div>
                                                <Label className="text-xs text-gray-500">From</Label>
                                                <Select 
                                                    value={schedule.from} 
                                                    onValueChange={(value) => handleTimeChange(day, 'from', value)}
                                                >
                                                    <SelectTrigger className="h-8 text-xs">
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
                                            <div>
                                                <Label className="text-xs text-gray-500">To</Label>
                                                <Select 
                                                    value={schedule.to} 
                                                    onValueChange={(value) => handleTimeChange(day, 'to', value)}
                                                >
                                                    <SelectTrigger className="h-8 text-xs">
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

                        {/* Additional Notes */}
                        <div className="space-y-3">
                            <Label className="text-sm font-medium text-gray-700">Additional Notes for Teacher</Label>
                            <Textarea
                                value={formData.additional_notes}
                                onChange={(e) => setFormData(prev => ({ ...prev, additional_notes: e.target.value }))}
                                placeholder="Enter any additional information for teachers..."
                                className="min-h-[100px] bg-gray-50 border-gray-200"
                            />
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
