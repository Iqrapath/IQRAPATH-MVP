import { useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { type User, type GuardianProfile } from '@/types';
import { X, Plus, Trash2 } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';

type DaySchedule = {
    enabled: boolean;
    from: string;
    to: string;
};

interface Child {
    id?: number; // Optional for new children
    name: string;
    age: string;
    gender: string;
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
}

interface GuardianOnboardingModalProps {
    isOpen: boolean;
    onClose: () => void;
    user: User;
    guardianProfile?: GuardianProfile;
    children?: Child[];
    availableSubjects: string[];
}

type GuardianOnboardingForm = {
    [key: string]: any;
    children: Child[];
    relationship: string;
};

export default function GuardianOnboardingModal({ isOpen, onClose, user, guardianProfile, children, availableSubjects }: GuardianOnboardingModalProps) {
    const { data, setData, post, processing, errors } = useForm<GuardianOnboardingForm>({
        children: children && children.length > 0 ? children.map(child => ({
            ...child,
            preferred_learning_times: child.preferred_learning_times && typeof child.preferred_learning_times === 'object' && !Array.isArray(child.preferred_learning_times) 
                ? child.preferred_learning_times 
                : {
                    monday: { enabled: false, from: '', to: '' },
                    tuesday: { enabled: false, from: '', to: '' },
                    wednesday: { enabled: false, from: '', to: '' },
                    thursday: { enabled: false, from: '', to: '' },
                    friday: { enabled: false, from: '', to: '' },
                    saturday: { enabled: false, from: '', to: '' },
                    sunday: { enabled: false, from: '', to: '' },
                }
        })) : [{
            name: '',
            age: '',
            gender: '',
            preferred_subjects: [],
            preferred_learning_times: {
                monday: { enabled: false, from: '', to: '' },
                tuesday: { enabled: false, from: '', to: '' },
                wednesday: { enabled: false, from: '', to: '' },
                thursday: { enabled: false, from: '', to: '' },
                friday: { enabled: false, from: '', to: '' },
                saturday: { enabled: false, from: '', to: '' },
                sunday: { enabled: false, from: '', to: '' },
            },
        }],
        relationship: 'guardian',
    });

    const addChild = () => {
        setData('children', [...data.children, {
            name: '',
            age: '',
            gender: '',
            preferred_subjects: [],
            preferred_learning_times: {
                monday: { enabled: false, from: '', to: '' },
                tuesday: { enabled: false, from: '', to: '' },
                wednesday: { enabled: false, from: '', to: '' },
                thursday: { enabled: false, from: '', to: '' },
                friday: { enabled: false, from: '', to: '' },
                saturday: { enabled: false, from: '', to: '' },
                sunday: { enabled: false, from: '', to: '' },
            },
        }]);
    };

    const removeChild = (index: number) => {
        if (data.children.length > 1) {
            setData('children', data.children.filter((_, i) => i !== index));
        }
    };

    const updateChild = (index: number, field: keyof Child, value: any) => {
        const updatedChildren = [...data.children];
        updatedChildren[index] = { ...updatedChildren[index], [field]: value };
        setData('children', updatedChildren);
    };

    const handleSubjectChange = (childIndex: number, subject: string, checked: boolean) => {
        const child = data.children[childIndex];
        const updatedSubjects = checked 
            ? [...child.preferred_subjects, subject]
            : child.preferred_subjects.filter(s => s !== subject);
        updateChild(childIndex, 'preferred_subjects', updatedSubjects);
    };

    const handleDayToggle = (childIndex: number, day: keyof Child['preferred_learning_times'], checked: boolean) => {
        const child = data.children[childIndex];
        const updatedTimes = {
            ...child.preferred_learning_times,
            [day]: {
                ...child.preferred_learning_times[day],
                enabled: checked,
                from: checked ? child.preferred_learning_times[day].from || '09:00' : '',
                to: checked ? child.preferred_learning_times[day].to || '17:00' : '',
            }
        };
        updateChild(childIndex, 'preferred_learning_times', updatedTimes);
    };

    const handleTimeSlotChange = (childIndex: number, day: keyof Child['preferred_learning_times'], field: 'from' | 'to', value: string) => {
        const child = data.children[childIndex];
        const updatedTimes = {
            ...child.preferred_learning_times,
            [day]: {
                ...child.preferred_learning_times[day],
                [field]: value,
            }
        };
        updateChild(childIndex, 'preferred_learning_times', updatedTimes);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/onboarding/guardian', {
            onSuccess: () => {
                onClose();
            },
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="max-w-lg max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <div className="flex items-center justify-between">
                        <DialogTitle className="text-lg font-semibold text-gray-900">
                            Register Your Child for Quran Learning
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
                    <p className="text-sm text-gray-600 mt-2">
                        As a Guardian, you can manage multiple children from one account. Add each child's learning preferences to personalize their experience.
                    </p>
                </DialogHeader>

                <form onSubmit={submit} className="space-y-6">
                    {/* Children */}
                    <div className="space-y-4">

                        {data.children.map((child, index) => (
                            <div key={index} className="space-y-4">
                                {data.children.length > 1 && (
                                    <div className="flex justify-end">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => removeChild(index)}
                                            className="text-red-600 hover:text-red-700"
                                        >
                                            <Trash2 className="w-3 h-3" />
                                        </Button>
                                    </div>
                                )}

                                {/* Child's Name */}
                                <div>
                                    <Label className="text-sm font-medium text-gray-700">
                                        Child's Full Name
                                    </Label>
                                    <Input
                                        value={child.name}
                                        onChange={(e) => updateChild(index, 'name', e.target.value)}
                                        placeholder="e.g. Fatima Bello"
                                        className="mt-1 bg-gray-50 border-gray-200"
                                    />
                                    {(errors as any)[`children.${index}.name`] && <span className="text-sm text-red-500">{(errors as any)[`children.${index}.name`]}</span>}
                                </div>

                                {/* Age and Gender */}
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <Label className="text-sm font-medium text-gray-700">
                                            Age
                                        </Label>
                                        <Input
                                            value={child.age}
                                            onChange={(e) => updateChild(index, 'age', e.target.value)}
                                            placeholder="e.g. 7 years"
                                            className="mt-1 bg-gray-50 border-gray-200"
                                        />
                                        {(errors as any)[`children.${index}.age`] && <span className="text-sm text-red-500">{(errors as any)[`children.${index}.age`]}</span>}
                                    </div>
                                    <div>
                                        <Label className="text-sm font-medium text-gray-700">
                                            Gender
                                        </Label>
                                        <Select value={child.gender} onValueChange={(value) => updateChild(index, 'gender', value)}>
                                            <SelectTrigger className="mt-1 bg-gray-50 border-gray-200">
                                                <SelectValue placeholder="Select gender" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="female">Female</SelectItem>
                                                <SelectItem value="male">Male</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {(errors as any)[`children.${index}.gender`] && <span className="text-sm text-red-500">{(errors as any)[`children.${index}.gender`]}</span>}
                                    </div>
                                </div>

                                {/* Preferred Subjects */}
                                <div>
                                    <Label className="text-sm font-medium text-gray-700">
                                        Preferred Subjects
                                    </Label>
                                    <div className="mt-3 grid grid-cols-3 gap-x-4 gap-y-3">
                                        {availableSubjects.map((subject) => (
                                            <div key={subject} className="flex items-center space-x-2">
                                                <Checkbox
                                                    id={`${index}-${subject}`}
                                                    checked={child.preferred_subjects.includes(subject)}
                                                    onCheckedChange={(checked) => handleSubjectChange(index, subject, checked as boolean)}
                                                    className="data-[state=checked]:bg-gray-900 data-[state=checked]:border-gray-900"
                                                />
                                                <Label htmlFor={`${index}-${subject}`} className="text-sm text-gray-700 cursor-pointer">
                                                    {subject}
                                                </Label>
                                            </div>
                                        ))}
                                    </div>
                                    {(errors as any)[`children.${index}.preferred_subjects`] && <span className="text-sm text-red-500">{(errors as any)[`children.${index}.preferred_subjects`]}</span>}
                                </div>

                                {/* Preferred Learning Times */}
                                <div>
                                    <Label className="text-sm font-medium text-gray-700">
                                        Preferred Learning Times
                                    </Label>
                                    <p className="text-xs text-gray-500 mt-1">
                                        A correct time zone is essential to coordinate lessons with international students
                                    </p>
                                    
                                    <div className="mt-3 space-y-4">
                                        {Object.entries(child.preferred_learning_times).map(([day, schedule]) => (
                                            <div key={day} className="space-y-2">
                                                <div className="flex items-center space-x-2">
                                                    <Checkbox
                                                        id={`${index}-${day}`}
                                                        checked={schedule.enabled}
                                                        onCheckedChange={(checked) => handleDayToggle(index, day as keyof Child['preferred_learning_times'], checked as boolean)}
                                                        className="data-[state=checked]:bg-gray-900 data-[state=checked]:border-gray-900"
                                                    />
                                                    <Label htmlFor={`${index}-${day}`} className="text-sm text-gray-700 capitalize">
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
                                                                onChange={(e) => handleTimeSlotChange(index, day as keyof Child['preferred_learning_times'], 'from', e.target.value)}
                                                                className="w-full h-8 px-3 text-sm bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                                            />
                                                        </div>
                                                        <div>
                                                            <Label className="text-xs text-gray-600">To</Label>
                                                            <input
                                                                type="time"
                                                                value={schedule.to}
                                                                onChange={(e) => handleTimeSlotChange(index, day as keyof Child['preferred_learning_times'], 'to', e.target.value)}
                                                                className="w-full h-8 px-3 text-sm bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                                            />
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                    {(errors as any)[`children.${index}.preferred_learning_times`] && <span className="text-sm text-red-500">{(errors as any)[`children.${index}.preferred_learning_times`]}</span>}
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Add Another Child Link */}
                    <div className="pt-2">
                        <button
                            type="button"
                            className="text-sm text-teal-600 hover:text-teal-700 font-medium"
                            onClick={addChild}
                        >
                            Add Another Child
                        </button>
                        <p className="text-xs text-gray-500 mt-1">
                            Adds a new child section to the form
                        </p>
                    </div>

                    {/* Submit Button */}
                    <div className="pt-4">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="w-full bg-teal-600 hover:bg-teal-700 text-white py-3 rounded-lg font-medium"
                        >
                            {processing ? 'Saving...' : 'Save and Continue'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
