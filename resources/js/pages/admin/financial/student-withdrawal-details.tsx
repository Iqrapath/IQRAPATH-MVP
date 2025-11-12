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
 * Component: Student Withdrawal Request Detail View
 * Matches the exact design of Withdrawal-request-details.tsx
 * 
 * 📏 EXACT SPECIFICATIONS:
 * - Page title: 24px, weight: 600
 * - Info labels: 14px, weight: 500, color: #64748B
 * - Info values: 14px, weight: 400, color: #0F172A
 * - Status badge: Pending #F59E0B, Approved #10B981
 * - Stat cards: bg colors - blue #E0F2FE, teal #CCFBF1, yellow #FEF3C7
 * - Action buttons: Approve #14B8A6, Reject #EF4444
 * 
 * 📱 RESPONSIVE: Desktop layout primary
 * 🎯 STATES: Approve/reject modals, loading states
 */

interface Student {
    id: number;
    name: string;
    email: string;
}

interface ProcessedBy {
    id: number;
    name: string;
    email: string;
}

interface PaymentDetails {
    bank_name?: string;
    account_number?: string;
    account_name?: string;
}

interface WithdrawalRequest {
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
    user?: Student;
    processed_by?: ProcessedBy;
}

interface StudentWallet {
    balance: number;
    total_funded: number;
    total_withdrawn: number;
}

interface Props extends PageProps {
    withdrawalRequest: WithdrawalRequest;
    studentWallet?: StudentWallet;
}

