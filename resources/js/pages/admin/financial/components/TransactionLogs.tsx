import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { Search } from 'lucide-react';

/**
 * 🎨 FIGMA DESIGN REFERENCE
 * 
 * Component: Transaction Logs
 * Design Pattern: Matches Withdrawal Request design
 * 
 * 📏 EXACT SPECIFICATIONS:
 * - Search input: height 40px, rounded-full, padding-x 16px
 * - Filter dropdowns: height 40px, rounded-lg
 * - Search button: width 84px, height 40px, teal background
 * - Table header: background #F6F7F9
 * - Table row: height 64px, padding-x 20px
 * - Status badges: Completed (green), Pending (orange), Platform Earned (teal)
 */

interface Transaction {
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

interface PaginatedTransactions {
    data: Transaction[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

interface Props {
    transactions: PaginatedTransactions | Transaction[];
}

export default function TransactionLogs({ transactions = [] }: Props) {
    const [searchQuery, setSearchQuery] = useState('');
    const [statusFilter, setStatusFilter] = useState<string>('all');
    const [paymentMethodFilter, setPaymentMethodFilter] = useState<string>('all');
    const [dateRangeFilter, setDateRangeFilter] = useState<string>('all');
    const [selectedTransactions, setSelectedTransactions] = useState<number[]>([]);

    // Handle both paginated and non-paginated data
    const isPaginated = transactions && typeof transactions === 'object' && 'data' in transactions;
    const transactionData = isPaginated ? (transactions as PaginatedTransactions).data : (transactions as Transaction[]);
    const paginationInfo = isPaginated ? (transactions as PaginatedTransactions) : null;

    const handleSelectAll = (checked: boolean) => {
        if (checked) {
            setSelectedTransactions(filteredTransactions.map(t => t.id));
        } else {
            setSelectedTransactions([]);
        }
    };

    const handleSelectTransaction = (id: number, checked: boolean) => {
        if (checked) {
            setSelectedTransactions([...selectedTransactions, id]);
        } else {
            setSelectedTransactions(selectedTransactions.filter(tid => tid !== id));
        }
    };

    // Filter transactions
    const filteredTransactions = transactionData.filter(transaction => {
        const matchesSearch = searchQuery === '' ||
            transaction.user_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
            transaction.user_email?.toLowerCase().includes(searchQuery.toLowerCase());

        const matchesStatus = statusFilter === 'all' || transaction.status === statusFilter;

        return matchesSearch && matchesStatus;
    });

    const getStatusBadge = (status: string) => {
        switch (status.toLowerCase()) {
            case 'completed':
                return <Badge className="bg-green-100 text-green-700 hover:bg-green-100">Completed</Badge>;
            case 'pending':
                return <Badge className="bg-orange-100 text-orange-700 hover:bg-orange-100">Pending</Badge>;
            case 'platform_earned':
                return <Badge className="bg-teal-100 text-teal-700 hover:bg-teal-100">Platform Earned</Badge>;
            case 'failed':
                return <Badge className="bg-red-100 text-red-700 hover:bg-red-100">Failed</Badge>;
            default:
                return <Badge variant="secondary">{status}</Badge>;
        }
    };

    const formatCurrency = (amount: number, currency: string = 'NGN') => {
        return new Intl.NumberFormat('en-NG', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 0,
        }).format(amount);
    };

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { month: 'short', day: '2-digit' });
    };

