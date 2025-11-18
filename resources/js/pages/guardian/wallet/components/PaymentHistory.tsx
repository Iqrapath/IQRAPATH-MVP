/**
 * Payment History Component for Guardian Wallet
 * Shows all payment transactions (funding, spending on children, refunds)
 */
import { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight, ArrowUpCircle, ArrowDownCircle, RefreshCw } from 'lucide-react';
import { toast } from 'sonner';
import axios from 'axios';
import { format } from 'date-fns';

interface PaymentTransaction {
    id: number;
    date: string;
    description: string;
    type: 'credit' | 'debit' | 'refund';
    amount: number;
    currency: string;
    status: 'completed' | 'pending' | 'failed';
    balance_after?: number;
}

interface PaginationData {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
    from: number;
    to: number;
}

export default function PaymentHistory() {
    const [transactions, setTransactions] = useState<PaymentTransaction[]>([]);
    const [loading, setLoading] = useState(true);
    const [pagination, setPagination] = useState<PaginationData>({
        current_page: 1,
        per_page: 10,
        total: 0,
        last_page: 1,
        from: 0,
        to: 0,
    });

    // Fetch payment history
    const fetchPaymentHistory = async (page: number = 1) => {
        setLoading(true);
        try {
            const response = await axios.get(`/guardian/wallet/history?page=${page}`);
            
            if (response.data.success) {
                setTransactions(response.data.data);
                setPagination(response.data.pagination);
            }
        } catch (error: any) {
            console.error('Error fetching payment history:', error);
            toast.error('Failed to load payment history', {
                description: error.response?.data?.message || 'Please try again later',
            });
        } finally {
            setLoading(false);
        }
    };

    // Initial load
    useEffect(() => {
        fetchPaymentHistory();
    }, []);

    const handlePageChange = (newPage: number) => {
        if (newPage >= 1 && newPage <= pagination.last_page) {
            fetchPaymentHistory(newPage);
        }
    };

    const getTransactionIcon = (type: string) => {
        switch (type) {
            case 'credit':
                return <ArrowDownCircle className="w-5 h-5 text-green-600" />;
            case 'debit':
                return <ArrowUpCircle className="w-5 h-5 text-red-600" />;
            case 'refund':
                return <RefreshCw className="w-5 h-5 text-blue-600" />;
            default:
                return <ArrowDownCircle className="w-5 h-5 text-gray-600" />;
        }
    };

    const getStatusBadge = (status: string) => {
        const variants: Record<string, { variant: 'default' | 'secondary' | 'destructive' | 'outline'; label: string }> = {
            completed: { variant: 'default', label: 'Completed' },
            pending: { variant: 'secondary', label: 'Pending' },
            failed: { variant: 'destructive', label: 'Failed' },
        };

        const config = variants[status] || variants.completed;
        return <Badge variant={config.variant}>{config.label}</Badge>;
    };

    const formatAmount = (amount: number, currency: string, type: string) => {
        const symbol = currency === 'USD' ? '$' : '₦';
        const sign = type === 'credit' || type === 'refund' ? '+' : '-';
        const colorClass = type === 'credit' || type === 'refund' ? 'text-green-600' : 'text-red-600';
        
        return (
            <span className={`font-semibold ${colorClass}`}>
                {sign}{symbol}{amount.toLocaleString()}
            </span>
        );
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-xl font-bold">Payment History</CardTitle>
                <p className="text-sm text-gray-600">
                    View all your wallet transactions including funding, payments for children's sessions, and refunds
                </p>
            </CardHeader>
            <CardContent>
                {loading ? (
                    <div className="flex items-center justify-center py-12">
                        <div className="text-center">
                            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-teal-600 mx-auto mb-4"></div>
                            <p className="text-gray-600">Loading payment history...</p>
                        </div>
                    </div>
                ) : transactions.length === 0 ? (
                    <div className="text-center py-12">
                        <p className="text-gray-400 text-lg mb-2">No payment history yet</p>
                        <p className="text-gray-500 text-sm">
                            Your wallet transactions will appear here once you start funding your account or paying for sessions.
                        </p>
                    </div>
                ) : (
                    <>
                        {/* Desktop Table View */}
                        <div className="hidden md:block overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b border-gray-200">
                                        <th className="text-left py-3 px-4 font-semibold text-gray-700">Date</th>
                                        <th className="text-left py-3 px-4 font-semibold text-gray-700">Description</th>
                                        <th className="text-left py-3 px-4 font-semibold text-gray-700">Type</th>
                                        <th className="text-right py-3 px-4 font-semibold text-gray-700">Amount</th>
                                        <th className="text-center py-3 px-4 font-semibold text-gray-700">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {transactions.map((transaction) => (
                                        <tr 
                                            key={transaction.id} 
                                            className="border-b border-gray-100 hover:bg-gray-50 transition-colors"
                                        >
                                            <td className="py-4 px-4 text-sm text-gray-600">
                                                {format(new Date(transaction.date), 'MMM dd, yyyy')}
                                                <br />
                                                <span className="text-xs text-gray-400">
                                                    {format(new Date(transaction.date), 'hh:mm a')}
                                                </span>
                                            </td>
                                            <td className="py-4 px-4">
                                                <div className="flex items-center gap-3">
                                                    {getTransactionIcon(transaction.type)}
                                                    <span className="text-sm text-gray-900">{transaction.description}</span>
                                                </div>
                                            </td>
                                            <td className="py-4 px-4">
                                                <span className="text-sm text-gray-600 capitalize">{transaction.type}</span>
                                            </td>
                                            <td className="py-4 px-4 text-right">
                                                {formatAmount(transaction.amount, transaction.currency, transaction.type)}
                                            </td>
                                            <td className="py-4 px-4 text-center">
                                                {getStatusBadge(transaction.status)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Mobile Card View */}
                        <div className="md:hidden space-y-4">
                            {transactions.map((transaction) => (
                                <div 
                                    key={transaction.id}
                                    className="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
                                >
                                    <div className="flex items-start justify-between mb-3">
                                        <div className="flex items-center gap-3">
                                            {getTransactionIcon(transaction.type)}
                                            <div>
                                                <p className="text-sm font-medium text-gray-900">{transaction.description}</p>
                                                <p className="text-xs text-gray-500 mt-1">
                                                    {format(new Date(transaction.date), 'MMM dd, yyyy • hh:mm a')}
                                                </p>
                                            </div>
                                        </div>
                                        {getStatusBadge(transaction.status)}
                                    </div>
                                    <div className="flex items-center justify-between pt-3 border-t border-gray-100">
                                        <span className="text-xs text-gray-600 capitalize">{transaction.type}</span>
                                        <span className="text-lg">
                                            {formatAmount(transaction.amount, transaction.currency, transaction.type)}
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </div>

                        {/* Pagination */}
                        {pagination.last_page > 1 && (
                            <div className="flex items-center justify-between mt-6 pt-4 border-t border-gray-200">
                                <div className="text-sm text-gray-600">
                                    Showing {pagination.from} to {pagination.to} of {pagination.total} transactions
                                </div>
                                <div className="flex items-center gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handlePageChange(pagination.current_page - 1)}
                                        disabled={pagination.current_page === 1}
                                    >
                                        <ChevronLeft className="w-4 h-4" />
                                        Previous
                                    </Button>
                                    <span className="text-sm text-gray-600">
                                        Page {pagination.current_page} of {pagination.last_page}
                                    </span>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handlePageChange(pagination.current_page + 1)}
                                        disabled={pagination.current_page === pagination.last_page}
                                    >
                                        Next
                                        <ChevronRight className="w-4 h-4" />
                                    </Button>
                                </div>
                            </div>
                        )}
                    </>
                )}
            </CardContent>
        </Card>
    );
}
