import { Head } from '@inertiajs/react';
import { type StudentProfile, type User } from '@/types';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

interface StudentDashboardProps {
    studentProfile: StudentProfile;
    guardian: User | null;
}

export default function StudentDashboard({ studentProfile, guardian }: StudentDashboardProps) {
    return (
        <AppLayout>
            <Head title="Student Dashboard" />
            
            <div className="container mx-auto py-10">
                <h1 className="text-3xl font-bold mb-6">Student Dashboard</h1>
                
                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>Student Profile</CardTitle>
                            <CardDescription>Your student information</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                <div>
                                    <span className="font-medium">Grade Level: </span>
                                    <span>{studentProfile?.grade_level || 'Not set'}</span>
                                </div>
                                <div>
                                    <span className="font-medium">School: </span>
                                    <span>{studentProfile?.school_name || 'Not set'}</span>
                                </div>
                                {guardian && (
                                    <div>
                                        <span className="font-medium">Guardian: </span>
                                        <span>{guardian.name}</span>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader>
                            <CardTitle>My Classes</CardTitle>
                            <CardDescription>Your enrolled classes</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p>Class list functionality will be implemented here.</p>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader>
                            <CardTitle>Learning Progress</CardTitle>
                            <CardDescription>Track your learning progress</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p>Progress tracking functionality will be implemented here.</p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
} 