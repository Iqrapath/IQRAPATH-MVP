import { Head, Link } from '@inertiajs/react';
import { AlertCircle, Bell } from 'lucide-react';
import { type User } from '@/types';
import AppLayout from '@/layouts/app-layout';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

interface UnassignedProps {
    user: User;
}

export default function Unassigned({ user }: UnassignedProps) {
    return (
        <AppLayout>
            <Head title="Account Pending" />
            
            <div className="container mx-auto py-10">
                <Card className="mx-auto max-w-2xl">
                    <CardHeader>
                        <CardTitle>Account Pending Assignment</CardTitle>
                        <CardDescription>Your account is waiting for role assignment</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <Alert>
                            <AlertCircle className="h-4 w-4" />
                            <AlertTitle>Waiting for role assignment</AlertTitle>
                            <AlertDescription>
                                Your account has been created successfully, but you haven't been assigned a role yet.
                                An administrator will assign you a role soon. Please check back later.
                            </AlertDescription>
                        </Alert>

                        <div className="space-y-4">
                            <h3 className="text-lg font-medium">Your account information:</h3>
                            <div className="grid gap-2">
                                <div className="grid grid-cols-2">
                                    <span className="font-medium">Name:</span>
                                    <span>{user.name}</span>
                                </div>
                                {user.email && (
                                    <div className="grid grid-cols-2">
                                        <span className="font-medium">Email:</span>
                                        <span>{user.email}</span>
                                    </div>
                                )}
                                {user.phone && (
                                    <div className="grid grid-cols-2">
                                        <span className="font-medium">Phone:</span>
                                        <span>{user.phone}</span>
                                    </div>
                                )}
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter>
                        <Link href={route('unassigned.notifications')} className="w-full">
                            <Button variant="outline" className="w-full">
                                <Bell className="mr-2 h-4 w-4" />
                                View Notifications
                            </Button>
                        </Link>
                    </CardFooter>
                </Card>
            </div>
        </AppLayout>
    );
} 