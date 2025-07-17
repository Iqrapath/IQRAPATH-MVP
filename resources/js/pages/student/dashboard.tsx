import { Head } from '@inertiajs/react';
import { type StudentProfile, type User } from '@/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import StudentLayout from '@/layouts/student/student-layout';

interface StudentDashboardProps {
    studentProfile: StudentProfile;
    user?: User;
}

export default function StudentDashboard({ studentProfile, user }: StudentDashboardProps) {
    return (
        <StudentLayout pageTitle="Student Dashboard">
            <Head title="Student Dashboard" />
            
            <div className="container mx-auto py-10">
                <h1 className="text-3xl font-bold mb-6">Welcome Back, {user?.name || 'Student'}</h1>
                
                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>Student Profile</CardTitle>
                            <CardDescription>Your student information</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                <div>
                                    <span className="font-medium">Date of Birth: </span>
                                    <span>{studentProfile?.date_of_birth || 'Not set'}</span>
                                </div>
                                <div>
                                    <span className="font-medium">Gender: </span>
                                    {/* <span>{studentProfile?.gender || 'Not set'}</span> */}
                                </div>
                                <div>
                                    <span className="font-medium">Status: </span>
                                    {/* <span>{studentProfile?.status || 'Not set'}</span> */}
                                </div>
                                <div>
                                    <span className="font-medium">Registration Date: </span>
                                    {/* <span>{studentProfile?.registration_date || 'Not set'}</span> */}
                                </div>
                                <div>
                                    <span className="font-medium">Grade Level: </span>
                                    <span>{studentProfile?.grade_level || 'Not set'}</span>
                                </div>
                                <div>
                                    <span className="font-medium">School Name: </span>
                                    <span>{studentProfile?.school_name || 'Not set'}</span>
                                </div>
                                <div>
                                    <span className="font-medium">Guardian: </span>
                                    <span>{studentProfile?.guardian_id || 'Not set'}</span>
                                </div>
                                <div>
                                    <span className="font-medium">Learning Goals: </span>
                                    <span>{studentProfile?.learning_goals || 'Not set'}</span>
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
        </StudentLayout>
    );
} 