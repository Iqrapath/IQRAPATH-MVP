import { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Eye, ChevronLeft, ChevronRight } from 'lucide-react';
import axios from 'axios';
import { toast } from 'sonner';

interface BankDetails {
    bank_name: string | null;
    account_name: string | null;
    last_four: string | null;
}

interface Withdrawal {
    id: number;
    request_uuid: string;
    amount: number;
    currency: string;
    status: 'pending' | 'approved' | 'rejected' | 'processing' | 'completed';
    payment_method: string;
    bank_details: BankDetails;
    notes: string | null;
    created_at: string;
    updated_at: string;
    processed_at: string | null;
}

interface WithdrawalDetails extends Withdrawal {
    rejection_reason: string | null;
    processed_by: {
        id: number;
        name: string;
        role: string;
    } | null;
    transaction_id: number | null;
    processing_time_estimate: string;
    bank_details: BankDetails & {
        bank_code?: string | null;
        account_number_masked?: string | null;
    };
}

interface PaginationData {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
    from: number | null;
    to: number | null;
}

export default function WithdrawalHistory() {
    const [withdrawals, setWithdrawals] = useState<Withdrawal[]>([]);
    const [loading, setLoading] = useState(true);
    const [pagination, setPagination] = useState<PaginationData>({
        current_page: 1,
        per_page: 50,
        total: 0,
        last_page: 1,
        from: null,
        to: null,
    });
    const [selectedWithdrawal, setSelectedWithdrawal] = useState<WithdrawalDetails | null>(null);
    const [showDetailsModal, setShowDetailsModal] = useState(false);
    const [loadingDetails, setLoadingDetails] = useState(false);

    // Fetch withdrawals
    const fetchWithdrawals = async (page: number = 1) => {
        setLoading(true);
        try {
            const response = await axios.get(`/guardian/wallet/withdrawals?page=${page}`);
            
            if (response.data.success) {
                setWithdrawals(response.data.data);
                setPagination(response.data.pagination);
            }
        } catch (error: any) {
            console.error('Error fetching withdrawals:', error);
            toast.error('Failed to load withdrawal history', {
                description: error.response?.data?.message || 'Please try again later',
            });
        } finally {
            setLoading(false);
        }
    };

    // Fetch withdrawal details
    const fetchWithdrawalDetails = async (id: number) => {
        setLoadingDetails(true);
        try {
            const response = await axios.get(`/guardian/wallet/withdrawals/${id}`);
            
            if (response.data.success) {
                setSelectedWithdrawal(response.data.data);
                setShowDetailsModal(true);
            }
        } catch (error: any) {
            console.error('Error fetching withdrawal details:', error);
            toast.error('Failed to load withdrawal details', {
                description: error.response?.data?.message || 'Please try again later',
            });
        } finally {
            setLoadingDetails(false);
        }
    };

    // Initial load
    useEffect(() => {
        fetchWithdrawals();
    }, []);

    // Format currency
    const formatAmount = (amount: number, currency: string = 'NGN'): string => {
        const symbol = currency === 'NGN' ? '₦' : '$';
        return `${symbol}${amount.toLocaleString()}`;
    };

    // Format date
    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            day: 'numeric', 
            month: 'short', 
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    // Get status badge variant and color
    const getStatusBadge = (status: string) => {
        const statusConfig = {
            pending: { label: 'Pending', className: 'bg-yellow-100 text-yellow-800' },
            approved: { label: 'Approved', className: 'bg-blue-100 text-blue-800' },
            rejected: { label: 'Rejected', className: 'bg-red-100 text-red-800' },
            processing: { label: 'Processing', className: 'bg-purple-100 text-purple-800' },
            completed: { label: 'Completed', className: 'bg-green-100 text-green-800' },
        };

        const config = statusConfig[status as keyof typeof statusConfig] || statusConfig.pending;
        
        return (
            <Badge className={config.className}>
                {config.label}
            </Badge>
        );
    };

    // Handle page change
    const handlePageChange = (newPage: number) => {
        if (newPage >= 1 && newPage <= pagination.last_page) {
            fetchWithdrawals(newPage);
        }
    };

    // Handle view details
    const handleViewDetails = (withdrawal: Withdrawal) => {
        fetchWithdrawalDetails(withdrawal.id);
    };

    return (
        <div className="space-y-6">
            <Card className="bg-white border border-gray-200">
                <CardHeader>
                    <CardTitle className="text-xl font-bold text-gray-900">
                        Withdrawal History
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    {loading ? (
                        <div className="text-center py-8 text-gray-500">
                            Loading withdrawal history...
                        </div>
                    ) : withdrawals.length === 0 ? (
                        <div className="text-center py-12">
                            <p className="text-gray-400 text-lg mb-2">
                                No withdrawal requests yet
                            </p>
                            <p className="text-gray-500 text-sm">
                                Your withdrawal requests will appear here
                            </p>
                        </div>
                    ) : (
                        <>
                            {/* Withdrawal Table */}
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead>
                                        <tr className="border-b border-gray-200">
                                            <th className="text-left py-3 px-4 text-sm font-medium text-gray-600">
                                                Request ID
                                            </th>
                                            <th className="text-left py-3 px-4 text-sm font-medium text-gray-600">
                                                Amount
                                            </th>
                                            <th className="text-left py-3 px-4 text-sm font-medium text-gray-600">
                                                Bank Details
                                            </th>
                                            <th className="text-left py-3 px-4 text-sm font-medium text-gray-600">
                                                Date
                                            </th>
                                            <th className="text-left py-3 px-4 text-sm font-medium text-gray-600">
                                                Status
                                            </th>
                                            <th className="text-left py-3 px-4 text-sm font-medium text-gray-600">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {withdrawals.map((withdrawal) => (
                                            <tr 
                                                key={withdrawal.id} 
                                                className="border-b border-gray-100 hover:bg-gray-50"
                                            >
                                                <td className="py-4 px-4">
                                                    <div className="text-sm font-medium text-gray-900">
                                                        #{withdrawal.request_uuid.slice(0, 8)}
                                                    </div>
                                                </td>
                                                <td className="py-4 px-4">
                                                    <div className="text-sm font-semibold text-gray-900">
                                                        {formatAmount(withdrawal.amount, withdrawal.currency)}
                                                    </div>
                                                </td>
                                                <td className="py-4 px-4">
                                                    <div className="text-sm text-gray-900">
                                                        {withdrawal.bank_details.bank_name || 'N/A'}
                                                    </div>
                                                    <div className="text-xs text-gray-500">
                                                        {withdrawal.bank_details.account_name || 'N/A'}
                                                        {withdrawal.bank_details.last_four && 
                                                            ` | ****${withdrawal.bank_details.last_four}`
                                                        }
                                                    </div>
                                                </td>
                                                <td className="py-4 px-4">
                                                    <div className="text-sm text-gray-900">
                                                        {formatDate(withdrawal.created_at)}
                                                    </div>
                                                </td>
                                                <td className="py-4 px-4">
                                                    {getStatusBadge(withdrawal.status)}
                                                </td>
                                                <td className="py-4 px-4">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleViewDetails(withdrawal)}
                                                        className="text-[#2C7870] hover:text-[#235f59] hover:bg-[#2C7870]/10"
                                                    >
                                                        <Eye className="w-4 h-4 mr-1" />
                                                        View Details
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {/* Pagination */}
                            {pagination.last_page > 1 && (
                                <div className="flex items-center justify-between mt-6 pt-4 border-t border-gray-200">
                                    <div className="text-sm text-gray-600">
                                        Showing {pagination.from} to {pagination.to} of {pagination.total} withdrawals
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => handlePageChange(pagination.current_page - 1)}
                                            disabled={pagination.current_page === 1}
                                            className="flex items-center gap-1"
                                        >
                                            <ChevronLeft className="w-4 h-4" />
                                            Previous
                                        </Button>
                                        <div className="text-sm text-gray-600">
                                            Page {pagination.current_page} of {pagination.last_page}
                                        </div>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => handlePageChange(pagination.current_page + 1)}
                                            disabled={pagination.current_page === pagination.last_page}
                                            className="flex items-center gap-1"
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

            {/* Withdrawal Details Modal */}
            <Dialog open={showDetailsModal} onOpenChange={setShowDetailsModal}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Withdrawal Request Details</DialogTitle>
                        <DialogDescription>
                            View complete information about this withdrawal request
                        </DialogDescription>
                    </DialogHeader>

                    {loadingDetails ? (
                        <div className="text-center py-8 text-gray-500">
                            Loading details...
                        </div>
                    ) : selectedWithdrawal ? (
                        <div className="space-y-6">
                            {/* Request Information */}
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="text-sm font-medium text-gray-600">Request ID</label>
                                    <p className="text-sm text-gray-900 mt-1">
                                        #{selectedWithdrawal.request_uuid}
                                    </p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-gray-600">Status</label>
                                    <div className="mt-1">
                                        {getStatusBadge(selectedWithdrawal.status)}
                                    </div>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-gray-600">Amount</label>
                                    <p className="text-lg font-semibold text-gray-900 mt-1">
                                        {formatAmount(selectedWithdrawal.amount, selectedWithdrawal.currency)}
                                    </p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-gray-600">Request Date</label>
                                    <p className="text-sm text-gray-900 mt-1">
                                        {formatDate(selectedWithdrawal.created_at)}
                                    </p>
                                </div>
                            </div>

                            {/* Bank Details */}
                            <div className="border-t border-gray-200 pt-4">
                                <h4 className="text-sm font-semibold text-gray-900 mb-3">Bank Details</h4>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="text-sm font-medium text-gray-600">Bank Name</label>
                                        <p className="text-sm text-gray-900 mt-1">
                                            {selectedWithdrawal.bank_details.bank_name || 'N/A'}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-600">Account Name</label>
                                        <p className="text-sm text-gray-900 mt-1">
                                            {selectedWithdrawal.bank_details.account_name || 'N/A'}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-600">Account Number</label>
                                        <p className="text-sm text-gray-900 mt-1">
                                            {selectedWithdrawal.bank_details.account_number_masked || 
                                             (selectedWithdrawal.bank_details.last_four ? 
                                              `****${selectedWithdrawal.bank_details.last_four}` : 'N/A')}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-600">Payment Method</label>
                                        <p className="text-sm text-gray-900 mt-1">
                                            {selectedWithdrawal.payment_method === 'bank_transfer' 
                                                ? 'Bank Transfer' 
                                                : selectedWithdrawal.payment_method}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* Processing Information */}
                            <div className="border-t border-gray-200 pt-4">
                                <h4 className="text-sm font-semibold text-gray-900 mb-3">Processing Information</h4>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="text-sm font-medium text-gray-600">Processing Time</label>
                                        <p className="text-sm text-gray-900 mt-1">
                                            {selectedWithdrawal.processing_time_estimate}
                                        </p>
                                    </div>
                                    {selectedWithdrawal.processed_at && (
                                        <div>
                                            <label className="text-sm font-medium text-gray-600">Processed Date</label>
                                            <p className="text-sm text-gray-900 mt-1">
                                                {formatDate(selectedWithdrawal.processed_at)}
                                            </p>
                                        </div>
                                    )}
                                    {selectedWithdrawal.processed_by && (
                                        <div>
                                            <label className="text-sm font-medium text-gray-600">Processed By</label>
                                            <p className="text-sm text-gray-900 mt-1">
                                                {selectedWithdrawal.processed_by.name} ({selectedWithdrawal.processed_by.role})
                                            </p>
                                        </div>
                                    )}
                                    {selectedWithdrawal.transaction_id && (
                                        <div>
                                            <label className="text-sm font-medium text-gray-600">Transaction ID</label>
                                            <p className="text-sm text-gray-900 mt-1">
                                                #{selectedWithdrawal.transaction_id}
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Notes */}
                            {selectedWithdrawal.notes && (
                                <div className="border-t border-gray-200 pt-4">
                                    <label className="text-sm font-medium text-gray-600">Notes</label>
                                    <p className="text-sm text-gray-900 mt-1">
                                        {selectedWithdrawal.notes}
                                    </p>
                                </div>
                            )}

                            {/* Rejection Reason */}
                            {selectedWithdrawal.status === 'rejected' && selectedWithdrawal.rejection_reason && (
                                <div className="border-t border-gray-200 pt-4">
                                    <label className="text-sm font-medium text-red-600">Rejection Reason</label>
                                    <p className="text-sm text-red-900 mt-1 bg-red-50 p-3 rounded-lg">
                                        {selectedWithdrawal.rejection_reason}
                                    </p>
                                </div>
                            )}

                            {/* Status Information */}
                            <div className="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                <p className="text-sm text-blue-900">
                                    {selectedWithdrawal.status === 'pending' && (
                                        <><strong>Pending:</strong> Your withdrawal request is awaiting admin approval.</>
                                    )}
                                    {selectedWithdrawal.status === 'approved' && (
                                        <><strong>Approved:</strong> Your withdrawal has been approved and is being processed.</>
                                    )}
                                    {selectedWithdrawal.status === 'processing' && (
                                        <><strong>Processing:</strong> Your withdrawal is currently being processed. Funds will be transferred within 1-3 business days.</>
                                    )}
                                    {selectedWithdrawal.status === 'completed' && (
                                        <><strong>Completed:</strong> Your withdrawal has been successfully processed and funds have been transferred to your bank account.</>
                                    )}
                                    {selectedWithdrawal.status === 'rejected' && (
                                        <><strong>Rejected:</strong> Your withdrawal request was rejected. The amount has been refunded to your wallet.</>
                                    )}
                                </p>
                            </div>
                        </div>
                    ) : null}
                </DialogContent>
            </Dialog>
        </div>
    );
}

