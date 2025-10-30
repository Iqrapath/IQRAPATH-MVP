import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import { usePage } from '@inertiajs/react';
import { toast } from 'sonner';
import { FileText, ExternalLink } from 'lucide-react';
import axios from 'axios';
import { route } from 'ziggy-js';

interface EarningsData {
    totalEarnings: number;
    availableBalance: number;
    pendingPayout: number;
    preferredCurrency: string;
    automaticPayouts: boolean;
}

interface UpcomingEarning {
    id: number;
    amount: number;
    amountSecondary: number;
    currency: string;
    secondaryCurrency: string;
    studentName: string;
    dueDate: string;
    status: 'pending' | 'completed' | 'cancelled';
}

interface RecentTransaction {
    id: number;
    date: string;
    subject: string;
    studentName: string;
    amount: number;
    currency?: string;
    status: 'completed' | 'pending' | 'cancelled';
}

export default function Earnings() {
    const pageProps = usePage().props as any;
    const user = pageProps.auth?.user || pageProps.user;

    // Get data from props (passed from controller)
    const walletBalance = pageProps.walletBalance || 0;
    const totalEarned = pageProps.totalEarned || 0;
    const pendingPayouts = pageProps.pendingPayouts || 0;
    const upcomingEarningsData = pageProps.upcomingEarnings || [];
    const recentTransactionsData = pageProps.recentTransactions || [];
    const earningsSettings = pageProps.earningsSettings || { preferredCurrency: 'NGN', automaticPayouts: false };
    const availableCurrencies = Array.isArray(pageProps.availableCurrencies) ? pageProps.availableCurrencies : [
        { value: 'NGN', label: 'Nigerian Naira (NGN)', symbol: '₦', is_default: true },
        { value: 'USD', label: 'US Dollar (USD)', symbol: '$', is_default: false },
        { value: 'EUR', label: 'Euro (EUR)', symbol: '€', is_default: false },
        { value: 'GBP', label: 'British Pound (GBP)', symbol: '£', is_default: false }
    ];
    const teacherRates = pageProps.teacherRates || { hourlyRateUSD: 25, hourlyRateNGN: 37500 };

    const [earningsData, setEarningsData] = useState<EarningsData>({
        totalEarnings: totalEarned,
        availableBalance: walletBalance,
        pendingPayout: pendingPayouts,
        preferredCurrency: earningsSettings.preferredCurrency,
        automaticPayouts: earningsSettings.automaticPayouts
    });

    const [upcomingEarnings, setUpcomingEarnings] = useState<UpcomingEarning[]>(upcomingEarningsData);
    const [recentTransactions, setRecentTransactions] = useState<RecentTransaction[]>(recentTransactionsData);

    const [saving, setSaving] = useState(false);
    const [loading, setLoading] = useState(false);

    // Update data when props change
    useEffect(() => {
        setEarningsData({
            totalEarnings: totalEarned,
            availableBalance: walletBalance,
            pendingPayout: pendingPayouts,
            preferredCurrency: earningsSettings.preferredCurrency,
            automaticPayouts: earningsSettings.automaticPayouts
        });
        setUpcomingEarnings(upcomingEarningsData);
        setRecentTransactions(recentTransactionsData);
    }, [totalEarned, walletBalance, pendingPayouts, earningsSettings, upcomingEarningsData, recentTransactionsData]);

    const handleSaveChanges = async () => {
        if (!user?.id) {
            toast.error('Unable to save earnings settings');
            return;
        }

        setSaving(true);
        try {
            const settingsData = {
                preferred_currency: earningsData.preferredCurrency,
                automatic_payouts: earningsData.automaticPayouts
            };

            await axios.post(`/teacher/earnings/settings`, settingsData, {
                withCredentials: true
            });

            toast.success('Earnings settings saved successfully!', {
                description: 'Your preferences have been updated',
                duration: 4000,
            });
        } catch (error: any) {
            console.error('Error saving earnings settings:', error);
            const errorMessage = error.response?.data?.message || 'Failed to save earnings settings';
            toast.error('Failed to save earnings settings', {
                description: errorMessage,
                duration: 5000,
            });
        } finally {
            setSaving(false);
        }
    };

    const formatCurrency = (amount: number, currency: string = earningsData.preferredCurrency): string => {
        const currencyMap = {
            'NGN': { locale: 'en-NG', currency: 'NGN' },
            'USD': { locale: 'en-US', currency: 'USD' },
            'EUR': { locale: 'en-EU', currency: 'EUR' },
            'GBP': { locale: 'en-GB', currency: 'GBP' },
        };

        const config = currencyMap[currency as keyof typeof currencyMap] || currencyMap['NGN'];

        return new Intl.NumberFormat(config.locale, {
            style: 'currency',
            currency: config.currency,
            minimumFractionDigits: 0,
        }).format(amount);
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'completed':
                return <Badge className="bg-green-100 text-green-800 border-green-200">Completed</Badge>;
            case 'pending':
                return <Badge className="bg-yellow-100 text-yellow-800 border-yellow-200">Pending</Badge>;
            case 'cancelled':
                return <Badge className="bg-red-100 text-red-800 border-red-200">Cancelled</Badge>;
            default:
                return <Badge className="bg-gray-100 text-gray-800 border-gray-200">{status}</Badge>;
        }
    };

    // Check if user exists
    if (!user || !user.id) {
        return (
            <Card className="bg-white rounded-lg border border-gray-200 shadow-sm">
                <CardContent className="p-6">
                    <div className="text-center text-gray-500">
                        <p>Unable to load user information. Please refresh the page.</p>
                    </div>
                </CardContent>
            </Card>
        );
    }

    // No loading state needed since data comes from props

    return (
        <div className="space-y-6">
            {/* Section 1: Manage your earnings and withdraw funds settings */}
            <Card className="bg-white rounded-lg border border-gray-200 shadow-sm">
                <CardHeader>
                    <CardTitle className="text-lg text-gray-900">Manage your earnings and withdraw funds settings</CardTitle>
                </CardHeader>
                <CardContent className="space-y-6">
                    {/* Preferred Currency */}
                    <div className="flex items-center">
                        <div className='mr-8'>
                            <label className="text-sm font-medium text-gray-900">Preferred Currency</label>
                        </div>
                        <Select
                            value={earningsData.preferredCurrency}
                            onValueChange={(value) => setEarningsData(prev => ({ ...prev, preferredCurrency: value }))}
                        >
                            <SelectTrigger className="w-48">
                                <SelectValue placeholder="Select currency" />
                            </SelectTrigger>
                            <SelectContent>
                                {availableCurrencies.map((currency: { value: string; label: string }) => (
                                    <SelectItem key={currency.value} value={currency.value}>
                                        {currency.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {/* Automatic Payouts */}
                    <div className="flex items-center">
                        <div className='mr-8'>
                            <label className="text-sm font-medium text-gray-900">Automatic Payouts</label>
                        </div>
                        <Switch
                            checked={earningsData.automaticPayouts}
                            onCheckedChange={(checked) => setEarningsData(prev => ({ ...prev, automaticPayouts: checked }))}
                            className="data-[state=checked]:bg-[#338078] data-[state=unchecked]:bg-gray-200"
                        />
                    </div>

                    {/* Save Changes Button */}
                    <div className="flex justify-end pt-4">
                        <Button
                            onClick={handleSaveChanges}
                            disabled={saving}
                            className="bg-[#338078] hover:bg-[#338078]/80 text-white px-6 py-2 rounded-full disabled:opacity-50"
                        >
                            {saving ? 'Saving...' : 'Save Changes'}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            {/* Section 2: Earnings Summary */}
            <Card className="bg-white rounded-lg border border-gray-200 shadow-sm">
                <CardContent className="p-6">
                    <div className="flex justify-between items-center mb-6">
                        <h3 className="text-lg font-semibold text-gray-900">Earnings Summary</h3>
                        <a href={route('teacher.earnings.history')} className="text-[#338078] text-sm font-medium hover:underline flex items-center gap-1">
                            Check Transaction History
                        </a>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        {/* Total Earnings */}
                        <div className="bg-gradient-to-l from-purple-50 rounded-full p-4">
                            <p className="text-sm text-gray-600 mb-1">Total Earnings:</p>
                            <p className="text-2xl font-bold text-gray-900">{formatCurrency(earningsData.totalEarnings, earningsData.preferredCurrency)}</p>
                        </div>

                        {/* Available Balance */}
                        <div className="bg-gradient-to-l from-green-50 rounded-full p-4">
                            <p className="text-sm text-gray-600 mb-1">Available Balance:</p>
                            <p className="text-2xl font-bold text-gray-900">{formatCurrency(earningsData.availableBalance, earningsData.preferredCurrency)}</p>
                        </div>

                        {/* Pending Payout */}
                        <div className="bg-gradient-to-l from-yellow-50 rounded-full p-4">
                            <p className="text-sm text-gray-600 mb-1">Pending Payout:</p>
                            <p className="text-2xl font-bold text-gray-900">{formatCurrency(earningsData.pendingPayout, earningsData.preferredCurrency)}</p>
                        </div>
                    </div>

                    {/* Withdraw Fund Button */}
                    <div className="flex justify-center">
                        <Button className="bg-[#338078] hover:bg-[#338078]/80 text-white px-8 py-3 rounded-full">
                            Withdraw Fund
                        </Button>
                    </div>
                </CardContent>
            </Card>

            {/* Section 3: Upcoming Earning Due */}
            <Card className="bg-white rounded-lg border border-gray-200 shadow-sm">
                <CardHeader>
                    <CardTitle className="text-lg text-gray-900">Upcoming Earning Due</CardTitle>
                </CardHeader>
                <CardContent>
                    {upcomingEarnings.length > 0 ? (
                        upcomingEarnings.map((earning) => (
                            <div key={earning.id} className="flex items-center justify-between p-4 bg-gray-50 rounded-full">
                                <div className="flex-1">
                                    <div className="flex items-center space-x-6">
                                        <div>
                                            <p className="text-sm text-gray-600">Amount</p>
                                            <p className="font-semibold text-gray-900">
                                                {formatCurrency(earning.amount, earning.currency)} / {formatCurrency(earning.amountSecondary, earning.secondaryCurrency)}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-600">Student</p>
                                            <p className="font-semibold text-gray-900">{earning.studentName}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-600">Due Date</p>
                                            <p className="font-semibold text-gray-900">{earning.dueDate}</p>
                                        </div>
                                    </div>
                                </div>
                                <div className="ml-4">
                                    {getStatusBadge(earning.status)}
                                </div>
                            </div>
                        ))
                    ) : (
                        <div className="text-center text-gray-500 py-8">
                            <p>No upcoming earnings at the moment</p>
                        </div>
                    )}
                </CardContent>
            </Card>

            {/* Section 4: Recent Transaction */}
            <Card className="bg-white rounded-lg border border-gray-200 shadow-sm">
                <CardHeader>
                    <div className="flex justify-between items-center">
                        <CardTitle className="text-lg text-gray-900">Recent Transaction</CardTitle>
                        <a href="#" className="text-[#338078] text-sm font-medium hover:underline flex items-center gap-1">
                            <FileText className="h-4 w-4" />
                            Email Activity report
                        </a>
                    </div>
                </CardHeader>
                <CardContent>
                    {recentTransactions.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b border-gray-200">
                                        <th className="text-left py-3 px-4 font-medium text-gray-600">Date</th>
                                        <th className="text-left py-3 px-4 font-medium text-gray-600">Subject</th>
                                        <th className="text-left py-3 px-4 font-medium text-gray-600">Name</th>
                                        <th className="text-left py-3 px-4 font-medium text-gray-600">Amount</th>
                                        <th className="text-left py-3 px-4 font-medium text-gray-600">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {recentTransactions.map((transaction) => (
                                        <tr key={transaction.id} className="border-b border-gray-100">
                                            <td className="py-3 px-4 text-gray-900">{transaction.date}</td>
                                            <td className="py-3 px-4 text-gray-900">{transaction.subject}</td>
                                            <td className="py-3 px-4 text-gray-900">{transaction.studentName}</td>
                                            <td className="py-3 px-4 text-gray-900 font-semibold">{formatCurrency(transaction.amount, transaction.currency || earningsData.preferredCurrency)}</td>
                                            <td className="py-3 px-4">
                                                {getStatusBadge(transaction.status)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="text-center text-gray-500 py-8">
                            <p>No recent transactions found</p>
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
