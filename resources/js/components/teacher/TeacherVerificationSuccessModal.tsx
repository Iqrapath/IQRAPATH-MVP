import { X, User, Calendar, GraduationCap, MessageCircle } from 'lucide-react';
import { router } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { Dialog, DialogContent } from '@/components/ui/dialog';

interface TeacherVerificationSuccessModalProps {
    isOpen: boolean;
    onClose: () => void;
    teacherName: string;
}

export default function TeacherVerificationSuccessModal({ 
    isOpen, 
    onClose, 
    teacherName 
}: TeacherVerificationSuccessModalProps) {
    const handleActionClick = (action: string) => {
        switch (action) {
            case 'profile':
                router.visit(route('teacher.profile.index'));
                break;
            case 'availability':
                router.visit(route('teacher.profile.index'));
                break;
            case 'students':
                router.visit(route('teacher.requests'));
                break;
            case 'messages':
                router.visit(route('teacher.notifications'));
                break;
            case 'dashboard':
                router.visit(route('teacher.dashboard'));
                break;
        }
        onClose();
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-lg border-0 p-0">

                <div className="p-8 text-center space-y-6">
                    {/* Congratulatory Message */}
                    <div className="space-y-3">
                        <h2 className="text-2xl font-bold text-gray-900">
                            🎉 Congratulations, {teacherName}!
                        </h2>
                        <p className="text-teal-600 font-medium">
                            Your profile is now verified! You're ready to start teaching and connecting with students.
                        </p>
                        <p className="text-sm text-gray-600">
                            To help you get started, we've highlighted some quick actions below.
                        </p>
                    </div>

                    {/* Quick Action Buttons */}
                    <div className="grid grid-cols-2 gap-4">
                        {/* View My Profile */}
                        <Button
                            variant="outline"
                            className="h-auto p-4 flex items-center space-x-3 hover:bg-teal-50 hover:border-teal-200"
                            onClick={() => handleActionClick('profile')}
                        >
                            <div className="w-8 h-8 bg-teal-100 rounded-full flex items-center justify-center">
                                <User className="h-4 w-4 text-teal-600" />
                            </div>
                            <span className="text-sm font-medium text-gray-700">View My Profile</span>
                        </Button>

                        {/* Set My Availability */}
                        <Button
                            variant="outline"
                            className="h-auto p-4 flex items-center space-x-3 hover:bg-teal-50 hover:border-teal-200"
                            onClick={() => handleActionClick('availability')}
                        >
                            <div className="w-8 h-8 bg-teal-100 rounded-full flex items-center justify-center">
                                <Calendar className="h-4 w-4 text-teal-600" />
                            </div>
                            <span className="text-sm font-medium text-gray-700">Set My Availability</span>
                        </Button>

                        {/* Find Students */}
                        <Button
                            variant="outline"
                            className="h-auto p-4 flex items-center space-x-3 hover:bg-teal-50 hover:border-teal-200"
                            onClick={() => handleActionClick('students')}
                        >
                            <div className="w-8 h-8 bg-teal-100 rounded-full flex items-center justify-center">
                                <GraduationCap className="h-4 w-4 text-teal-600" />
                            </div>
                            <span className="text-sm font-medium text-gray-700">Find Students</span>
                        </Button>

                        {/* Check Messages */}
                        <Button
                            variant="outline"
                            className="h-auto p-4 flex items-center space-x-3 hover:bg-teal-50 hover:border-teal-200"
                            onClick={() => handleActionClick('messages')}
                        >
                            <div className="w-8 h-8 bg-teal-100 rounded-full flex items-center justify-center">
                                <MessageCircle className="h-4 w-4 text-teal-600" />
                            </div>
                            <span className="text-sm font-medium text-gray-700">Check Messages</span>
                        </Button>
                    </div>

                    {/* Dashboard Link */}
                    <div className="pt-4">
                        <button
                            onClick={() => handleActionClick('dashboard')}
                            className="text-teal-600 hover:text-teal-700 font-medium text-sm transition-colors cursor-pointer"
                        >
                            Go to Dashboard
                        </button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
