import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { type GuardianProfile, type User, type SharedData } from '@/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import GuardianOnboardingModal from '@/components/onboarding/guardian-onboarding-modal';
import GuardianLayout from '@/layouts/guardian/guardian-layout';

type DaySchedule = {
    enabled: boolean;
    from: string;
    to: string;
};

interface Child {
    id: number;
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

interface GuardianDashboardProps {
    guardianProfile: GuardianProfile;
    children: Child[];
    students: Array<{
        user: {
            id: number;
            name: string;
            email?: string;
        };
        grade_level?: string;
        school_name?: string;
    }>;
    availableSubjects: string[];
    showOnboarding?: boolean;
}

export default function GuardianDashboard({ guardianProfile, children, students, availableSubjects, showOnboarding = false }: GuardianDashboardProps) {
    const { auth } = usePage<SharedData>().props;
    const [isOnboardingOpen, setIsOnboardingOpen] = useState(showOnboarding);
    return (
        <GuardianLayout pageTitle="Guardian Dashboard">
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

            {/* Onboarding Modal */}
            <GuardianOnboardingModal 
                isOpen={isOnboardingOpen}
                onClose={() => setIsOnboardingOpen(false)}
                user={auth.user}
                guardianProfile={guardianProfile}
                children={children}
                availableSubjects={availableSubjects}
            />
        </GuardianLayout>
    );
} 