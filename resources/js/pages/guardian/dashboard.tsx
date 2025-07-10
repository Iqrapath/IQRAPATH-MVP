import { Head } from '@inertiajs/react';
import { type GuardianProfile } from '@/types';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

interface GuardianDashboardProps {
    guardianProfile: GuardianProfile;
    students: Array<{
        user: {
            id: number;
            name: string;
            email?: string;
        };
        grade_level?: string;
        school_name?: string;
    }>;
}

export default function GuardianDashboard({ guardianProfile, students }: GuardianDashboardProps) {
    return (
        <AppLayout>
            <Head title="Guardian Dashboard" />
            
            <div className="container mx-auto py-10">
                <h1 className="text-3xl font-bold mb-6">Guardian Dashboard</h1>
                
                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>Guardian Profile</CardTitle>
                            <CardDescription>Your guardian information</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                <div>
                                    <span className="font-medium">Relationship: </span>
                                    <span>{guardianProfile?.relationship || 'Not set'}</span>
                                </div>
                                <div>
                                    <span className="font-medium">Emergency Contact: </span>
                                    <span>{guardianProfile?.emergency_contact || 'Not set'}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader>
                            <CardTitle>My Students</CardTitle>
                            <CardDescription>Students under your guardianship</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {students && students.length > 0 ? (
                                <div className="space-y-4">
                                    {students.map((student) => (
                                        <div key={student.user.id} className="p-3 border rounded-md">
                                            <h3 className="font-medium">{student.user.name}</h3>
                                            {student.grade_level && <p className="text-sm">Grade: {student.grade_level}</p>}
                                            {student.school_name && <p className="text-sm">School: {student.school_name}</p>}
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p>No students linked to your account yet.</p>
                            )}
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader>
                            <CardTitle>Progress Reports</CardTitle>
                            <CardDescription>Student progress reports</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p>Progress reports functionality will be implemented here.</p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
} 