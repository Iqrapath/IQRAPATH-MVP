import { Head } from '@inertiajs/react';
import { type AdminProfile } from '@/types';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

interface AdminDashboardProps {
    adminProfile: AdminProfile;
}

export default function AdminDashboard({ adminProfile }: AdminDashboardProps) {
    return (
        <AppLayout>
            <Head title="Admin Dashboard" />
            
            <div className="container mx-auto py-10">
                <h1 className="text-3xl font-bold mb-6">Admin Dashboard</h1>
                
                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>Admin Profile</CardTitle>
                            <CardDescription>Your admin information</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                <div>
                                    <span className="font-medium">Department: </span>
                                    <span>{adminProfile?.department || 'Not set'}</span>
                                </div>
                                <div>
                                    <span className="font-medium">Admin Level: </span>
                                    <span>{adminProfile?.admin_level || 'Not set'}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader>
                            <CardTitle>User Management</CardTitle>
                            <CardDescription>Manage system users</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p>User management functionality will be implemented here.</p>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader>
                            <CardTitle>System Settings</CardTitle>
                            <CardDescription>Configure system settings</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p>System settings functionality will be implemented here.</p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
} 