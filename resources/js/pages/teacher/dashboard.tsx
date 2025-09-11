import { Head } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { type TeacherProfile, type User } from '@/types';
import TeacherLayout from '@/layouts/teacher/teacher-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import TeacherVerificationSuccessModal from '@/components/teacher/TeacherVerificationSuccessModal';

interface TeacherDashboardProps {
    teacherProfile: TeacherProfile;
    user?: User;
    showVerificationSuccess?: boolean;
}

export default function TeacherDashboard({ teacherProfile, user, showVerificationSuccess = false }: TeacherDashboardProps) {
    const [isModalOpen, setIsModalOpen] = useState(false);

    useEffect(() => {
        if (showVerificationSuccess) {
            setIsModalOpen(true);
        }
    }, [showVerificationSuccess]);

    return (
        <TeacherLayout pageTitle="Teacher Dashboard">
            <Head title="Teacher Dashboard" />
            
            <div className="container mx-auto py-10">
                <h1 className="text-3xl font-bold mb-6">Welcome Back, {user?.name || 'Teacher'}</h1>
                
                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>Teacher Profile</CardTitle>
                            <CardDescription>Your teaching information</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                <div>
                                    <span className="font-medium">Specialization: </span>
                                    <span>{teacherProfile?.specialization || 'Not set'}</span>
                                </div>
                                <div>
                                    <span className="font-medium">Teaching Level: </span>
                                    <span>{teacherProfile?.teaching_level || 'Not set'}</span>
                                </div>
                                <div>
                                    <span className="font-medium">Experience: </span>
                                    <span>{teacherProfile?.experience_years || 'Not set'} years</span>
                                </div>
                                <div>
                                    <span className="font-medium">Hourly Rate: </span>
                                    <span>${teacherProfile?.hourly_rate || 'Not set'}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader>
                            <CardTitle>My Students</CardTitle>
                            <CardDescription>Students you are teaching</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p>Student list functionality will be implemented here.</p>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader>
                            <CardTitle>Teaching Schedule</CardTitle>
                            <CardDescription>Your upcoming classes</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p>Schedule functionality will be implemented here.</p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            {/* Teacher Verification Success Modal */}
            <TeacherVerificationSuccessModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                teacherName={user?.name || 'Teacher'}
            />
        </TeacherLayout>
    );
} 