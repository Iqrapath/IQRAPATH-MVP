import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin/admin-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { ArrowLeft, Filter, Download } from 'lucide-react';
import EarningsOverview from './earnings-components/earnings-overview';
import TransactionLog from './earnings-components/transaction-log';
import PayoutRequests from './earnings-components/payout-requests';
import EarningsFilters from './earnings-components/earnings-filters';
import { Breadcrumbs } from '@/components/breadcrumbs';

interface Teacher {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    location: string;
}

interface Earnings {
    wallet_balance: number;
    total_earned: number;
    total_withdrawn: number;
    pending_payouts: number;
    calculated_at: string;
}

interface Transaction {
    id: number;
    uuid: string;
    date: string;
    description: string;
    amount: number;
    formatted_amount: string;
    type: string;
    status: string;
    session?: {
        id: number;
        subject: string;
        student_name?: string;
    };
    created_by?: {
        name: string;
        role: string;
    };
    created_at: string;
}

interface PayoutRequest {
    id: number;
    uuid: string;
    request_date: string;
    amount: number;
    formatted_amount: string;
    payment_method: string;
    payment_method_display: string;
    status: string;
    status_display: string;
    processed_date?: string;
    processed_by?: {
        name: string;
    };
    notes?: string;
    created_at: string;
}

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

interface Filters {
    type?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
    payout_status?: string;
    payout_date_from?: string;
    payout_date_to?: string;
    [key: string]: string | undefined;
}

interface Props {
    teacher: Teacher;
    earnings: Earnings;
    transactions: PaginatedData<Transaction>;
    payoutRequests: PaginatedData<PayoutRequest>;
    filters: Filters;
}

export default function TeacherEarnings({ teacher, earnings, transactions, payoutRequests, filters }: Props) {
    const [showFilters, setShowFilters] = useState(false);

    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map(word => word[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const handleGoBack = () => {
        router.visit(route('admin.teachers.show', teacher.id));
    };

    const handleExport = () => {
        // TODO: Implement export functionality
        console.log('Export earnings data');
    };

    return (
        <AdminLayout pageTitle="Teacher Earnings" showRightSidebar={false}>
            <Head title={`${teacher.name} - Earnings`} />

            <div className="container py-6 space-y-6">
                <div className="mb-8">
                    <Breadcrumbs
                        breadcrumbs={[
                            { title: 'Dashboard', href: '/admin/dashboard' },
                            { title: 'Teachers', href: '/admin/teachers' },
                            { title: teacher.name, href: `/admin/teachers/${teacher.id}` },
                            { title: 'Earnings', href: `/admin/teachers/${teacher.id}/earnings` },
                        ]}
                    />
                </div>

                {/* Header */}
                <div className="flex items-center justify-between">


                    <div className="flex items-center gap-3">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setShowFilters(!showFilters)}
                            className="flex items-center gap-2"
                        >
                            <Filter className="h-4 w-4" />
                            Filters
                        </Button>

                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleExport}
                            className="flex items-center gap-2"
                        >
                            <Download className="h-4 w-4" />
                            Export
                        </Button>
                    </div>
                </div>

                {/* Earnings Overview */}
                <EarningsOverview teacher={teacher} earnings={earnings} profile={null} />

                {/* Filters */}
                {showFilters && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Filter Options</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <EarningsFilters filters={filters} />
                        </CardContent>
                    </Card>
                )}

                {/* Transaction Log */}
                <Card>
                    <CardHeader>
                        <CardTitle>Transaction Log</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <TransactionLog
                            transactions={transactions}
                            filters={filters}
                        />
                    </CardContent>
                </Card>

                {/* Payout Requests */}
                <Card>
                    <CardHeader>
                        <CardTitle>Payout Requests</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <PayoutRequests
                            payoutRequests={payoutRequests}
                            filters={filters}
                        />
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
