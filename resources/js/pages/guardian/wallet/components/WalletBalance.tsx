import { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { usePage, router } from '@inertiajs/react';
import { toast } from 'sonner';
import { FileText } from 'lucide-react';
import axios from 'axios';
import FundAccountModal from '../../../../components/guardian/FundAccountModal';
import { useCurrency } from '@/contexts/CurrencyContext';

interface WalletData {
    balance: number;
    totalSpentOnChildren: number;
    totalRefunded: number;
    preferredCurrency: string;
}

interface UpcomingPayment {
    id: number;
    childName?: string;
    childId?: number;
    amount: number;
    amountSecondary: number;
    currency: string;
    secondaryCurrency: string;
    teacherName: string;
    subjectName?: string;
    dueDate: string;
    status: 'pending' | 'completed' | 'cancelled';
}

interface PaymentHistory {
    id: number;
    date: string;
    description: string;
    type: string;
    amount: number;
    currency?: string;
    status: 'completed' | 'pending' | 'cancelled';
}

interface WalletBalanceProps {
    onViewHistory?: () => void;
}

export default function WalletBalance({ onViewHistory }: WalletBalanceProps = {}) {
    const pageProps = usePage().props as any;
    const user = pageProps.auth?.user || pageProps.user;
    const { formatBalance: formatBalanceFromContext, selectedCurrency, setSelectedCurrency } = useCurrency();

    // Get data from props (passed from controller)
    const walletBalance = pageProps.walletBalance || 0;
    const totalSpentOnChildren = pageProps.totalSpentOnChildren || 0;
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
        totalSpentOnChildren: totalSpentOnChildren,
        totalRefunded: totalRefunded,
        preferredCurrency: walletSettings.preferredCurrency,
    });

    const [upcomingPayments, setUpcomingPayments] = useState<UpcomingPayment[]>(upcomingPaymentsData);
    const [paymentHistory, setPaymentHistory] = useState<PaymentHistory[]>(paymentHistoryData);

    const [saving, setSaving] = useState(false);
    const [showFundModal, setShowFundModal] = useState(false);
    const [emailingReport, setEmailingReport] = useState(false);

    // Update data when props change
    useEffect(() => {
        setWalletData({
            balance: walletBalance,
            totalSpentOnChildren: totalSpentOnChildren,
            totalRefunded: totalRefunded,
            preferredCurrency: walletSettings.preferredCurrency,
        });
        setUpcomingPayments(upcomingPaymentsData);
        setPaymentHistory(paymentHistoryData);
    }, [walletBalance, totalSpentOnChildren, totalRefunded, walletSettings, upcomingPaymentsData, paymentHistoryData]);

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

            await axios.post(`/guardian/wallet/settings`, settingsData, {
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

    const handleEmailReport = async () => {
        setEmailingReport(true);
        try {
            await axios.post('/guardian/wallet/email-report', {}, {
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

    const handleCheckHistory = () => {
        if (onViewHistory) {
            onViewHistory();
        }
    };

    // Get currency symbol
    const getCurrencySymbol = (currency: string) => {
        const curr = availableCurrencies.find((c: any) => c.value === currency);
        return curr?.symbol || currency;
    };

    // Format amount with currency (consistent with header - no decimals for NGN)
    const formatAmount = (amount: number, currency?: string): string => {
        const curr = currency || walletData.preferredCurrency;
        const symbol = getCurrencySymbol(curr);
        // Use same formatting as CurrencyContext - no decimals for NGN
        return `${symbol}${amount.toLocaleString(undefined, {
            minimumFractionDigits: curr === 'NGN' ? 0 : 2,
            maximumFractionDigits: curr === 'NGN' ? 0 : 2
        })}`;
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
                                value={selectedCurrency}
                                onValueChange={(value) => {
                                    setSelectedCurrency(value);
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
                                {formatBalanceFromContext(walletData.balance)}
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button
                                onClick={handleTopUpClick}
                                className="bg-[#2C7870] hover:bg-[#235f59] text-white px-6 rounded-full cursor-pointer"
                            >
                                Top Up Balance
                            </Button>
                        </div>
                    </div>

                    {/* Family Spending Summary */}
                    {totalSpentOnChildren > 0 && (
                        <div className="mt-6 pt-6 border-t border-gray-200">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <span className="text-sm text-gray-600">Total Spent on Children</span>
                                    <div className="text-lg font-semibold text-gray-900">
                                        {formatBalanceFromContext(totalSpentOnChildren)}
                                    </div>
                                </div>
                                <div>
                                    <span className="text-sm text-gray-600">Total Refunded</span>
                                    <div className="text-lg font-semibold text-gray-900">
                                        {formatBalanceFromContext(totalRefunded)}
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
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
                                            {payment.childName && (
                                                <div className="text-xs text-gray-500 mt-1">
                                                    For: {payment.childName}
                                                </div>
                                            )}
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
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-600">Description</th>
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-600">Type</th>
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
                                            <td className="py-4 px-4 text-sm text-gray-900">{transaction.description}</td>
                                            <td className="py-4 px-4 text-sm text-gray-900 capitalize">{transaction.type.replace('_', ' ')}</td>
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
            {/* <WithdrawalHistory /> */}

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
        </div>
    );
}

