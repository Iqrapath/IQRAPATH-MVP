import { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin/admin-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertCircle } from 'lucide-react';
import TeacherPayouts from './components/TeacherPayouts';
import StudentWithdrawals from './components/StudentWithdrawals';
import StudentPayments from './components/StudentPayments';

/**
 * 🎨 FIGMA DESIGN REFERENCE
 * 
 * Component: Admin Payment Management (Index)
 * Figma URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAQUEST?node-id=2069-12736
 * Export: .cursor/design-references/admin/payments/index-default-desktop@2x.png
 * 
 * 📏 EXACT SPECIFICATIONS:
 * - Container max width: 1200px
 * - Tabs radius: 28px, height: 44px, padding-x: 22px
 * - Section title size: 20px, weight: 600
 * - Filters gap: 16px; inputs height: 40px; search button width: 84px
 * - Table row height: 64px; header bg: #F6F7F9; cell padding-x: 20px
 * - Status colors: Pending #F59E0B, Approved #10B981, Missed #EF4444
 * - Page background: #F7FBFC
 * 
 * 📱 RESPONSIVE: Desktop layout primary; simple stack on < 768px
 * 🎯 STATES: Tabs active/hover, filters focus, row actions menu
 */

interface PayoutRequestRow {
    id: number;
    teacher_name?: string;
    email?: string;
    teacher?: {
        name: string;
        email: string;
    };
    amount: number;
    request_date: string;
    payment_method: string;
    payment_details?: {
        bank_name?: string;
        account_number?: string;
        account_name?: string;
    };
    status: 'pending' | 'approved' | 'rejected' | 'paid' | string;
}

interface StudentWithdrawalRow {
    id: number;
    student_name?: string;
    email?: string;
    user?: {
        name: string;
        email: string;
    };
    amount: number;
    request_date: string;
    bank_name?: string;
    account_number?: string;
    account_name?: string;
    payment_method?: {
        bank_name?: string;
        account_number?: string;
        account_name?: string;
    };
    status: 'pending' | 'approved' | 'rejected' | 'processing' | 'completed' | string;
}

interface StudentPaymentRow {
    id: number;
    date: string;
    student_name: string;
    student_email?: string;
    plan: string;
    amount: number;
    currency?: string;
    payment_method: string;
    status: string;
}

interface Props {
    totalTeachers?: number;
    totalEarnings?: number;
    pendingPayouts?: number;
    pendingPayoutsAmount?: number;
    pendingPayoutRequests?: PayoutRequestRow[];
    studentWithdrawalRequests?: StudentWithdrawalRow[];
    studentPayments?: StudentPaymentRow[];
    error?: string;
}

