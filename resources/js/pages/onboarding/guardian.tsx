import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { type User } from '@/types';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';

interface GuardianOnboardingProps {
    user: User;
}

export default function GuardianOnboarding({ user }: GuardianOnboardingProps) {
    const { post, processing } = useForm();

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('onboarding.guardian'));
    };

    return (
        <AppLayout>
            <Head title="Guardian Setup" />
            
            <div className="container mx-auto py-10">
                <div className="max-w-2xl mx-auto">
                    <div className="text-center mb-10">
                        <h1 className="text-3xl font-bold mb-4">Welcome Guardian {user.name}!</h1>
                        <p className="text-lg text-muted-foreground">
                            Let's set up profiles for your children
                        </p>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Guardian Profile Setup</CardTitle>
                            <CardDescription>
                                Add your children and manage their learning journey
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-6">
                                <div className="text-center">
                                    <p className="text-muted-foreground mb-6">
                                        Guardian onboarding form will be implemented here.
                                        This will include:
                                    </p>
                                    <ul className="text-left text-sm text-muted-foreground space-y-2 max-w-md mx-auto">
                                        <li>• Add children profiles</li>
                                        <li>• Set learning goals for each child</li>
                                        <li>• Preferred teaching methods</li>
                                        <li>• Schedule preferences</li>
                                        <li>• Payment and billing setup</li>
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
