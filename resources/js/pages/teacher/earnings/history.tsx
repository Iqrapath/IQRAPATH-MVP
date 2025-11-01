import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import TeacherLayout from '@/layouts/teacher/teacher-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Calendar, FileText, Filter, DollarSign, TrendingDown } from 'lucide-react';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Calendar as CalendarComponent } from '@/components/ui/calendar';
import { format } from 'date-fns';
import { route } from 'ziggy-js';
import { toast } from 'sonner';
import axios from 'axios';

interface Transaction {
    id: number;
    transaction_uuid: string;
    transaction_type: string;
    amount: number;
    currency: string;
    description: string;
    status: 'pending' | 'completed' | 'failed' | 'cancelled';
    transaction_date: string;
    session?: {
        student: {
            name: string;
        };
        subject: {
            name: string;
        };
    };
}

interface PaginationData {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    data: Transaction[];
}

interface Filters {
    type?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
    currency?: string;
}

export default function TransactionHistory() {
    const pageProps = usePage().props as any;
    const transactions = pageProps.transactions as PaginationData;
    const filters = pageProps.filters as Filters;
    const availableCurrencies = pageProps.availableCurrencies || [];
    const transactionTypes = pageProps.transactionTypes || [];
    const statuses = pageProps.statuses || [];

    const [activeTab, setActiveTab] = useState<'earnings' | 'withdrawals'>('earnings');
    const [localFilters, setLocalFilters] = useState<Filters>(filters);
    const [dateFrom, setDateFrom] = useState<Date | undefined>(() => {
        try {
            return filters.date_from ? new Date(filters.date_from) : undefined;
        } catch {
            return undefined;
        }
    });
    const [dateTo, setDateTo] = useState<Date | undefined>(() => {
        try {
            return filters.date_to ? new Date(filters.date_to) : undefined;
        } catch {
            return undefined;
        }
    });
    const [showFilters, setShowFilters] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [emailingReport, setEmailingReport] = useState(false);

    const formatCurrency = (amount: number, currency: string): string => {
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
            case 'failed':
                return <Badge className="bg-red-100 text-red-800 border-red-200">Failed</Badge>;
            case 'cancelled':
                return <Badge className="bg-gray-100 text-gray-800 border-gray-200">Cancelled</Badge>;
            default:
                return <Badge className="bg-gray-100 text-gray-800 border-gray-200">{status}</Badge>;
        }
    };

    const getTransactionTypeLabel = (type: string): string => {
        const typeLabels: Record<string, string> = {
            'session_payment': 'Session Payment',
            'withdrawal': 'Withdrawal',
            'credit': 'Credit',
            'debit': 'Debit',
            'refund': 'Refund',
            'bonus': 'Bonus',
            'fee': 'Fee',
            'adjustment': 'Adjustment',
        };
        return typeLabels[type] || type;
    };

    const applyFilters = () => {
        setIsLoading(true);
        const params = { ...localFilters };

        if (dateFrom) {
            params.date_from = format(dateFrom, 'yyyy-MM-dd');
        }
        if (dateTo) {
            params.date_to = format(dateTo, 'yyyy-MM-dd');
        }

        router.get(route('teacher.earnings.history'), params, {
            preserveState: true,
            replace: true,
            onFinish: () => setIsLoading(false),
        });
    };

    const clearFilters = () => {
        setIsLoading(true);
        setLocalFilters({});
        setDateFrom(undefined);
        setDateTo(undefined);
        router.get(route('teacher.earnings.history'), {}, {
            preserveState: true,
            replace: true,
            onFinish: () => setIsLoading(false),
        });
    };

    const handlePageChange = (page: number) => {
        setIsLoading(true);
        const params = { ...filters, page };
        router.get(route('teacher.earnings.history'), params, {
            preserveState: true,
            replace: true,
            onFinish: () => setIsLoading(false),
        });
    };

    const handleEmailReport = async () => {
        setEmailingReport(true);
        try {
            const response = await axios.post('/teacher/earnings/email-report', {}, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            if (response.data.success) {
                toast.success('Activity report sent!', {
                    description: 'Check your email for the earnings activity report',
                    duration: 4000,
                });
            }
        } catch (error: any) {
            console.error('Error sending email report:', error);
            const errorMessage = error.response?.data?.message || 'Failed to send email report';
            toast.error('Email failed', {
                description: errorMessage,
                duration: 5000,
            });
        } finally {
            setEmailingReport(false);
        }
    };

    return (
        <TeacherLayout pageTitle="Transaction History">
            <Head title="Transaction History" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Transaction History</h1>
                        <p className="text-gray-600 mt-1">View and manage your transaction history</p>
                    </div>
                </div>

                {/* Filters Card */}
                <Card className="bg-white rounded-lg border border-gray-200 shadow-sm">
                    <CardHeader>
                        <div className="flex justify-between items-center">
                            <CardTitle className="text-lg text-gray-900">Filters</CardTitle>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => setShowFilters(!showFilters)}
                                className="flex items-center gap-2"
                            >
                                <Filter className="h-4 w-4" />
                                {showFilters ? 'Hide Filters' : 'Show Filters'}
                            </Button>
                        </div>
                    </CardHeader>
                    {showFilters && (
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                {/* Transaction Type Filter */}
                                <div>
                                    <label className="text-sm font-medium text-gray-700 mb-2 block">Transaction Type</label>
                                    <Select
                                        value={localFilters.type || 'all'}
                                        onValueChange={(value) => setLocalFilters(prev => ({ ...prev, type: value === 'all' ? undefined : value }))}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="All Types" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Types</SelectItem>
                                            {transactionTypes.map((type: string) => (
                                                <SelectItem key={type} value={type}>
                                                    {getTransactionTypeLabel(type)}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                {/* Status Filter */}
                                <div>
                                    <label className="text-sm font-medium text-gray-700 mb-2 block">Status</label>
                                    <Select
                                        value={localFilters.status || 'all'}
                                        onValueChange={(value) => setLocalFilters(prev => ({ ...prev, status: value === 'all' ? undefined : value }))}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="All Statuses" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Statuses</SelectItem>
                                            {statuses.map((status: string) => (
                                                <SelectItem key={status} value={status}>
                                                    {status.charAt(0).toUpperCase() + status.slice(1)}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                {/* Currency Filter */}
                                <div>
                                    <label className="text-sm font-medium text-gray-700 mb-2 block">Currency</label>
                                    <Select
                                        value={localFilters.currency || 'all'}
                                        onValueChange={(value) => setLocalFilters(prev => ({ ...prev, currency: value === 'all' ? undefined : value }))}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="All Currencies" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Currencies</SelectItem>
                                            {availableCurrencies.map((currency: string) => (
                                                <SelectItem key={currency} value={currency}>
                                                    {currency}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                {/* Date Range */}
                                <div>
                                    <label className="text-sm font-medium text-gray-700 mb-2 block">Date Range</label>
                                    <div className="flex gap-2">
                                        <Popover>
                                            <PopoverTrigger asChild>
                                                <Button variant="outline" className="flex-1 justify-start text-left">
                                                    <Calendar className="mr-2 h-4 w-4" />
                                                    {dateFrom ? format(dateFrom, 'MMM dd') : 'From'}
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent className="w-auto p-0">
                                                <CalendarComponent
                                                    mode="single"
                                                    selected={dateFrom}
                                                    onSelect={setDateFrom}
                                                    initialFocus
                                                />
                                            </PopoverContent>
                                        </Popover>
                                        <Popover>
                                            <PopoverTrigger asChild>
                                                <Button variant="outline" className="flex-1 justify-start text-left">
                                                    <Calendar className="mr-2 h-4 w-4" />
                                                    {dateTo ? format(dateTo, 'MMM dd') : 'To'}
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent className="w-auto p-0">
                                                <CalendarComponent
                                                    mode="single"
                                                    selected={dateTo}
                                                    onSelect={setDateTo}
                                                    initialFocus
                                                />
                                            </PopoverContent>
                                        </Popover>
                                    </div>
                                </div>
                            </div>

                            {/* Filter Actions */}
                            <div className="flex justify-end gap-2 pt-4">
                                <Button variant="outline" onClick={clearFilters} disabled={isLoading}>
                                    Clear Filters
                                </Button>
                                <Button onClick={applyFilters} disabled={isLoading} className="bg-[#338078] hover:bg-[#338078]/80">
                                    {isLoading ? 'Applying...' : 'Apply Filters'}
                                </Button>
                            </div>
                        </CardContent>
                    )}
                </Card>

                {/* Tab Navigation */}
                <div className="flex space-x-1 bg-white p-1 rounded-lg border border-gray-200 w-fit">
                    <Button
                        variant={activeTab === 'earnings' ? 'default' : 'ghost'}
                        onClick={() => setActiveTab('earnings')}
                        className={`px-6 py-2 rounded-md transition-all duration-200 flex items-center gap-2 ${activeTab === 'earnings'
                            ? 'bg-[#338078] text-white shadow-sm'
                            : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
                            }`}
                    >
                        <DollarSign className="h-4 w-4" />
                        Teaching Earnings
                    </Button>
                    <Button
                        variant={activeTab === 'withdrawals' ? 'default' : 'ghost'}
                        onClick={() => setActiveTab('withdrawals')}
                        className={`px-6 py-2 rounded-md transition-all duration-200 flex items-center gap-2 ${activeTab === 'withdrawals'
                            ? 'bg-[#338078] text-white shadow-sm'
                            : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
                            }`}
                    >
                        <TrendingDown className="h-4 w-4" />
                        Withdrawals
                    </Button>
                </div>

                {/* Teaching Earnings Table */}
                {activeTab === 'earnings' && (
                    <Card className="bg-white rounded-lg border border-gray-200 shadow-sm">
                        <CardHeader>
                            <div className="flex justify-between items-center">
                                <CardTitle className="text-lg text-gray-900">Teaching Earnings History</CardTitle>
                                <button
                                    onClick={handleEmailReport}
                                    className="text-[#338078] text-sm font-medium hover:underline flex items-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed"
                                    disabled={emailingReport}
                                >
                                    <FileText className="h-4 w-4" />
                                    {emailingReport ? 'Sending...' : 'Email Activity report'}
                                </button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            {isLoading ? (
                                <div className="text-center text-gray-500 py-12">
                                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#338078] mx-auto mb-4"></div>
                                    <p>Loading earnings...</p>
                                </div>
                            ) : transactions?.data && transactions.data.filter(t => t.transaction_type === 'earning' && t.session).length > 0 ? (
                                <div className="space-y-4">
                                    <div className="overflow-x-auto">
                                        <table className="w-full">
                                            <thead>
                                                <tr className="border-b border-gray-200">
                                                    <th className="text-left py-3 px-4 font-medium text-gray-600">Date</th>
                                                    <th className="text-left py-3 px-4 font-medium text-gray-600">Subject</th>
                                                    <th className="text-left py-3 px-4 font-medium text-gray-600">Student Name</th>
                                                    <th className="text-left py-3 px-4 font-medium text-gray-600">Amount</th>
                                                    <th className="text-left py-3 px-4 font-medium text-gray-600">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {transactions.data
                                                    .filter(t => t.transaction_type === 'earning' && t.session)
                                                    .map((transaction) => (
                                                        <tr key={transaction.id} className="border-b border-gray-100 hover:bg-gray-50">
                                                            <td className="py-3 px-4 text-gray-900">
                                                                {(() => {
                                                                    try {
                                                                        return format(new Date(transaction.transaction_date), 'd MMM yyyy');
                                                                    } catch {
                                                                        return 'Invalid Date';
                                                                    }
                                                                })()}
                                                            </td>
                                                            <td className="py-3 px-4 text-gray-900">
                                                                {transaction.session?.subject?.name || 'General Class'}
                                                            </td>
                                                            <td className="py-3 px-4 text-gray-900">
                                                                {transaction.session?.student?.name || 'Unknown'}
                                                            </td>
                                                            <td className="py-3 px-4 text-green-600 font-semibold">
                                                                +{formatCurrency(transaction.amount, transaction.currency)}
                                                            </td>
                                                            <td className="py-3 px-4">
                                                                {getStatusBadge(transaction.status)}
                                                            </td>
                                                        </tr>
                                                    ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            ) : (
                                <div className="text-center text-gray-500 py-12">
                                    <DollarSign className="h-12 w-12 mx-auto mb-4 text-gray-300" />
                                    <p className="text-lg font-medium">No teaching earnings found</p>
                                    <p className="text-sm">Your earnings from teaching sessions will appear here</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}

                {/* Withdrawals Table */}
                {activeTab === 'withdrawals' && (
                    <Card className="bg-white rounded-lg border border-gray-200 shadow-sm">
                        <CardHeader>
                            <div className="flex justify-between items-center">
                                <CardTitle className="text-lg text-gray-900">Withdrawal History</CardTitle>
                                <button
                                    onClick={handleEmailReport}
                                    className="text-[#338078] text-sm font-medium hover:underline flex items-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed"
                                    disabled={emailingReport}
                                >
                                    <FileText className="h-4 w-4" />
                                    {emailingReport ? 'Sending...' : 'Email Activity report'}
                                </button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            {isLoading ? (
                                <div className="text-center text-gray-500 py-12">
                                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#338078] mx-auto mb-4"></div>
                                    <p>Loading withdrawals...</p>
                                </div>
                            ) : transactions?.data && transactions.data.filter(t => t.transaction_type === 'withdrawal').length > 0 ? (
                                <div className="space-y-4">
                                    <div className="overflow-x-auto">
                                        <table className="w-full">
                                            <thead>
                                                <tr className="border-b border-gray-200">
                                                    <th className="text-left py-3 px-4 font-medium text-gray-600">Date</th>
                                                    <th className="text-left py-3 px-4 font-medium text-gray-600">Description</th>
                                                    <th className="text-left py-3 px-4 font-medium text-gray-600">Transaction ID</th>
                                                    <th className="text-left py-3 px-4 font-medium text-gray-600">Amount</th>
                                                    <th className="text-left py-3 px-4 font-medium text-gray-600">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {transactions.data
                                                    .filter(t => t.transaction_type === 'withdrawal')
                                                    .map((transaction) => (
                                                        <tr key={transaction.id} className="border-b border-gray-100 hover:bg-gray-50">
                                                            <td className="py-3 px-4 text-gray-900">
                                                                {(() => {
                                                                    try {
                                                                        return format(new Date(transaction.transaction_date), 'd MMM yyyy');
                                                                    } catch {
                                                                        return 'Invalid Date';
                                                                    }
                                                                })()}
                                                            </td>
                                                            <td className="py-3 px-4 text-gray-900">
                                                                {transaction.description || 'Withdrawal'}
                                                            </td>
                                                            <td className="py-3 px-4 text-gray-500 text-sm font-mono">
                                                                {transaction.transaction_uuid?.substring(0, 12)}...
                                                            </td>
                                                            <td className="py-3 px-4 text-red-600 font-semibold">
                                                                -{formatCurrency(transaction.amount, transaction.currency)}
                                                            </td>
                                                            <td className="py-3 px-4">
                                                                {getStatusBadge(transaction.status)}
                                                            </td>
                                                        </tr>
                                                    ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            ) : (
                                <div className="text-center text-gray-500 py-12">
                                    <TrendingDown className="h-12 w-12 mx-auto mb-4 text-gray-300" />
                                    <p className="text-lg font-medium">No withdrawals found</p>
                                    <p className="text-sm">Your approved withdrawal requests will appear here</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </TeacherLayout>
    );
}


