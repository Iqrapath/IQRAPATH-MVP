import { Head, useForm } from '@inertiajs/react';
import { BookOpen, Users } from 'lucide-react';
import { FormEventHandler } from 'react';
import { type User } from '@/types';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';

interface RoleSelectionProps {
    user: User;
}

type RoleForm = {
    role: 'student' | 'guardian' | '';
};

export default function RoleSelection({ user }: RoleSelectionProps) {
    const { data, setData, post, processing, errors } = useForm<RoleForm>({
        role: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (data.role) {
            post(route('onboarding'));
        }
    };

    const selectRole = (role: 'student' | 'guardian') => {
        setData('role', role);
    };

    return (
        <AppLayout>
            <Head title="How do you want to use this platform?" />
            
            <div className="flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
                <div className="max-w-lg w-full space-y-8">
                    <div className="text-center">
                        <h1 className="text-2xl font-bold text-gray-900 mb-2">
                            How do you want to use this platform?
                        </h1>
                        <p className="text-gray-600 text-sm">
                            Kindly select how you like to use IqraQuest for learning
                        </p>
                    </div>

                    <form onSubmit={submit} className="space-y-4">
                        {/* Student Option */}
                        <div 
                            className={`relative cursor-pointer rounded-lg border-2 p-6 transition-all hover:border-teal-300 ${
                                data.role === 'student' ? 'border-teal-500 bg-teal-50' : 'border-gray-200 bg-white'
                            }`}
                            onClick={() => selectRole('student')}
                        >
                            <div className="flex items-center space-x-4">
                                <div className="flex-shrink-0">
                                    <div className="w-12 h-12 bg-teal-100 rounded-full flex items-center justify-center">
                                        <BookOpen className="w-6 h-6 text-teal-600" />
                                    </div>
                                </div>
                                <div className="flex-grow">
                                    <h3 className="text-lg font-medium text-gray-900">
                                        I'm a Student (Learning for myself)
                                    </h3>
                                </div>
                                <div className="flex-shrink-0">
                                    <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center ${
                                        data.role === 'student' ? 'border-teal-500 bg-teal-500' : 'border-gray-300'
                                    }`}>
                                        {data.role === 'student' && (
                                            <div className="w-2 h-2 bg-white rounded-full"></div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Guardian Option */}
                        <div 
                            className={`relative cursor-pointer rounded-lg border-2 p-6 transition-all hover:border-teal-300 ${
                                data.role === 'guardian' ? 'border-teal-500 bg-teal-50' : 'border-gray-200 bg-white'
                            }`}
                            onClick={() => selectRole('guardian')}
                        >
                            <div className="flex items-center space-x-4">
                                <div className="flex-shrink-0">
                                    <div className="w-12 h-12 bg-teal-100 rounded-full flex items-center justify-center">
                                        <Users className="w-6 h-6 text-teal-600" />
                                    </div>
                                </div>
                                <div className="flex-grow">
                                    <h3 className="text-lg font-medium text-gray-900">
                                        I'm a Guardian (Registering for my child/children)
                                    </h3>
                                </div>
                                <div className="flex-shrink-0">
                                    <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center ${
                                        data.role === 'guardian' ? 'border-teal-500 bg-teal-500' : 'border-gray-300'
                                    }`}>
                                        {data.role === 'guardian' && (
                                            <div className="w-2 h-2 bg-white rounded-full"></div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {errors.role && (
                            <div className="text-red-500 text-center text-sm mt-2">
                                Please select how you want to use the platform.
                            </div>
                        )}

                        <div className="pt-4">
                            <Button
                                type="submit"
                                className="w-full bg-teal-600 hover:bg-teal-700 text-white py-3 rounded-lg font-medium"
                                disabled={!data.role || processing}
                            >
                                {processing ? 'Setting up...' : 'Continue'}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