    return (
        <div className="mt-[28px]">
            {/* Filters */}
            <div className="flex items-center gap-[16px] mb-[24px]">
                {/* Search Input */}
                <div className="relative flex-1 max-w-[400px]">
                    <Search className="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                    <Input
                        type="text"
                        placeholder="Search by Name / Email"
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        className="h-[40px] rounded-full pl-10 pr-4 border-gray-300"
                    />
                </div>

                {/* Status Filter */}
                <Select value={statusFilter} onValueChange={setStatusFilter}>
                    <SelectTrigger className="w-[160px] h-[40px] rounded-lg">
                        <SelectValue placeholder="Select Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Status</SelectItem>
                        <SelectItem value="completed">Completed</SelectItem>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="platform_earned">Platform Earned</SelectItem>
                        <SelectItem value="failed">Failed</SelectItem>
                    </SelectContent>
                </Select>

                {/* Payment Method Filter */}
                <Select value={paymentMethodFilter} onValueChange={setPaymentMethodFilter}>
                    <SelectTrigger className="w-[180px] h-[40px] rounded-lg">
                        <SelectValue placeholder="Payment Method" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Methods</SelectItem>
                        <SelectItem value="bank_transfer">Bank Transfer</SelectItem>
                        <SelectItem value="paystack">Paystack</SelectItem>
                        <SelectItem value="wallet">Wallet</SelectItem>
                    </SelectContent>
                </Select>

                {/* Date Range Filter */}
                <Select value={dateRangeFilter} onValueChange={setDateRangeFilter}>
                    <SelectTrigger className="w-[160px] h-[40px] rounded-lg">
                        <SelectValue placeholder="Date Range" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Time</SelectItem>
                        <SelectItem value="today">Today</SelectItem>
                        <SelectItem value="week">This Week</SelectItem>
                        <SelectItem value="month">This Month</SelectItem>
                    </SelectContent>
                </Select>

                {/* Search Button */}
                <Button className="w-[84px] h-[40px] bg-[#14B8A6] hover:bg-[#129c8e] text-white rounded-lg">
                    Search
                </Button>
            </div>

            {/* Table */}
            <div className="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <table className="w-full">
                    <thead className="bg-[#F6F7F9]">
                        <tr className="h-[56px]">
                            <th className="px-[20px] text-left">
                                <Checkbox
                                    checked={selectedTransactions.length === filteredTransactions.length && filteredTransactions.length > 0}
                                    onCheckedChange={handleSelectAll}
                                />
                            </th>
                            <th className="px-[20px] text-left text-sm font-medium text-gray-700">Date</th>
                            <th className="px-[20px] text-left text-sm font-medium text-gray-700">User Name</th>
                            <th className="px-[20px] text-left text-sm font-medium text-gray-700">Description</th>
                            <th className="px-[20px] text-right text-sm font-medium text-gray-700">Amount</th>
                            <th className="px-[20px] text-center text-sm font-medium text-gray-700">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {filteredTransactions.length === 0 ? (
                            <tr>
                                <td colSpan={6} className="px-[20px] py-12 text-center text-gray-500">
                                    No transactions found
                                </td>
                            </tr>
                        ) : (
                            filteredTransactions.map((transaction) => (
                                <tr key={transaction.id} className="h-[64px] border-t border-gray-100 hover:bg-gray-50">
                                    <td className="px-[20px]">
                                        <Checkbox
                                            checked={selectedTransactions.includes(transaction.id)}
                                            onCheckedChange={(checked) => handleSelectTransaction(transaction.id, checked as boolean)}
                                        />
                                    </td>
                                    <td className="px-[20px] text-sm text-gray-900">
                                        {formatDate(transaction.date)}
                                    </td>
                                    <td className="px-[20px] text-sm text-gray-900">
                                        {transaction.user_name}
                                    </td>
                                    <td className="px-[20px] text-sm text-gray-600">
                                        {transaction.description}
                                    </td>
                                    <td className="px-[20px] text-right text-sm font-medium text-gray-900">
                                        {formatCurrency(transaction.amount, transaction.currency)}
                                    </td>
                                    <td className="px-[20px] text-center">
                                        {getStatusBadge(transaction.status)}
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>

                {/* Pagination */}
                {paginationInfo && paginationInfo.last_page > 1 && (
                    <div className="flex items-center justify-between px-6 py-4 border-t border-gray-200">
                        <div className="text-sm text-gray-700">
                            Showing {paginationInfo.from} to {paginationInfo.to} of {paginationInfo.total} transactions
                        </div>
                        <div className="flex items-center gap-2">
                            {paginationInfo.current_page > 1 && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => {
                                        const url = new URL(window.location.href);
                                        url.searchParams.set('page', String(paginationInfo.current_page - 1));
                                        url.searchParams.set('tab', 'transactions');
                                        window.history.pushState({}, '', url);
                                        window.location.reload();
                                    }}
                                >
                                    Previous
                                </Button>
                            )}
                            <span className="text-sm text-gray-700">
                                Page {paginationInfo.current_page} of {paginationInfo.last_page}
                            </span>
                            {paginationInfo.current_page < paginationInfo.last_page && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => {
                                        const url = new URL(window.location.href);
                                        url.searchParams.set('page', String(paginationInfo.current_page + 1));
                                        url.searchParams.set('tab', 'transactions');
                                        window.history.pushState({}, '', url);
                                        window.location.reload();
                                    }}
                                >
                                    Next
                                </Button>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
