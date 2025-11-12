import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin/admin-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { ArrowLeft, Wallet, TrendingUp, DollarSign, CheckCircle, XCircle, Loader2, Edit2 } from 'lucide-react';
import { toast } from 'sonner';
import axios from 'axios';
import { PageProps } from '@/types';

/**
 * 🎨 FIGMA DESIGN REFERENCE
 * 
 * Component: Withdrawal Request Detail View
 * Based on the provided design image
 * 
 * 📏 EXACT SPECIFICATIONS:
 * - Page title: 24px, weight: 600
 * - Info labels: 14px, weight: 500, color: #64748B
 * - Info values: 14px, weight: 400, color: #0F172A
 * - Status badge: Pending #F59E0B, Approved #10B981
 * - Stat cards: bg colors - blue #E0F2FE, teal #CCFBF1, yellow #FEF3C7
 * - Table header: bg #F6F7F9, text 12px
 * - Action buttons: Approve #14B8A6, Reject #EF4444
 * 
 * 📱 RESPONSIVE: Desktop layout primary
 * 🎯 STATES: Approve/reject modals, loading states
 */

interface Teacher {
    id: number;
    name: string;
    email: string;
}

interface ProcessedBy {
    id: number;
    name: string;
    email: string;
}

interface Transaction {
    id: number;
    amount: number;
    status: string;
    created_at: string;
}

interface PaymentDetails {
    bank_name?: string;
    account_number?: string;
    account_name?: string;
}

interface PayoutRequest {
    id: number;
    request_uuid: string;
    user_id: number;
    amount: number;
    currency: string;
    payment_method: string;
    payment_details: PaymentDetails;
    status: string;
    request_date: string;
    processed_at?: string;
    notes?: string;
    teacher?: Teacher;
    user?: Teacher;
    processed_by?: ProcessedBy;
    transaction?: Transaction;
}

interface TeacherEarnings {
    wallet_balance: number;
    total_earned: number;
    previous_payouts: number;
}

interface SessionLog {
    id: number;
    date: string;
    subject: string;
    session_type: string;
    amount_earned: number;
}

interface AvailablePaymentMethod {
    id: number;
    type: string;
    details: {
        bank_name?: string;
        account_number?: string;
        account_name?: string;
        card_last_four?: string;
        card_brand?: string;
        paypal_email?: string;
    };
    is_default: boolean;
}

interface Props extends PageProps {
    payoutRequest: PayoutRequest;
    teacherEarnings?: TeacherEarnings;
    sessionLogs?: SessionLog[];
    availablePaymentMethods?: AvailablePaymentMethod[];
}

