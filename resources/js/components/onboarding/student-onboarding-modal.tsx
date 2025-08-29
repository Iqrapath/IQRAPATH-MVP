import { useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { type User, type StudentProfile } from '@/types';
import { X, BookOpen, Target, Clock, Heart } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';

interface StudentOnboardingModalProps {
    isOpen: boolean;
    onClose: () => void;
    user: User;
    studentProfile?: StudentProfile;
    availableSubjects: string[];
}

type DaySchedule = {
    enabled: boolean;
    from: string;
    to: string;
};

type StudentOnboardingForm = {
    preferred_subjects: string[];
    preferred_learning_times: {
        monday: DaySchedule;
        tuesday: DaySchedule;
        wednesday: DaySchedule;
        thursday: DaySchedule;
        friday: DaySchedule;
        saturday: DaySchedule;
        sunday: DaySchedule;
    };
    current_level: string;
    learning_goals: string;
};

export default function StudentOnboardingModal({ isOpen, onClose, user, studentProfile, availableSubjects }: StudentOnboardingModalProps) {
    const { data, setData, post, processing, errors } = useForm<StudentOnboardingForm>({
        preferred_subjects: (studentProfile?.subjects_of_interest as string[]) || [],
        preferred_learning_times: {
            monday: { enabled: false, from: '', to: '' },
            tuesday: { enabled: false, from: '', to: '' },
            wednesday: { enabled: false, from: '', to: '' },
            thursday: { enabled: false, from: '', to: '' },
            friday: { enabled: false, from: '', to: '' },
            saturday: { enabled: false, from: '', to: '' },
            sunday: { enabled: false, from: '', to: '' },
        },
        current_level: studentProfile?.grade_level || '',
        learning_goals: studentProfile?.learning_goals || '',
    });

    const handleSubjectChange = (subject: string, checked: boolean) => {
        if (checked) {
            setData('preferred_subjects', [...data.preferred_subjects, subject]);
        } else {
            setData('preferred_subjects', data.preferred_subjects.filter(s => s !== subject));
        }
    };

    const handleDayToggle = (day: keyof typeof data.preferred_learning_times, checked: boolean) => {
        setData('preferred_learning_times', {
            ...data.preferred_learning_times,
            [day]: {
                ...data.preferred_learning_times[day],
                enabled: checked,
                from: checked ? data.preferred_learning_times[day].from || '09:00' : '',
                to: checked ? data.preferred_learning_times[day].to || '17:00' : '',
            }
        });
    };

    const handleTimeSlotChange = (day: keyof typeof data.preferred_learning_times, field: 'from' | 'to', value: string) => {
        setData('preferred_learning_times', {
            ...data.preferred_learning_times,
            [day]: {
                ...data.preferred_learning_times[day],
                [field]: value,
            }
        });
    };



    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('onboarding.student'), {
            onSuccess: () => {
                onClose();
            },
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <div className="flex items-center justify-between">
                        <DialogTitle className="text-lg font-semibold flex items-center gap-2">
                            <BookOpen className="w-5 h-5 text-teal-600" />
                            Quick Setup
                        </DialogTitle>
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={onClose}
                            className="h-6 w-6 p-0"
                        >
                            <X className="h-4 w-4" />
                        </Button>
                    </div>
                </DialogHeader>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-4">
                        {/* Preferred Subjects */}
                        <div className="grid gap-2">
                            <Label className="text-sm flex items-center gap-1">
                                <BookOpen className="w-3 h-3" />
                                Subjects of Interest
                            </Label>
                            <div className="grid grid-cols-2 gap-2 max-h-32 overflow-y-auto">
                                {availableSubjects.map((subject) => (
                                    <div key={subject} className="flex items-center space-x-2">
                                        <Checkbox
                                            id={`subject-${subject}`}
                                            checked={data.preferred_subjects.includes(subject)}
                                            onCheckedChange={(checked) => handleSubjectChange(subject, checked as boolean)}
                                        />
                                        <Label htmlFor={`subject-${subject}`} className="text-xs font-normal">
                                            {subject}
                                        </Label>
                                    </div>
                                ))}
                            </div>
                            {errors.preferred_subjects && <span className="text-sm text-red-500">{errors.preferred_subjects}</span>}
                        </div>

                        {/* Preferred Learning Times */}
                        <div className="grid gap-4">
                            <div>
                                <Label className="text-sm font-medium text-gray-700 flex items-center gap-1">
                                    <Clock className="w-3 h-3" />
                                    Preferred Learning Times
                                </Label>
                                <p className="text-xs text-gray-500 mt-1">
                                    A correct time zone is essential to coordinate lessons with international students
                                </p>
                            </div>
                            
                            <div className="space-y-4">
                                {Object.entries(data.preferred_learning_times).map(([day, schedule]) => (
                                    <div key={day} className="space-y-2">
                                        <div className="flex items-center space-x-2">
                                            <Checkbox
                                                id={day}
                                                checked={schedule.enabled}
                                                onCheckedChange={(checked) => handleDayToggle(day as keyof typeof data.preferred_learning_times, checked as boolean)}
                                            />
                                            <Label htmlFor={day} className="text-sm font-medium capitalize">
                                                {day}
                                            </Label>
                                        </div>
                                        
                                        {schedule.enabled && (
                                            <div className="ml-6 grid grid-cols-2 gap-4">
                                                <div>
                                                    <Label className="text-xs text-gray-600">From</Label>
                                                    <input
                                                        type="time"
                                                        value={schedule.from}
                                                        onChange={(e) => handleTimeSlotChange(day as keyof typeof data.preferred_learning_times, 'from', e.target.value)}
                                                        className="w-full h-8 px-3 text-sm bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                                    />
                                                </div>
                                                <div>
                                                    <Label className="text-xs text-gray-600">To</Label>
                                                    <input
                                                        type="time"
                                                        value={schedule.to}
                                                        onChange={(e) => handleTimeSlotChange(day as keyof typeof data.preferred_learning_times, 'to', e.target.value)}
                                                        className="w-full h-8 px-3 text-sm bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                                    />
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                            {errors.preferred_learning_times && <span className="text-sm text-red-500">{errors.preferred_learning_times}</span>}
                        </div>

                        {/* Current Level */}
                        <div className="grid gap-2">
                            <Label htmlFor="current_level" className="text-sm flex items-center gap-1">
                                <Target className="w-3 h-3" />
                                Current Level (Optional)
                            </Label>
                            <Select value={data.current_level} onValueChange={(value) => setData('current_level', value)}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select your level" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="beginner">Beginner</SelectItem>
                                    <SelectItem value="intermediate">Intermediate</SelectItem>
                                    <SelectItem value="advanced">Advanced</SelectItem>
                                    <SelectItem value="memorization">Memorization (Hifz)</SelectItem>
                                </SelectContent>
                            </Select>
                            {errors.current_level && <span className="text-sm text-red-500">{errors.current_level}</span>}
                        </div>

                        {/* Learning Goals */}
                        <div className="grid gap-2">
                            <Label htmlFor="learning_goals" className="text-sm flex items-center gap-1">
                                <Heart className="w-3 h-3" />
                                Learning Goals (Optional)
                            </Label>
                            <Textarea
                                id="learning_goals"
                                placeholder="What would you like to achieve?"
                                value={data.learning_goals}
                                onChange={(e) => setData('learning_goals', e.target.value)}
                                className="min-h-[60px] text-sm"
                            />
                            {errors.learning_goals && <span className="text-sm text-red-500">{errors.learning_goals}</span>}
                        </div>
                    </div>

                    <div className="flex gap-2 pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                            className="flex-1"
                        >
                            Skip for now
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing}
                            className="flex-1 bg-teal-600 hover:bg-teal-700"
                        >
                            {processing ? 'Saving...' : 'Get Started'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
