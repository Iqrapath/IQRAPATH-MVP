import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { type User } from '@/types';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';

interface StudentOnboardingProps {
    user: User;
}

export default function StudentOnboarding({ user }: StudentOnboardingProps) {
    const { post, processing } = useForm();

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('onboarding.student'));
    };

    return (
        <AppLayout>
            <Head title="Student Setup" />
            
            <div className="container mx-auto py-10">
                <div className="max-w-2xl mx-auto">
                    <div className="text-center mb-10">
                        <h1 className="text-3xl font-bold mb-4">Welcome Student {user.name}!</h1>
                        <p className="text-lg text-muted-foreground">
                            Let's personalize your learning experience
                        </p>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Student Profile Setup</CardTitle>
                            <CardDescription>
                                Tell us about your learning goals and preferences
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-6">
                                <div className="text-center">
                                    <p className="text-muted-foreground mb-6">
                                        Student onboarding form will be implemented here.
                                        This will include:
                                    </p>
                                    <ul className="text-left text-sm text-muted-foreground space-y-2 max-w-md mx-auto">
                                        <li>• Current Quran knowledge level</li>
                                        <li>• Learning goals and objectives</li>
                                        <li>• Preferred learning schedule</li>
                                        <li>• Language preferences</li>
                                        <li>• Special requirements</li>
                                    </ul>
                                </div>

                                <div className="text-center pt-6">
                                    <Button
                                        type="submit"
                                        className="bg-teal-600 hover:bg-teal-700 text-white px-8 py-3 rounded-full"
                                        disabled={processing}
                                    >
                                        {processing ? 'Setting up...' : 'Complete Setup'}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