export default function FinancialIndex({
    pendingPayoutRequests = [],
    studentWithdrawalRequests = [],
    studentPayments = [],
    totalTeachers = 0,
    totalEarnings = 0,
    pendingPayouts = 0,
    pendingPayoutsAmount = 0,
    error
}: Props) {
    const [activeTab, setActiveTab] = useState<'teacher-payouts' | 'student-withdrawals' | 'student-payments' | 'transactions' | 'settings'>('teacher-payouts');
    const [hasError, setHasError] = useState<string | null>(null);

    // Handle error from backend
    useEffect(() => {
        if (error) {
            setHasError(error);
        }
    }, [error]);

    const goTo = (tab: 'teacher-payouts' | 'student-withdrawals' | 'student-payments' | 'transactions' | 'settings') => {
        try {
            setActiveTab(tab);

            // Navigate to different routes based on tab
            if (tab === 'transactions') {
                router.visit(route('admin.financial.transactions'));
            } else if (tab === 'teacher-payouts') {
                router.visit(route('admin.financial.dashboard'));
            }
            // Other tabs stay on same page for now
        } catch (err) {
            console.error('Navigation error:', err);
            setHasError('Failed to navigate. Please try again.');
        }
    };

    // Safe data with fallbacks
    const safePayoutRequests = Array.isArray(pendingPayoutRequests)
        ? pendingPayoutRequests
        : [];

    const safeWithdrawalRequests = Array.isArray(studentWithdrawalRequests)
        ? studentWithdrawalRequests
        : [];

    const safeStudentPayments = Array.isArray(studentPayments)
        ? studentPayments
        : [];

    // Stats available for future use
    // const stats = {
    //     totalTeachers: totalTeachers ?? 0,
    //     totalEarnings: totalEarnings ?? 0,
    //     pendingPayouts: pendingPayouts ?? 0,
    //     pendingPayoutsAmount: pendingPayoutsAmount ?? 0,
    // };

    return (
        <AdminLayout pageTitle="Payment and wallet System" showRightSidebar={false}>
            <Head title="Payment Management" />

            <div className="px-[20px] py-[8px]">
                <Breadcrumbs
                    breadcrumbs={[
                        { title: 'Dashboard', href: route('admin.dashboard') },
                        { title: 'Payment and wallet System', href: route('admin.financial.dashboard') },
                    ]}
                />
            </div>

            {/* Error Alert */}
            {hasError && (
                <div className="max-w-[1200px] mx-auto mb-4">
                    <Alert variant="destructive">
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>
                            {hasError}
                        </AlertDescription>
                    </Alert>
                </div>
            )}

            <div className="max-w-[1200px] mx-auto">
                {/* Tabs */}
                <div className="bg-white/60 backdrop-blur rounded-[20px] shadow-sm p-[8px] inline-flex gap-[8px]">
                    <Button
                        onClick={() => goTo('teacher-payouts')}
                        className={`rounded-[28px] h-[44px] px-[22px] ${activeTab === 'teacher-payouts'
                            ? 'bg-[#14B8A6] hover:bg-[#129c8e] text-white'
                            : 'bg-transparent text-[#334155] hover:bg-[#F1F5F9]'
                            }`}
                    >
                        Teacher Payouts
                    </Button>
                    {/* <Button
                        onClick={() => goTo('student-withdrawals')}
                        className={`rounded-[28px] h-[44px] px-[22px] ${activeTab === 'student-withdrawals'
                            ? 'bg-[#14B8A6] hover:bg-[#129c8e] text-white'
                            : 'bg-transparent text-[#334155] hover:bg-[#F1F5F9]'
                            }`}
                    >
                        Student Withdrawals
                    </Button> */}
                    <Button
                        onClick={() => goTo('student-payments')}
                        className={`rounded-[28px] h-[44px] px-[22px] ${activeTab === 'student-payments'
                            ? 'bg-[#14B8A6] hover:bg-[#129c8e] text-white'
                            : 'bg-transparent text-[#334155] hover:bg-[#F1F5F9]'
                            }`}
                    >
                        Student Payments
                    </Button>
                    <Button
                        onClick={() => goTo('transactions')}
                        className={`rounded-[28px] h-[44px] px-[22px] ${activeTab === 'transactions'
                            ? 'bg-[#14B8A6] hover:bg-[#129c8e] text-white'
                            : 'bg-transparent text-[#334155] hover:bg-[#F1F5F9]'
                            }`}
                    >
                        Transaction Logs
                    </Button>
                    <Button
                        onClick={() => goTo('settings')}
                        className={`rounded-[28px] h-[44px] px-[22px] ${activeTab === 'settings'
                            ? 'bg-[#14B8A6] hover:bg-[#129c8e] text-white'
                            : 'bg-transparent text-[#334155] hover:bg-[#F1F5F9]'
                            }`}
                    >
                        Payment Settings
                    </Button>
                </div>

                {/* Tab Content */}
                {activeTab === 'teacher-payouts' && (
                    <div>
                        {safePayoutRequests.length === 0 && !hasError ? (
                            <div className="mt-[28px] bg-white rounded-lg border border-gray-200 p-12 text-center">
                                <p className="text-gray-500 text-lg">No pending payout requests at the moment.</p>
                                <p className="text-gray-400 text-sm mt-2">New requests will appear here.</p>
                            </div>
                        ) : (
                            <TeacherPayouts pendingPayoutRequests={safePayoutRequests} />
                        )}
                    </div>
                )}

                {activeTab === 'student-withdrawals' && (
                    <div>
                        {safeWithdrawalRequests.length === 0 && !hasError ? (
                            <div className="mt-[28px] bg-white rounded-lg border border-gray-200 p-12 text-center">
                                <p className="text-gray-500 text-lg">No student withdrawal requests at the moment.</p>
                                <p className="text-gray-400 text-sm mt-2">New requests will appear here.</p>
                            </div>
                        ) : (
                            <StudentWithdrawals withdrawalRequests={safeWithdrawalRequests} />
                        )}
                    </div>
                )}

                {activeTab === 'student-payments' && (
                    <div>
                        {safeStudentPayments.length === 0 && !hasError ? (
                            <div className="mt-[28px] bg-white rounded-lg border border-gray-200 p-12 text-center">
                                <p className="text-gray-500 text-lg">No student payments at the moment.</p>
                                <p className="text-gray-400 text-sm mt-2">New payments will appear here.</p>
                            </div>
                        ) : (
                            <StudentPayments payments={safeStudentPayments} />
                        )}
                    </div>
                )}

                {activeTab === 'transactions' && (
                    <div className="mt-[28px] bg-white rounded-lg border border-gray-200 p-12 text-center">
                        <p className="text-gray-500 text-lg font-medium">Transaction Logs</p>
                        <p className="text-gray-400 text-sm mt-2">This feature is coming soon.</p>
                    </div>
                )}

                {activeTab === 'settings' && (
                    <div className="mt-[28px] bg-white rounded-lg border border-gray-200 p-12 text-center">
                        <p className="text-gray-500 text-lg font-medium">Payment Settings</p>
                        <p className="text-gray-400 text-sm mt-2">This feature is coming soon.</p>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}



