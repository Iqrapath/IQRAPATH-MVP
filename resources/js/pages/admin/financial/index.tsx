import { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin/admin-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertCircle } from 'lucide-react';
import TeacherPayouts from './components/TeacherPayouts';
import StudentWithdrawals from './components/StudentWithdrawals';
import StudentPayments from './components/StudentPayments';
import TransactionLogs from './components/TransactionLogs';
import PaymentSettings from './components/PaymentSettings';
import WithdrawalLimits from './components/WithdrawalLimits';
import PaymentMethodsSettings from './components/PaymentMethodsSettings';
import CurrencySettings from './components/CurrencySettings';

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

interface TransactionRow {
    id: number;
    date: string;
    user_name: string;
    user_email?: string;
    description: string;
    amount: number;
    currency?: string;
    status: 'completed' | 'pending' | 'platform_earned' | 'failed' | string;
    transaction_type?: string;
}

interface PaymentSettingsData {
    commission_rate: number;
    commission_type: 'fixed_percentage' | 'tiered';
    auto_payout_threshold: number;
    minimum_withdrawal_amount: number;
    bank_verification_enabled: boolean;
    withdrawal_note: string;
}

interface WithdrawalLimitsData {
    daily_withdrawal_limit: number;
    monthly_withdrawal_limit: number;
    instant_payouts_enabled: boolean;
}

interface PaymentMethodsData {
    bank_transfer_fee_type: string;
    bank_transfer_fee_amount: number;
    bank_transfer_processing_time: string;
    mobile_money_fee_type: string;
    mobile_money_fee_amount: number;
    mobile_money_processing_time: string;
    paypal_fee_type: string;
    paypal_fee_amount: number;
    paypal_processing_time: string;
    flutterwave_fee_type: string;
    flutterwave_fee_amount: number;
    flutterwave_processing_time: string;
    paystack_fee_type: string;
    paystack_fee_amount: number;
    paystack_processing_time: string;
    stripe_fee_type: string;
    stripe_fee_amount: number;
    stripe_processing_time: string;
}

interface CurrencySettingsData {
    platform_currency: string;
    multi_currency_mode: boolean;
}

interface Props {
    totalTeachers?: number;
    totalEarnings?: number;
    pendingPayouts?: number;
    pendingPayoutsAmount?: number;
    pendingPayoutRequests?: PayoutRequestRow[];
    studentWithdrawalRequests?: StudentWithdrawalRow[];
    studentPayments?: StudentPaymentRow[];
    transactions?: TransactionRow[];
    paymentSettings?: PaymentSettingsData;
    withdrawalLimits?: WithdrawalLimitsData;
    paymentMethods?: PaymentMethodsData;
    currencySettings?: CurrencySettingsData;
    error?: string;
}

export default function FinancialIndex({
    pendingPayoutRequests = [],
    studentWithdrawalRequests = [],
    studentPayments = [],
    transactions = [],
    paymentSettings,
    withdrawalLimits,
    paymentMethods,
    currencySettings,
    error
}: Props) {
    // Get initial tab from URL params
    const getInitialTab = () => {
        const params = new URLSearchParams(window.location.search);
        const tabParam = params.get('tab');
        const validTabs = ['teacher-payouts', 'student-withdrawals', 'student-payments', 'transactions', 'settings', 'withdrawal-limits', 'payment-methods', 'currency'];
        if (tabParam && validTabs.includes(tabParam)) {
            return tabParam;
        }
        return 'teacher-payouts';
    };

    type TabType = 'teacher-payouts' | 'student-withdrawals' | 'student-payments' | 'transactions' | 'settings' | 'withdrawal-limits' | 'payment-methods' | 'currency';
    const [activeTab, setActiveTab] = useState<TabType>(getInitialTab() as TabType);
    const [hasError, setHasError] = useState<string | null>(null);

    // Handle error from backend
    useEffect(() => {
        if (error) {
            setHasError(error);
        }
    }, [error]);

    const goTo = (tab: TabType) => {
        try {
            setActiveTab(tab);
            // Update URL with tab parameter
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            url.searchParams.delete('page'); // Reset page when switching tabs
            window.history.pushState({}, '', url);
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

    // Handle both paginated and array transactions
    const safeTransactions = transactions && typeof transactions === 'object' && 'data' in transactions
        ? transactions
        : Array.isArray(transactions)
            ? transactions
            : [];

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
                        Commission
                    </Button>
                    <Button
                        onClick={() => goTo('withdrawal-limits')}
                        className={`rounded-[28px] h-[44px] px-[22px] ${activeTab === 'withdrawal-limits'
                            ? 'bg-[#14B8A6] hover:bg-[#129c8e] text-white'
                            : 'bg-transparent text-[#334155] hover:bg-[#F1F5F9]'
                            }`}
                    >
                        Limits
                    </Button>
                    <Button
                        onClick={() => goTo('payment-methods')}
                        className={`rounded-[28px] h-[44px] px-[22px] ${activeTab === 'payment-methods'
                            ? 'bg-[#14B8A6] hover:bg-[#129c8e] text-white'
                            : 'bg-transparent text-[#334155] hover:bg-[#F1F5F9]'
                            }`}
                    >
                        Methods
                    </Button>
                    <Button
                        onClick={() => goTo('currency')}
                        className={`rounded-[28px] h-[44px] px-[22px] ${activeTab === 'currency'
                            ? 'bg-[#14B8A6] hover:bg-[#129c8e] text-white'
                            : 'bg-transparent text-[#334155] hover:bg-[#F1F5F9]'
                            }`}
                    >
                        Currency
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
                    <div>
                        {((Array.isArray(safeTransactions) && safeTransactions.length === 0) || 
                          (typeof safeTransactions === 'object' && 'data' in safeTransactions && Array.isArray((safeTransactions as any).data) && (safeTransactions as any).data.length === 0)) && !hasError ? (
                            <div className="mt-[28px] bg-white rounded-lg border border-gray-200 p-12 text-center">
                                <p className="text-gray-500 text-lg">No transactions at the moment.</p>
                                <p className="text-gray-400 text-sm mt-2">New transactions will appear here.</p>
                            </div>
                        ) : (
                            <TransactionLogs transactions={safeTransactions as any} />
                        )}
                    </div>
                )}

                {activeTab === 'settings' && (
                    <PaymentSettings settings={paymentSettings} />
                )}

                {activeTab === 'withdrawal-limits' && (
                    <WithdrawalLimits settings={withdrawalLimits} />
                )}

                {activeTab === 'payment-methods' && (
                    <PaymentMethodsSettings settings={paymentMethods} />
                )}

                {activeTab === 'currency' && (
                    <CurrencySettings settings={currencySettings} />
                )}
            </div>
        </AdminLayout>
    );
}



