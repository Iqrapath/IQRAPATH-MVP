import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ArrowLeft, FileText } from 'lucide-react';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import axios from 'axios';
import { toast } from 'sonner';
import { useCurrency } from '@/contexts/CurrencyContext';

interface Transaction {
    id: number;
    date: string;
    type: string;
    description: string;
    amount: number;
    currency: string;
    status: string;
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
    const pageProps = usePage().props as any;
    const { formatBalance } = useCurrency();
    
    const [emailingReport, setEmailingReport] = useState(false);
    const [currentPage, setCurrentPage] = useState(1);
    const [transactions, setTransactions] = useState<Transaction[]>(pageProps.transactions?.data || []);
    const [pagination, setPagination] = useState<PaginationData | null>(pageProps.transactions || null);

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

    const handlePageChange = async (page: number) => {
        try {
            const response = await axios.get(`/student/wallet/history?page=${page}`, {
                withCredentials: true
            });

            if (response.data.success) {
                setTransactions(response.data.data);
                setPagination(response.data.pagination);
                setCurrentPage(page);
            }
        } catch (error) {
            console.error('Error fetching transactions:', error);
            toast.error('Failed to load transactions');
        }
    };

    const handleBackToWallet = () => {
        router.visit('/student/wallet');
    };

    // Format amount with currency (consistent with header - no decimals for NGN)
    const formatAmount = (amount: number, currency?: string): string => {
        const curr = currency || 'NGN';
        const symbol = curr === 'NGN' ? '₦' : '$';
        return `${symbol}${amount.toLocaleString(undefined, {
            minimumFractionDigits: curr === 'NGN' ? 0 : 2,
            maximumFractionDigits: curr === 'NGN' ? 0 : 2
        })}`;
    };

    // Get status badge color
    const getStatusColor = (status: string): string => {
        switch (status.toLowerCase()) {
            case 'completed':
                return 'bg-green-100 text-green-800';
            case 'pending':
                return 'bg-yellow-100 text-yellow-800';
            case 'cancelled':
            case 'failed':
                return 'bg-red-100 text-red-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    return (
        <div className="space-y-6">
            {/* Header with Back Button */}
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-4">
                    <Button
                        variant="ghost"
                        onClick={handleBackToWallet}
                        className="flex items-center gap-2"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        Back to Wallet
                    </Button>
                    <h1 className="text-2xl font-bold text-gray-900">Payment History</h1>
                </div>
                <Button
                    variant="outline"
                    className="flex items-center gap-2"
                    onClick={handleEmailReport}
                    disabled={emailingReport}
                >
                    <FileText className="w-4 h-4" />
                    {emailingReport ? 'Sending...' : 'Email Report'}
                </Button>
            </div>

            {/* Payment History Card */}
            <Card className="bg-white border border-gray-200">
                <CardHeader>
                    <CardTitle className="text-xl font-bold text-gray-900">All Transactions</CardTitle>
                </CardHeader>
                <CardContent>
                    {transactions.length === 0 ? (
                        <div className="text-center py-12 text-gray-500">
                            <p className="text-lg">No payment history yet</p>
                            <p className="text-sm mt-2">Your transactions will appear here</p>
                        </div>
                    ) : (
                        <>
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
                                        {transactions.map((transaction) => (
                                            <tr key={transaction.id} className="border-b border-gray-100 hover:bg-gray-50">
                                                <td className="py-4 px-4">
                                                    <div className="text-2xl font-bold text-gray-900">
                                                        {new Date(transaction.date).getDate()}
                                                    </div>
                                                    <div className="text-xs text-gray-600">
                                                        {new Date(transaction.date).toLocaleDateString('en-US', { 
                                                            month: 'short',
                                                            year: 'numeric'
                                                        })}
                                                    </div>
                                                </td>
                                                <td className="py-4 px-4 text-sm text-gray-900">{transaction.description}</td>
                                                <td className="py-4 px-4 text-sm text-gray-900 capitalize">
                                                    {transaction.type.replace('_', ' ')}
                                                </td>
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

                            {/* Pagination */}
                            {pagination && pagination.last_page > 1 && (
                                <div className="flex items-center justify-between mt-6 pt-4 border-t border-gray-200">
                                    <div className="text-sm text-gray-600">
                                        Showing {pagination.from} to {pagination.to} of {pagination.total} transactions
                                    </div>
                                    <div className="flex gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => handlePageChange(currentPage - 1)}
                                            disabled={currentPage === 1}
                                        >
                                            Previous
                                        </Button>
                                        <div className="flex items-center gap-1">
                                            {Array.from({ length: Math.min(5, pagination.last_page) }, (_, i) => {
                                                let pageNum;
                                                if (pagination.last_page <= 5) {
                                                    pageNum = i + 1;
                                                } else if (currentPage <= 3) {
                                                    pageNum = i + 1;
                                                } else if (currentPage >= pagination.last_page - 2) {
                                                    pageNum = pagination.last_page - 4 + i;
                                                } else {
                                                    pageNum = currentPage - 2 + i;
                                                }

                                                return (
                                                    <Button
                                                        key={pageNum}
                                                        variant={currentPage === pageNum ? 'default' : 'outline'}
                                                        size="sm"
                                                        onClick={() => handlePageChange(pageNum)}
                                                        className="w-10"
                                                    >
                                                        {pageNum}
                                                    </Button>
                                                );
                                            })}
                                        </div>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => handlePageChange(currentPage + 1)}
                                            disabled={currentPage === pagination.last_page}
                                        >
                                            Next
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
