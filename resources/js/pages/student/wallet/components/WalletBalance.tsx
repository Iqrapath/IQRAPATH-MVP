import { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { usePage, router } from '@inertiajs/react';
import { toast } from 'sonner';
import { FileText } from 'lucide-react';
import axios from 'axios';
import FundAccountModal from '../../../../components/student/FundAccountModal';
import WithdrawFundModal from '../../../../components/student/WithdrawFundModal';
import WithdrawalHistory from './WithdrawalHistory';

interface WalletData {
    balance: number;
    totalSpent: number;
    totalRefunded: number;
    preferredCurrency: string;
}

interface UpcomingPayment {
    id: number;
    amount: number;
    amountSecondary: number;
    currency: string;
    secondaryCurrency: string;
    teacherName: string;
    dueDate: string;
    status: 'pending' | 'completed' | 'cancelled';
}

interface PaymentHistory {
    id: number;
    date: string;
    subject: string;
    teacherName: string;
    amount: number;
    currency?: string;
    status: 'completed' | 'pending' | 'cancelled';
}

export default function WalletBalance() {
    const pageProps = usePage().props as any;
    const user = pageProps.auth?.user || pageProps.user;

    // Get data from props (passed from controller)
    const walletBalance = pageProps.walletBalance || 0;
    const totalSpent = pageProps.totalSpent || 0;
    const totalRefunded = pageProps.totalRefunded || 0;
    const upcomingPaymentsData = pageProps.upcomingPayments || [];
    const paymentHistoryData = pageProps.paymentHistory || [];
    const walletSettings = pageProps.walletSettings || { preferredCurrency: 'NGN' };
    const availableCurrencies = Array.isArray(pageProps.availableCurrencies) ? pageProps.availableCurrencies : [
        { value: 'NGN', label: 'Nigerian Naira (NGN)', symbol: '₦', is_default: true },
        { value: 'USD', label: 'US Dollar (USD)', symbol: '$', is_default: false },
    ];

    const [walletData, setWalletData] = useState<WalletData>({
        balance: walletBalance,
        totalSpent: totalSpent,
        totalRefunded: totalRefunded,
        preferredCurrency: walletSettings.preferredCurrency,
    });

    const [upcomingPayments, setUpcomingPayments] = useState<UpcomingPayment[]>(upcomingPaymentsData);
    const [paymentHistory, setPaymentHistory] = useState<PaymentHistory[]>(paymentHistoryData);

    const [saving, setSaving] = useState(false);
    const [showFundModal, setShowFundModal] = useState(false);
    const [showWithdrawModal, setShowWithdrawModal] = useState(false);
    const [emailingReport, setEmailingReport] = useState(false);

    // Update data when props change
    useEffect(() => {
        setWalletData({
            balance: walletBalance,
            totalSpent: totalSpent,
            totalRefunded: totalRefunded,
            preferredCurrency: walletSettings.preferredCurrency,
        });
        setUpcomingPayments(upcomingPaymentsData);
        setPaymentHistory(paymentHistoryData);
    }, [walletBalance, totalSpent, totalRefunded, walletSettings, upcomingPaymentsData, paymentHistoryData]);

    const handleSaveChanges = async () => {
        if (!user?.id) {
            toast.error('Unable to save wallet settings');
            return;
        }

        setSaving(true);
        try {
            const settingsData = {
                preferred_currency: walletData.preferredCurrency,
            };

            await axios.post(`/student/wallet/settings`, settingsData, {
                withCredentials: true
            });

            toast.success('Wallet settings saved successfully!', {
                description: 'Your preferences have been updated',
                duration: 4000,
            });
        } catch (error: any) {
            console.error('Error saving wallet settings:', error);
            const errorMessage = error.response?.data?.message || 'Failed to save wallet settings';
            toast.error('Failed to save wallet settings', {
                description: errorMessage,
                duration: 5000,
            });
        } finally {
            setSaving(false);
        }
    };

    const handleTopUpClick = () => {
        setShowFundModal(true);
    };

    const handleWithdrawClick = () => {
        setShowWithdrawModal(true);
    };

    const handleWithdrawalSuccess = () => {
        setShowWithdrawModal(false);
        // Refresh wallet balance and page data
        router.reload({ only: ['walletBalance', 'availableWithdrawalBalance', 'upcomingPayments', 'paymentHistory'] });
    };

    const handleEmailReport = async () => {
        setEmailingReport(true);
        try {
            await axios.post('/student/wallet/email-report', {}, {
                withCredentials: true
            });

            toast.success('Activity report sent!', {
                description: 'Check your email for the payment activity report',
                duration: 4000,
            });
        } catch (error: any) {
            console.error('Error emailing report:', error);
            toast.error('Failed to send report', {
                description: 'Please try again later',
                duration: 5000,
            });
        } finally {
            setEmailingReport(false);
        }
    };

    const handlePayNow = (paymentId: number) => {
        // Navigate to payment page or open payment modal
        router.visit(`/student/payments/${paymentId}`);
    };

    const handleCheckHistory = () => {
        router.visit('/student/wallet/history');
    };

    // Get currency symbol
    const getCurrencySymbol = (currency: string) => {
        const curr = availableCurrencies.find((c: any) => c.value === currency);
        return curr?.symbol || currency;
    };

    // Format amount with currency
    const formatAmount = (amount: number, currency?: string): string => {
        const curr = currency || walletData.preferredCurrency;
        const symbol = getCurrencySymbol(curr);
        return `${symbol}${amount.toLocaleString()}`;
    };

    // Format date
    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
    };

    // Get status badge color
    const getStatusColor = (status: string): string => {
        switch (status) {
            case 'completed':
                return 'bg-green-100 text-green-800';
            case 'pending':
                return 'bg-yellow-100 text-yellow-800';
            case 'cancelled':
                return 'bg-red-100 text-red-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    return (
        <div className="space-y-6">
            {/* Wallet Balance Card */}
            <Card className="bg-white border border-gray-200">
                <CardContent className="p-6">
                    <div className="flex items-center justify-between mb-4">
                        <div className="flex items-center gap-4">
                            <span className="text-sm font-medium text-gray-700">Preferred Currency</span>
                            <Select
                                value={walletData.preferredCurrency}
                                onValueChange={(value) => {
                                    setWalletData({ ...walletData, preferredCurrency: value });
                                    handleSaveChanges();
                                }}
                            >
                                <SelectTrigger className="w-[95px] bg-white border-gray-300 rounded-lg">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {availableCurrencies.map((currency: any) => (
                                        <SelectItem key={currency.value} value={currency.value}>
                                            {currency.value}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <Button
                            variant="link"
                            className="text-[#2C7870] hover:text-[#235f59]"
                            onClick={handleCheckHistory}
                        >
                            Check Payment History
                        </Button>
                    </div>

                    {/* Balance Display */}
                    <div className="flex flex-col items-center justify-center py-8">
                        <div className='flex flex-col items-center bg-gradient-to-l from-[#C0B7E8] rounded-[40px] p-4 pr-10 pl-10 mb-4'>
                            <span className="text-md text-gray-600 mb-2">Wallet Balance</span>
                            <div className="text-4xl font-bold text-gray-900">
                                {formatAmount(walletData.balance)}
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button
                                onClick={handleTopUpClick}
                                className="bg-[#2C7870] hover:bg-[#235f59] text-white px-6 rounded-full cursor-pointer"
                            >
                                Top Up Balance
                            </Button>
                            {/* <Button
                                onClick={handleWithdrawClick}
                                variant="ghost"
                                className="text-gray-700 hover:bg-gray-50 cursor-pointer"
                            >
                                Withdraw Fund
                            </Button> */}
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Upcoming Payments Due */}
            <Card className="bg-white border border-gray-200">
                <CardHeader>
                    <CardTitle className="text-xl font-bold text-gray-900">Upcoming Payments Due</CardTitle>
                </CardHeader>
                <CardContent>
                    {upcomingPayments.length === 0 ? (
                        <div className="text-center py-8 text-gray-500">
                            No upcoming payments
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {upcomingPayments.map((payment) => (
                                <div
                                    key={payment.id}
                                    className="flex items-center justify-between p-4 bg-gray-50 rounded-lg"
                                >
                                    <div className="flex items-center gap-6">
                                        <div>
                                            <div className="text-lg font-semibold text-gray-900">
                                                {formatAmount(payment.amount, payment.currency)} / {formatAmount(payment.amountSecondary, payment.secondaryCurrency)}
                                            </div>
                                        </div>
                                        <div>
                                            <div className="text-sm text-gray-600">Teacher</div>
                                            <div className="text-sm font-medium text-gray-900">{payment.teacherName}</div>
                                        </div>
                                        <div>
                                            <div className="text-sm text-gray-600">Due Date</div>
                                            <div className="text-sm font-medium text-gray-900">{formatDate(payment.dueDate)}</div>
                                        </div>
                                        <div>
                                            <div className="text-sm text-gray-600">Status</div>
                                            <Badge className={getStatusColor(payment.status)}>
                                                {payment.status.charAt(0).toUpperCase() + payment.status.slice(1)}
                                            </Badge>
                                        </div>
                                    </div>
                                    <Button
                                        onClick={() => handlePayNow(payment.id)}
                                        className="bg-[#2C7870] hover:bg-[#235f59] text-white"
                                    >
                                        Pay Now
                                    </Button>
                                </div>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>

            {/* Payment History */}
            <Card className="bg-white border border-gray-200">
                <CardHeader className="flex flex-row items-center justify-between">
                    <CardTitle className="text-xl font-bold text-gray-900">Payment History</CardTitle>
                    <Button
                        variant="link"
                        className="text-[#2C7870] hover:text-[#235f59] flex items-center gap-2"
                        onClick={handleEmailReport}
                        disabled={emailingReport}
                    >
                        <FileText className="w-4 h-4" />
                        {emailingReport ? 'Sending...' : 'Email Activity report'}
                    </Button>
                </CardHeader>
                <CardContent>
                    {paymentHistory.length === 0 ? (
                        <div className="text-center py-8 text-gray-500">
                            No payment history yet
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b border-gray-200">
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-600">Date</th>
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-600">Subject</th>
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-600">Name</th>
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-600">Amount</th>
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-600">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {paymentHistory.map((transaction) => (
                                        <tr key={transaction.id} className="border-b border-gray-100 hover:bg-gray-50">
                                            <td className="py-4 px-4">
                                                <div className="text-2xl font-bold text-gray-900">
                                                    {new Date(transaction.date).getDate()}
                                                </div>
                                                <div className="text-xs text-gray-600">
                                                    {new Date(transaction.date).toLocaleDateString('en-US', { month: 'short' })}
                                                </div>
                                            </td>
                                            <td className="py-4 px-4 text-sm text-gray-900">{transaction.subject}</td>
                                            <td className="py-4 px-4 text-sm text-gray-900">{transaction.teacherName}</td>
                                            <td className="py-4 px-4 text-sm font-semibold text-gray-900">
                                                {formatAmount(transaction.amount, transaction.currency)}
                                            </td>
                                            <td className="py-4 px-4">
                                                <Badge className={getStatusColor(transaction.status)}>
                                                    {transaction.status.charAt(0).toUpperCase() + transaction.status.slice(1)}
                                                </Badge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </CardContent>
            </Card>

            {/* Withdrawal History */}
            <WithdrawalHistory />

            {/* Fund Account Modal */}
            {showFundModal && (
                <FundAccountModal
                    isOpen={showFundModal}
                    onClose={() => setShowFundModal(false)}
                    onPayment={() => {
                        setShowFundModal(false);
                        router.reload();
                    }}
                />
            )}

            {/* Withdraw Fund Modal */}
            {showWithdrawModal && (
                <WithdrawFundModal
                    isOpen={showWithdrawModal}
                    onClose={() => setShowWithdrawModal(false)}
                    onSuccess={handleWithdrawalSuccess}
                    walletBalance={walletData.balance}
                    availableWithdrawalBalance={pageProps.availableWithdrawalBalance || walletData.balance}
                    currency={getCurrencySymbol(walletData.preferredCurrency)}
                    user={user}
                />
            )}
        </div>
    );
}