export default function StudentWithdrawalDetails({ withdrawalRequest, studentWallet }: Props) {
    const [showRejectModal, setShowRejectModal] = useState(false);
    const [rejectReason, setRejectReason] = useState('');
    const [isRejecting, setIsRejecting] = useState(false);
    const [showApproveModal, setShowApproveModal] = useState(false);
    const [isApproving, setIsApproving] = useState(false);

    const student = withdrawalRequest.user;

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

    const handleApproveConfirm = async () => {
        setIsApproving(true);
        try {
            const response = await axios.post(`/admin/financial/student-withdrawals/${withdrawalRequest.id}/approve`);

            if (response.data.success) {
                toast.success(response.data.message || 'Withdrawal approved successfully!');
                setShowApproveModal(false);

                // Reload page data
                router.reload({ only: ['withdrawalRequest'] });
            } else {
                toast.error(response.data.message || 'Failed to approve withdrawal');
            }
        } catch (error: any) {
            console.error('Error approving withdrawal:', error);
            const errorMessage = error.response?.data?.message || 'An error occurred while approving the withdrawal';
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
            const response = await axios.post(`/admin/financial/student-withdrawals/${withdrawalRequest.id}/reject`, {
                reason: rejectReason
            });

            if (response.data.success) {
                toast.success(response.data.message || 'Withdrawal rejected successfully!');
                setShowRejectModal(false);
                setRejectReason('');

                // Reload page data
                router.reload({ only: ['withdrawalRequest'] });
            } else {
                toast.error(response.data.message || 'Failed to reject withdrawal');
            }
        } catch (error: any) {
            console.error('Error rejecting withdrawal:', error);
            const errorMessage = error.response?.data?.message || 'An error occurred while rejecting the withdrawal';
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
                                    <p className="text-sm font-medium text-[#64748B]">Student:</p>
                                    <p className="text-sm text-[#0F172A] mt-1">{student?.name || 'N/A'}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-[#64748B]">Email:</p>
                                    <p className="text-sm text-[#0F172A] mt-1">{student?.email || 'N/A'}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-[#64748B]">Request ID:</p>
                                    <p className="text-sm text-[#0F172A] mt-1">{withdrawalRequest.request_uuid}</p>
                                </div>
                            </div>

                            <div className="space-y-4">
                                <div>
                                    <p className="text-sm font-medium text-[#64748B]">Requested On:</p>
                                    <p className="text-sm text-[#0F172A] mt-1">
                                        {new Date(withdrawalRequest.request_date).toLocaleDateString('en-US', {
                                            month: 'long',
                                            day: '2-digit',
                                            year: 'numeric'
                                        })} – {new Date(withdrawalRequest.request_date).toLocaleTimeString('en-US', {
                                            hour: '2-digit',
                                            minute: '2-digit',
                                            hour12: true
                                        })}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-[#64748B]">Requested Amount:</p>
                                    <p className="text-sm font-semibold text-[#0F172A] mt-1">
                                        ₦{withdrawalRequest.amount.toLocaleString()}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-[#64748B]">Status</p>
                                    <div className="mt-1">{statusBadge(withdrawalRequest.status)}</div>
                                </div>
                            </div>
                        </div>

                        {/* Payment Method */}
                        <div className="mt-6 pt-6 border-t border-[#F1F5F9]">
                            <p className="text-sm font-medium text-[#64748B] mb-3">Payment Method:</p>
                            <div className="bg-[#F8FAFC] rounded-lg p-4">
                                <p className="text-sm text-[#0F172A]">
                                    {formatPaymentMethod(withdrawalRequest.payment_method)} – {withdrawalRequest.payment_details?.bank_name || 'N/A'},
                                    A/C: {withdrawalRequest.payment_details?.account_number
                                        ? '****' + withdrawalRequest.payment_details.account_number.slice(-4)
                                        : 'N/A'}
                                    ({withdrawalRequest.payment_details?.account_name || 'N/A'})
                                </p>
                            </div>
                        </div>
                    </Card>

                    {/* Wallet Balance at Time of Request */}
                    {studentWallet && (
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
                                        ₦{studentWallet.balance.toLocaleString()}
                                    </p>
                                </div>

                                <div className="bg-[#CCFBF1] rounded-lg p-4">
                                    <div className="flex items-center gap-2 mb-2">
                                        <TrendingUp className="w-5 h-5 text-[#14B8A6]" />
                                        <p className="text-xs text-[#64748B]">Total Funded</p>
                                    </div>
                                    <p className="text-xl font-semibold text-[#0F172A]">
                                        ₦{studentWallet.total_funded.toLocaleString()}
                                    </p>
                                </div>

                                <div className="bg-[#FEF3C7] rounded-lg p-4">
                                    <div className="flex items-center gap-2 mb-2">
                                        <DollarSign className="w-5 h-5 text-[#F59E0B]" />
                                        <p className="text-xs text-[#64748B]">Total Withdrawn</p>
                                    </div>
                                    <p className="text-xl font-semibold text-[#0F172A]">
                                        ₦{studentWallet.total_withdrawn.toLocaleString()}
                                    </p>
                                </div>
                            </div>

                            <div className="mt-4 text-right">
                                <Button
                                    variant="link"
                                    className="text-[#14B8A6] hover:text-[#129c8e] text-sm"
                                    onClick={() => router.visit(`/admin/teachers/${student?.id}/earnings`)}
                                >
                                    View Student Wallet →
                                </Button>
                            </div>
                        </Card>
                    )}



                    {/* Action Buttons */}
                    {withdrawalRequest.status === 'pending' && (
                        <div className="flex items-center justify-center gap-4">
                            <Button
                                onClick={handleApproveClick}
                                className="rounded-full px-8 py-6 bg-[#14B8A6] hover:bg-[#129c8e] text-white font-medium cursor-pointer"
                            >
                                Approve Withdrawal
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
                    {withdrawalRequest.processed_at && withdrawalRequest.processed_by && (
                        <Card className="p-6 bg-[#F8FAFC]">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm font-medium text-[#64748B]">Processed By:</p>
                                    <p className="text-sm text-[#0F172A] mt-1">{withdrawalRequest.processed_by.name}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-[#64748B]">Processed At:</p>
                                    <p className="text-sm text-[#0F172A] mt-1">
                                        {new Date(withdrawalRequest.processed_at).toLocaleDateString('en-US', {
                                            month: 'long',
                                            day: '2-digit',
                                            year: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit'
                                        })}
                                    </p>
                                </div>
                            </div>
                            {withdrawalRequest.notes && (
                                <div className="mt-4 pt-4 border-t border-[#E2E8F0]">
                                    <p className="text-sm font-medium text-[#64748B]">Notes:</p>
                                    <p className="text-sm text-[#0F172A] mt-1">{withdrawalRequest.notes}</p>
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
                            Approve Withdrawal Request
                        </DialogTitle>
                        <DialogDescription className="text-[#64748B]">
                            Are you sure you want to approve this Withdrawal request?
                        </DialogDescription>
                    </DialogHeader>

                    <div className="py-4 space-y-3">
                        <div className="flex justify-between items-center py-2 border-b border-[#F1F5F9]">
                            <span className="text-sm text-[#64748B]">Teacher Name:</span>
                            <span className="text-sm font-medium text-[#0F172A]">{student?.name}</span>
                        </div>
                        <div className="flex justify-between items-center py-2 border-b border-[#F1F5F9]">
                            <span className="text-sm text-[#64748B]">Amount:</span>
                            <span className="text-sm font-semibold text-[#0F172A]">
                                ₦{withdrawalRequest.amount.toLocaleString()}
                            </span>
                        </div>
                        <div className="mt-4 p-3 bg-[#E6FAF2] rounded-lg">
                            <p className="text-xs text-[#0E9F6E]">
                                The Withdrawal will be processed and the teacher will be notified.
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
                            Reject Withdrawal Request
                        </DialogTitle>
                        <DialogDescription className="text-[#64748B]">
                            Please provide a reason for rejecting this Withdrawal request.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="py-4 space-y-4">
                        <div className="space-y-3">
                            <div className="flex justify-between items-center py-2 border-b border-[#F1F5F9]">
                                <span className="text-sm text-[#64748B]">Teacher Name:</span>
                                <span className="text-sm font-medium text-[#0F172A]">{student?.name}</span>
                            </div>
                            <div className="flex justify-between items-center py-2 border-b border-[#F1F5F9]">
                                <span className="text-sm text-[#64748B]">Amount:</span>
                                <span className="text-sm font-semibold text-[#0F172A]">
                                    ₦{withdrawalRequest.amount.toLocaleString()}
                                </span>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="reject-reason" className="text-sm font-medium text-[#0F172A]">
                                Reason for Rejection *
                            </Label>
                            <Textarea
                                id="reject-reason"
                                placeholder="Enter the reason for rejecting this Withdrawal request..."
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
                                The Withdrawal amount will be refunded to the teacher's wallet immediately.
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
        </AdminLayout>
    );
}