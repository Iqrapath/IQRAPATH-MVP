import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import GuardianLayout from '@/layouts/guardian/guardian-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Plus, Trash2 } from 'lucide-react';

const breadcrumbs = [
    { title: "Dashboard", href: route("guardian.dashboard") },
    { title: "Children Details", href: route("guardian.children.index") },
    { title: "Edit Student", href: "#", className: "text-[#338078]" }
];

type DaySchedule = {
    enabled: boolean;
    from: string;
    to: string;
};

interface Child {
    id?: number;
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

type EditChildForm = {
    [key: string]: any;
    children: Child[];
    relationship: string;
};

interface EditChildProps {
    child: Child;
    availableSubjects: string[];
}

export default function EditChild({ child, availableSubjects }: EditChildProps) {
    const { data, setData, put, processing, errors } = useForm<EditChildForm>({
        children: [{
            id: child.id,
            name: child.name,
            age: child.age,
            gender: child.gender,
            preferred_subjects: child.preferred_subjects,
            preferred_learning_times: child.preferred_learning_times,
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
        put(route('guardian.children.update', child.id), {
            onSuccess: () => {
                // Redirect to children index page
                window.location.href = route('guardian.children.index');
            },
        });
    };

    return (
        <GuardianLayout pageTitle="Edit Student">
            <Head title="Edit Student" />

            <div className="max-w-4xl mx-auto p-6">
                <div className="mb-6">
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>

                {/* Main Form Card */}
                <div className="bg-white rounded-3xl border border-gray-100 shadow-sm p-8">
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold text-gray-900 mb-3">
                            Edit Your Child's Information
                        </h1>
                        <p className="text-gray-600">
                            Update your child's learning preferences and personal information to ensure the best learning experience.
                        </p>
                    </div>

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
                        <div className="pt-4 flex justify-end">
                            <Button
                                type="submit"
                                disabled={processing}
                                className="w-auto bg-teal-600 hover:bg-teal-700 text-white py-3 rounded-full font-medium"
                            >
                                {processing ? 'Updating...' : 'Update Child Information'}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </GuardianLayout>
    );
}
