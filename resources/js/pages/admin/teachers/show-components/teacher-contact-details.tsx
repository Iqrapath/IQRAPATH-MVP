import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Mail,
    Phone,
    BookOpen,
    Calendar,
    Star,
    Edit,
    User,
    MapPin
} from 'lucide-react';
import { PhoneIcon } from '@/components/icons/phone-icon';
import { BookIcon } from '@/components/icons/book-icon';
import { SessionIcon } from '@/components/icons/session-icon';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';

interface Teacher {
    id: number;
    name: string;
    email: string;
    phone: string;
    location?: string;
}

interface TeacherProfile {
    subjects: any[];
    rating?: number;
    reviews_count?: number;
}

interface Props {
    teacher: Teacher;
    profile: TeacherProfile | null;
    totalSessions: number;
}

export default function TeacherContactDetails({ teacher, profile, totalSessions }: Props) {
    const [isEditModalOpen, setIsEditModalOpen] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [formData, setFormData] = useState({
        name: teacher.name || '',
        phone: teacher.phone || '',
        email: teacher.email || '',
        location: teacher.location || '',
    });

    const subjectsList = profile?.subjects?.map(subject => subject.name).join(', ') || 'No subjects assigned';

    const handleInputChange = (field: string, value: string) => {
        setFormData(prev => ({
            ...prev,
            [field]: value
        }));
    };

    const handleSave = async () => {
        if (!formData.name.trim() || !formData.email.trim()) {
            toast.error('Name and email are required fields');
            return;
        }

        setIsLoading(true);
        try {
            await router.patch(`/admin/teachers/${teacher.id}/contact`, formData, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Contact details updated successfully');
                    setIsEditModalOpen(false);
                },
                onError: (errors) => {
                    const errorMessage = Object.values(errors).flat().join(', ');
                    toast.error(errorMessage || 'Failed to update contact details');
                }
            });
        } catch (error) {
            toast.error('Failed to update contact details');
        } finally {
            setIsLoading(false);
        }
    };

    const openEditModal = () => {
        setFormData({
            name: teacher.name || '',
            phone: teacher.phone || '',
            email: teacher.email || '',
            location: teacher.location || '',
        });
        setIsEditModalOpen(true);
    };

    return (
        <>
            <Card className="mb-8 shadow-sm">
                <CardContent className="p-6">
                    <div className="flex items-center justify-between">
                        <div className="space-y-6">
                            {/* First Row: Email and Phone */}
                            <div className="flex items-center gap-6">
                                <div className="flex items-center gap-3">
                                    <Mail className="h-5 w-5 text-teal-600" />
                                    <span className="text-gray-700">{teacher.email}</span>
                                </div>
                                <div className="flex items-center gap-1">
                                    <PhoneIcon className="h-5 w-5 text-teal-600" />
                                    <span className="text-gray-700">{teacher.phone || 'Phone not provided'}</span>
                                </div>
                            </div>

                            {/* Second Row: Subjects, Sessions, and Edit Button */}
                            <div className="flex items-center gap-6">
                                <div className="flex items-center gap-3">
                                    <BookIcon className="h-5 w-5 text-teal-600" />
                                    <span className="text-gray-700">Subjects: {subjectsList}</span>
                                </div>
                                <div className="flex items-center gap-6">
                                    <div className="flex items-center gap-2">
                                        <SessionIcon className="h-5 w-5 text-teal-600" />
                                        <span className="text-gray-700">{totalSessions} Sessions</span>
                                    </div>
                                </div>
                            </div>

                            {/* Third Row: Rating and Reviews */}
                            <div className="flex items-center">
                                <div className="flex items-center gap-3">
                                    <Star className="h-5 w-5 text-teal-600" />
                                    <span className="text-gray-700">
                                        {profile?.rating ? `${profile.rating.toFixed(1)} (${profile.reviews_count || 0} Reviews)` : 'No reviews yet'}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div className="text-right">
                            <Button 
                                variant="link" 
                                className="text-sm p-0 h-auto cursor-pointer text-teal-600 hover:text-teal-700" 
                                onClick={openEditModal}
                            >
                                {/* <Edit className="h-4 w-4 mr-1" /> */}
                                Edit
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Edit Modal */}
            <Dialog open={isEditModalOpen} onOpenChange={setIsEditModalOpen}>
                <DialogContent className="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle className="text-xl font-semibold text-gray-900">
                            Personal Information
                        </DialogTitle>
                        <DialogDescription className="text-gray-600">
                            Update the teacher's contact details and personal information.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-6 py-4">
                        {/* Full Name */}
                        <div className="space-y-2">
                            <Label htmlFor="name" className="text-sm font-medium text-gray-700">
                                Full Name
                            </Label>
                            <div className="relative">
                                <User className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <Input
                                    id="name"
                                    type="text"
                                    placeholder="Enter your username"
                                    value={formData.name}
                                    onChange={(e) => handleInputChange('name', e.target.value)}
                                    className="pl-10 h-12 bg-gray-50 border-gray-200 focus:bg-white focus:border-teal-500 focus:ring-teal-500"
                                />
                            </div>
                        </div>

                        {/* Phone and Email Row */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* Phone Number */}
                            <div className="space-y-2">
                                <Label htmlFor="phone" className="text-sm font-medium text-gray-700">
                                    Phone Number
                                </Label>
                                <div className="relative">
                                    <Phone className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <Input
                                        id="phone"
                                        type="tel"
                                        placeholder="Enter your Phone Number"
                                        value={formData.phone}
                                        onChange={(e) => handleInputChange('phone', e.target.value)}
                                        className="pl-10 h-12 bg-gray-50 border-gray-200 focus:bg-white focus:border-teal-500 focus:ring-teal-500"
                                    />
                                </div>
                            </div>

                            {/* Email Address */}
                            <div className="space-y-2 md:col-span-1">
                                <Label htmlFor="email" className="text-sm font-medium text-gray-700">
                                    Email Address
                                </Label>
                                <div className="relative">
                                    <Mail className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <Input
                                        id="email"
                                        type="email"
                                        placeholder="Enter your email address"
                                        value={formData.email}
                                        onChange={(e) => handleInputChange('email', e.target.value)}
                                        className="pl-10 h-12 bg-gray-50 border-gray-200 focus:bg-white focus:border-teal-500 focus:ring-teal-500"
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Location */}
                        <div className="space-y-2">
                            <Label htmlFor="location" className="text-sm font-medium text-gray-700">
                                Location
                            </Label>
                            <div className="relative">
                                <MapPin className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <Input
                                    id="location"
                                    type="text"
                                    placeholder="Select your location"
                                    value={formData.location}
                                    onChange={(e) => handleInputChange('location', e.target.value)}
                                    className="pl-10 h-12 bg-gray-50 border-gray-200 focus:bg-white focus:border-teal-500 focus:ring-teal-500"
                                />
                            </div>
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