export default function PayoutRequestDetails({ auth, payoutRequest, teacherEarnings, sessionLogs, availablePaymentMethods }: Props) {
    const [showRejectModal, setShowRejectModal] = useState(false);
    const [rejectReason, setRejectReason] = useState('');
    const [isRejecting, setIsRejecting] = useState(false);
    const [showApproveModal, setShowApproveModal] = useState(false);
    const [isApproving, setIsApproving] = useState(false);

    // Edit payment method modal state
    const [showEditPaymentModal, setShowEditPaymentModal] = useState(false);
    const [selectedPaymentMethod, setSelectedPaymentMethod] = useState(payoutRequest.payment_method);
    const [isSavingPaymentMethod, setIsSavingPaymentMethod] = useState(false);

    const teacher = payoutRequest.teacher || payoutRequest.user;

    const breadcrumbs = [
        { title: 'Dashboard', href: '/admin/dashboard' },
        { title: 'Financial Management', href: '/admin/financial' },
        { title: 'Withdrawal Request Details', href: '' },
    ];

    const statusBadge = (status: string) => {
        const statusMap: Record<string, { bg: string; text: string; label: string }> = {
            'pending': { bg: 'bg-[#FFF7E6]', text: 'text-[#F59E0B]', label: 'Pending Approval' },
            'approved': { bg: 'bg-[#E6FAF2]', text: 'text-[#0E9F6E]', label: 'Approved' },
            'processing': { bg: 'bg-[#E0F2FE]', text: 'text-[#0284C7]', label: 'Processing' },
            'completed': { bg: 'bg-[#D1FAE5]', text: 'text-[#059669]', label: 'Completed' },
            'rejected': { bg: 'bg-[#FEE2E2]', text: 'text-[#EF4444]', label: 'Rejected' },
            'failed': { bg: 'bg-[#FEE2E2]', text: 'text-[#DC2626]', label: 'Failed' },
        };

        const s = statusMap[status.toLowerCase()] || { bg: 'bg-gray-100', text: 'text-gray-800', label: status };
        return <Badge className={`${s.bg} ${s.text} border-0 px-4 py-1`}>{s.label}</Badge>;
    };

    const formatPaymentMethod = (method: string): string => {
        return method
            .split('_')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ');
    };

    const handleApproveClick = () => {
        setShowApproveModal(true);
    };

    const handleRejectClick = () => {
        setRejectReason('');
        setShowRejectModal(true);
    };

    const handleEditPaymentMethodClick = () => {
        setSelectedPaymentMethod(payoutRequest.payment_method);
        setShowEditPaymentModal(true);
    };

    const handleSavePaymentMethod = async () => {
        if (!selectedPaymentMethod) {
            toast.error('Please select a payment method');
            return;
        }

        setIsSavingPaymentMethod(true);
        try {
            const response = await axios.patch(`/admin/financial/payout-requests/${payoutRequest.id}/payment-method`, {
                payment_method: selectedPaymentMethod
            });

            if (response.data.success) {
                toast.success('Payment method updated successfully!');
                setShowEditPaymentModal(false);

                // Reload page data
                router.reload({ only: ['payoutRequest'] });
            } else {
                toast.error(response.data.message || 'Failed to update payment method');
            }
        } catch (error: any) {
            console.error('Error updating payment method:', error);
            const errorMessage = error.response?.data?.message || 'An error occurred while updating the payment method';
            toast.error(errorMessage);
        } finally {
            setIsSavingPaymentMethod(false);
        }
    };

    const handleApproveConfirm = async () => {
        setIsApproving(true);
        try {
            const response = await axios.post(`/admin/financial/payout-requests/${payoutRequest.id}/approve`);

            if (response.data.success) {
                toast.success(response.data.message || 'Payout approved successfully!');
                setShowApproveModal(false);

                // Reload page data
                router.reload({ only: ['payoutRequest'] });
            } else {
                toast.error(response.data.message || 'Failed to approve payout');
            }
        } catch (error: any) {
            console.error('Error approving payout:', error);
            const errorMessage = error.response?.data?.message || 'An error occurred while approving the payout';
            toast.error(errorMessage);
        } finally {
            setIsApproving(false);
        }
    };

    const handleRejectConfirm = async () => {
        if (!rejectReason.trim()) {
            toast.error('Please provide a reason for rejection');
            return;
        }

        setIsRejecting(true);
        try {
            const response = await axios.post(`/admin/financial/payout-requests/${payoutRequest.id}/reject`, {
                reason: rejectReason
            });

            if (response.data.success) {
                toast.success(response.data.message || 'Payout rejected successfully!');
                setShowRejectModal(false);
                setRejectReason('');

                // Reload page data
                router.reload({ only: ['payoutRequest'] });
            } else {
                toast.error(response.data.message || 'Failed to reject payout');
            }
        } catch (error: any) {
            console.error('Error rejecting payout:', error);
            const errorMessage = error.response?.data?.message || 'An error occurred while rejecting the payout';
            toast.error(errorMessage);
        } finally {
            setIsRejecting(false);
        }
    };

    return (
        <AdminLayout pageTitle="Withdrawal Request Details">
            <Head title="Withdrawal Request Details" />

            <div className="p-6 max-w-7xl mx-auto">
                {/* Breadcrumbs */}
                <Breadcrumbs breadcrumbs={breadcrumbs} />

                {/* Header */}
                <div className="mt-6 flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => router.visit('/admin/financial')}
                            className="rounded-full"
                        >
                            <ArrowLeft className="w-5 h-5" />
                        </Button>
                        <h1 className="text-2xl font-semibold text-[#0F172A]">
                            Withdrawal Request – Detail View
                        </h1>
                    </div>
                </div>

                {/* Main Content */}
                <div className="mt-6 space-y-6">
                    {/* Request Information Card */}
                    <Card className="p-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div className="space-y-4">
                                <div>
                                    <p className="text-sm font-medium text-[#64748B]">Teacher:</p>
                                    <p className="text-sm text-[#0F172A] mt-1">{teacher?.name || 'N/A'}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-[#64748B]">Email:</p>
                                    <p className="text-sm text-[#0F172A] mt-1">{teacher?.email || 'N/A'}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-[#64748B]">Request ID:</p>
                                    <p className="text-sm text-[#0F172A] mt-1">{payoutRequest.request_uuid}</p>
                                </div>
                            </div>

                            <div className="space-y-4">
                                <div>
                                    <p className="text-sm font-medium text-[#64748B]">Requested On:</p>
                                    <p className="text-sm text-[#0F172A] mt-1">
                                        {new Date(payoutRequest.request_date).toLocaleDateString('en-US', {
                                            month: 'long',
                                            day: '2-digit',
                                            year: 'numeric'
                                        })} – {new Date(payoutRequest.request_date).toLocaleTimeString('en-US', {
                                            hour: '2-digit',
                                            minute: '2-digit',
                                            hour12: true
                                        })}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-[#64748B]">Requested Amount:</p>
                                    <p className="text-sm font-semibold text-[#0F172A] mt-1">
                                        ₦{payoutRequest.amount.toLocaleString()}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-[#64748B]">Status</p>
                                    <div className="mt-1">{statusBadge(payoutRequest.status)}</div>
                                </div>
                            </div>
                        </div>

                        {/* Payment Method */}
                        <div className="mt-6 pt-6 border-t border-[#F1F5F9]">
                            <p className="text-sm font-medium text-[#64748B] mb-3">Payment Method:</p>
                            <div className="bg-[#F8FAFC] rounded-lg p-4">
                                <p className="text-sm text-[#0F172A]">
                                    {formatPaymentMethod(payoutRequest.payment_method)} – {payoutRequest.payment_details?.bank_name || 'N/A'},
                                    A/C: {payoutRequest.payment_details?.account_number
                                        ? '****' + payoutRequest.payment_details.account_number.slice(-4)
                                        : 'N/A'}
                                    ({payoutRequest.payment_details?.account_name || 'N/A'})
                                </p>
                            </div>
                        </div>
                    </Card>

                    {/* Wallet Balance at Time of Request */}
                    {teacherEarnings && (
                        <Card className="p-6">
                            <h3 className="text-base font-medium text-[#64748B] mb-4">
                                Wallet Balance at Time of Request
                            </h3>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div className="bg-[#E0F2FE] rounded-lg p-4">
                                    <div className="flex items-center gap-2 mb-2">
                                        <Wallet className="w-5 h-5 text-[#0284C7]" />
                                        <p className="text-xs text-[#64748B]">Wallet Balance</p>
                                    </div>
                                    <p className="text-xl font-semibold text-[#0F172A]">
                                        ₦{teacherEarnings.wallet_balance.toLocaleString()}
                                    </p>
                                </div>

                                <div className="bg-[#CCFBF1] rounded-lg p-4">
                                    <div className="flex items-center gap-2 mb-2">
                                        <TrendingUp className="w-5 h-5 text-[#14B8A6]" />
                                        <p className="text-xs text-[#64748B]">Total Earnings</p>
                                    </div>
                                    <p className="text-xl font-semibold text-[#0F172A]">
                                        ₦{teacherEarnings.total_earned.toLocaleString()}
                                    </p>
                                </div>

                                <div className="bg-[#FEF3C7] rounded-lg p-4">
                                    <div className="flex items-center gap-2 mb-2">
                                        <DollarSign className="w-5 h-5 text-[#F59E0B]" />
                                        <p className="text-xs text-[#64748B]">Previous Payouts</p>
                                    </div>
                                    <p className="text-xl font-semibold text-[#0F172A]">
                                        ₦{teacherEarnings.previous_payouts.toLocaleString()}
                                    </p>
                                </div>
                            </div>

                            <div className="mt-4 text-right">
                                <Button
                                    variant="link"
                                    className="text-[#14B8A6] hover:text-[#129c8e] text-sm"
                                    onClick={() => router.visit(`/admin/teachers/${teacher?.id}/earnings`)}
                                >
                                    View Teacher Earnings →
                                </Button>
                            </div>
                        </Card>
                    )}

                    {/* Session Logs */}
                    {sessionLogs && sessionLogs.length > 0 && (
                        <Card className="p-6">
                            <h3 className="text-lg font-semibold text-[#0F172A] mb-4">
                                Session Logs (Earning Source):
                            </h3>

                            <div className="bg-white rounded-lg overflow-hidden border border-[#E2E8F0]">
                                <div className="bg-[#F6F7F9] h-[44px] flex items-center px-[20px] text-[12px] text-[#64748B] font-medium">
                                    <div className="w-[36px] flex items-center"><Checkbox /></div>
                                    <div className="flex-1">Date & Time</div>
                                    <div className="flex-1">Subject</div>
                                    <div className="flex-1">Session Type</div>
                                    <div className="w-[150px] text-right">Amount Earned</div>
                                </div>

                                {sessionLogs.map((session) => (
                                    <div
                                        key={session.id}
                                        className="h-[56px] flex items-center px-[20px] border-t border-[#F1F5F9] text-[14px] text-[#0F172A]"
                                    >
                                        <div className="w-[36px] flex items-center"><Checkbox /></div>
                                        <div className="flex-1 text-[#475569]">{session.date}</div>
                                        <div className="flex-1">{session.subject}</div>
                                        <div className="flex-1">{session.session_type}</div>
                                        <div className="w-[150px] text-right font-medium">
                                            ₦{session.amount_earned.toLocaleString()}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </Card>
                    )}

                    {/* Action Buttons */}
                    {payoutRequest.status === 'pending' && (
                        <div className="flex items-center justify-center gap-4">
                            <Button
                                onClick={handleApproveClick}
                                className="rounded-full px-8 py-6 bg-[#14B8A6] hover:bg-[#129c8e] text-white font-medium cursor-pointer"
                            >
                                Approve Withdrawal
                            </Button>

                            <Button
                                variant="outline"
                                onClick={handleEditPaymentMethodClick}
                                className="rounded-full px-8 py-6 border-[#E2E8F0] text-[#64748B] hover:bg-[#F8FAFC] font-medium cursor-pointer"
                            >
                                <Edit2 className="w-4 h-4 mr-2" />
                                Edit Payment Method
                            </Button>

                            <Button
                                onClick={handleRejectClick}
                                variant="outline"
                                className="rounded-full px-8 py-6 border-[#FEE2E2] text-[#EF4444] hover:bg-[#FEF2F2] font-medium cursor-pointer"
                            >
                                Reject & Enter Reason
                            </Button>
                        </div>
                    )}

                    {/* Processed Information */}
                    {payoutRequest.processed_at && payoutRequest.processed_by && (
                        <Card className="p-6 bg-[#F8FAFC]">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm font-medium text-[#64748B]">Processed By:</p>
                                    <p className="text-sm text-[#0F172A] mt-1">{payoutRequest.processed_by.name}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-[#64748B]">Processed At:</p>
                                    <p className="text-sm text-[#0F172A] mt-1">
                                        {new Date(payoutRequest.processed_at).toLocaleDateString('en-US', {
                                            month: 'long',
                                            day: '2-digit',
                                            year: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit'
                                        })}
                                    </p>
                                </div>
                            </div>
                            {payoutRequest.notes && (
                                <div className="mt-4 pt-4 border-t border-[#E2E8F0]">
                                    <p className="text-sm font-medium text-[#64748B]">Notes:</p>
                                    <p className="text-sm text-[#0F172A] mt-1">{payoutRequest.notes}</p>
                                </div>
                            )}
                        </Card>
                    )}
                </div>
            </div>

            {/* Approve Confirmation Modal */}
            <Dialog open={showApproveModal} onOpenChange={setShowApproveModal}>
                <DialogContent className="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle className="text-xl font-semibold text-[#0F172A]">
                            Approve Payout Request
                        </DialogTitle>
                        <DialogDescription className="text-[#64748B]">
                            Are you sure you want to approve this payout request?
                        </DialogDescription>
                    </DialogHeader>

                    <div className="py-4 space-y-3">
                        <div className="flex justify-between items-center py-2 border-b border-[#F1F5F9]">
                            <span className="text-sm text-[#64748B]">Teacher Name:</span>
                            <span className="text-sm font-medium text-[#0F172A]">{teacher?.name}</span>
                        </div>
                        <div className="flex justify-between items-center py-2 border-b border-[#F1F5F9]">
                            <span className="text-sm text-[#64748B]">Amount:</span>
                            <span className="text-sm font-semibold text-[#0F172A]">
                                ₦{payoutRequest.amount.toLocaleString()}
                            </span>
                        </div>
                        <div className="mt-4 p-3 bg-[#E6FAF2] rounded-lg">
                            <p className="text-xs text-[#0E9F6E]">
                                The payout will be processed and the teacher will be notified.
                                Processing time: 1-3 business days.
                            </p>
                        </div>
                    </div>

                    <DialogFooter className="gap-2">
                        <Button
                            variant="outline"
                            onClick={() => setShowApproveModal(false)}
                            disabled={isApproving}
                            className="rounded-full px-6"
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={handleApproveConfirm}
                            disabled={isApproving}
                            className="rounded-full px-6 bg-[#10B981] hover:bg-[#059669] text-white"
                        >
                            {isApproving ? (
                                <>
                                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                    Approving...
                                </>
                            ) : (
                                <>
                                    <CheckCircle className="w-4 h-4 mr-2" />
                                    Approve
                                </>
                            )}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Reject Confirmation Modal */}
            <Dialog open={showRejectModal} onOpenChange={setShowRejectModal}>
                <DialogContent className="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle className="text-xl font-semibold text-[#0F172A]">
                            Reject Payout Request
                        </DialogTitle>
                        <DialogDescription className="text-[#64748B]">
                            Please provide a reason for rejecting this payout request.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="py-4 space-y-4">
                        <div className="space-y-3">
                            <div className="flex justify-between items-center py-2 border-b border-[#F1F5F9]">
                                <span className="text-sm text-[#64748B]">Teacher Name:</span>
                                <span className="text-sm font-medium text-[#0F172A]">{teacher?.name}</span>
                            </div>
                            <div className="flex justify-between items-center py-2 border-b border-[#F1F5F9]">
                                <span className="text-sm text-[#64748B]">Amount:</span>
                                <span className="text-sm font-semibold text-[#0F172A]">
                                    ₦{payoutRequest.amount.toLocaleString()}
                                </span>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="reject-reason" className="text-sm font-medium text-[#0F172A]">
                                Reason for Rejection *
                            </Label>
                            <Textarea
                                id="reject-reason"
                                placeholder="Enter the reason for rejecting this payout request..."
                                value={rejectReason}
                                onChange={(e) => setRejectReason(e.target.value)}
                                className="min-h-[100px] resize-none"
                                disabled={isRejecting}
                            />
                            <p className="text-xs text-[#64748B]">
                                This reason will be sent to the teacher via notification.
                            </p>
                        </div>

                        <div className="p-3 bg-[#FEE2E2] rounded-lg">
                            <p className="text-xs text-[#EF4444]">
                                The payout amount will be refunded to the teacher's wallet immediately.
                            </p>
                        </div>
                    </div>

                    <DialogFooter className="gap-2">
                        <Button
                            variant="outline"
                            onClick={() => setShowRejectModal(false)}
                            disabled={isRejecting}
                            className="rounded-full px-6"
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={handleRejectConfirm}
                            disabled={isRejecting || !rejectReason.trim()}
                            className="rounded-full px-6 bg-[#EF4444] hover:bg-[#DC2626] text-white"
                        >
                            {isRejecting ? (
                                <>
                                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                    Rejecting...
                                </>
                            ) : (
                                <>
                                    <XCircle className="w-4 h-4 mr-2" />
                                    Reject
                                </>
                            )}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Edit Payment Method Modal */}
            <Dialog open={showEditPaymentModal} onOpenChange={setShowEditPaymentModal}>
                <DialogContent className="sm:max-w-[600px]">
                    <DialogHeader>
                        <DialogTitle className="text-xl font-semibold text-[#0F172A]">
                            Edit Payout Method for: {teacher?.name}
                        </DialogTitle>
                    </DialogHeader>

                    <div className="py-6 space-y-6">
                        {/* Current Payment Method Display */}
                        <div className="bg-[#F8FAFC] rounded-lg p-4">
                            <div className="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p className="text-[#64748B] font-medium mb-1">Method Type</p>
                                    <p className="text-[#0F172A]">{formatPaymentMethod(payoutRequest.payment_method)}</p>
                                </div>
                                <div>
                                    <p className="text-[#64748B] font-medium mb-1">Details</p>
                                    <p className="text-[#0F172A]">
                                        {payoutRequest.payment_details?.bank_name || 'N/A'} -
                                        {payoutRequest.payment_details?.account_number
                                            ? ' ****' + payoutRequest.payment_details.account_number.slice(-4)
                                            : ' N/A'}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Switch Method Section */}
                        <div className="space-y-4">
                            <Label className="text-base font-medium text-[#0F172A]">
                                Available Payment Methods ({availablePaymentMethods?.length || 0}):
                            </Label>

                            {availablePaymentMethods && availablePaymentMethods.length > 0 ? (
                                <div className="space-y-3">
                                    {availablePaymentMethods.map((method) => {
                                        const methodType = method.type;
                                        const isSelected = selectedPaymentMethod === methodType;

                                        // Format display name
                                        const displayName = methodType === 'bank_transfer' ? 'Bank Transfer' :
                                            methodType === 'debit_credit_card' ? 'Debit/Credit Card' :
                                                methodType === 'paypal' ? 'PayPal' :
                                                    methodType.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');

                                        // Format details
                                        let detailsText = '';
                                        if (method.details.bank_name && method.details.account_number) {
                                            detailsText = `${method.details.bank_name} - ****${method.details.account_number.slice(-4)}`;
                                        } else if (method.details.card_brand && method.details.card_last_four) {
                                            detailsText = `${method.details.card_brand} ****${method.details.card_last_four}`;
                                        } else if (method.details.paypal_email) {
                                            detailsText = method.details.paypal_email;
                                        }

                                        return (
                                            <div
                                                key={method.id}
                                                className={`flex items-start space-x-3 p-4 rounded-lg border-2 cursor-pointer transition-all ${isSelected
                                                    ? 'border-[#14B8A6] bg-[#F0FDFA]'
                                                    : 'border-[#E2E8F0] hover:border-[#CBD5E1]'
                                                    }`}
                                                onClick={() => setSelectedPaymentMethod(methodType)}
                                            >
                                                <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center mt-0.5 ${isSelected
                                                    ? 'border-[#14B8A6] bg-[#14B8A6]'
                                                    : 'border-[#CBD5E1]'
                                                    }`}>
                                                    {isSelected && (
                                                        <CheckCircle className="w-3 h-3 text-white" />
                                                    )}
                                                </div>
                                                <div className="flex-1">
                                                    <div className="flex items-center gap-2">
                                                        <span className="text-[#0F172A] font-medium">{displayName}</span>
                                                        {method.is_default && (
                                                            <Badge className="bg-[#E0F2FE] text-[#0284C7] border-0 text-xs">
                                                                Default
                                                            </Badge>
                                                        )}
                                                    </div>
                                                    {detailsText && (
                                                        <p className="text-sm text-[#64748B] mt-1">{detailsText}</p>
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            ) : (
                                <div className="p-6 text-center bg-[#F8FAFC] rounded-lg border-2 border-dashed border-[#E2E8F0]">
                                    <p className="text-[#64748B] text-sm">
                                        No payment methods available for this user.
                                    </p>
                                    <p className="text-[#94A3B8] text-xs mt-1">
                                        The user needs to add payment methods in their account settings.
                                    </p>
                                </div>
                            )}
                        </div>

                        {/* Note */}
                        <div className="p-3 bg-[#FFF7E6] rounded-lg">
                            <p className="text-xs text-[#F59E0B]">
                                Note: Changing the payment method will update how this payout is processed.
                                The teacher will be notified of this change.
                            </p>
                        </div>
                    </div>

                    <DialogFooter className="gap-2">
                        <Button
                            variant="outline"
                            onClick={() => setShowEditPaymentModal(false)}
                            disabled={isSavingPaymentMethod}
                            className="rounded-full px-6 text-[#EF4444] border-[#FEE2E2] hover:bg-[#FEF2F2]"
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={handleSavePaymentMethod}
                            disabled={isSavingPaymentMethod || selectedPaymentMethod === payoutRequest.payment_method}
                            className="rounded-full px-6 bg-[#14B8A6] hover:bg-[#129c8e] text-white"
                        >
                            {isSavingPaymentMethod ? (
                                <>
                                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                    Saving...
                                </>
                            ) : (
                                'Save Changes'
                            )}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}
