import React, { useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { CalendarDays, MoreVertical, Search, CheckCircle, Eye, XCircle, Loader2 } from 'lucide-react';
import { toast } from 'sonner';
import axios from 'axios';

/**
 * 🎨 FIGMA DESIGN REFERENCE
 * 
 * Component: Student Withdrawals Admin Table
 * Follows the same design pattern as TeacherPayouts
 * 
 * 📏 EXACT SPECIFICATIONS:
 * - Section title size: 20px, weight: 600
 * - Filters gap: 12px; inputs height: 48px; search button width: auto
 * - Table row height: 64px; header bg: #F6F7F9; cell padding-x: 20px
 * - Status colors: Pending #F59E0B, Approved #10B981, Rejected #EF4444
 * 
 * 📱 RESPONSIVE: Desktop layout primary
 * 🎯 STATES: Filters focus, row actions menu, approve/reject modals
 */

interface StudentWithdrawalRow {
    id: number;
    student_name?: string;
    email?: string;
    user?: {
        name: string;
        email: string;
    };
    amount: number;
    request_date: string;
    bank_name?: string;
    account_number?: string;
    account_name?: string;
    payment_method?: {
        bank_name?: string;
        account_number?: string;
        account_name?: string;
    };
    status: 'pending' | 'approved' | 'rejected' | 'processing' | 'completed' | string;
}

interface StudentWithdrawalsProps {
    withdrawalRequests: StudentWithdrawalRow[];
}

export default function StudentWithdrawals({ withdrawalRequests }: StudentWithdrawalsProps) {
    const [status, setStatus] = useState<string>('');
    const [dateRange, setDateRange] = useState<string>('');
    const [query, setQuery] = useState<string>('');
    
    // Local state for withdrawal requests (allows optimistic updates)
    const [localWithdrawals, setLocalWithdrawals] = useState<StudentWithdrawalRow[]>(withdrawalRequests || []);
    
    // Approve modal state
    const [showApproveModal, setShowApproveModal] = useState(false);
    const [selectedWithdrawal, setSelectedWithdrawal] = useState<StudentWithdrawalRow | null>(null);
    const [isApproving, setIsApproving] = useState(false);
    
    // Reject modal state
    const [showRejectModal, setShowRejectModal] = useState(false);
    const [rejectReason, setRejectReason] = useState('');
    const [isRejecting, setIsRejecting] = useState(false);
    
    // Update local state when props change
    React.useEffect(() => {
        setLocalWithdrawals(withdrawalRequests || []);
    }, [withdrawalRequests]);

    const filtered = useMemo(() => {
        return (localWithdrawals || []).filter((r) => {
            const q = query.trim().toLowerCase();
            const studentName = (r.user?.name || r.student_name || '').toLowerCase();
            const studentEmail = (r.user?.email || r.email || '').toLowerCase();
            const matchesQuery = !q || studentName.includes(q) || studentEmail.includes(q);
            const matchesStatus = !status || status === 'all' || r.status === status;

            return matchesQuery && matchesStatus;
        });
    }, [localWithdrawals, query, status]);

    const statusBadge = (s: string) => {
        const statusMap: Record<string, { bg: string; text: string; label: string }> = {
            'pending': { bg: 'bg-[#FFF7E6]', text: 'text-[#F59E0B]', label: 'Pending' },
            'approved': { bg: 'bg-[#E6FAF2]', text: 'text-[#0E9F6E]', label: 'Approved' },
            'processing': { bg: 'bg-[#E0F2FE]', text: 'text-[#0284C7]', label: 'Processing' },
            'completed': { bg: 'bg-[#D1FAE5]', text: 'text-[#059669]', label: 'Completed' },
            'rejected': { bg: 'bg-[#FEE2E2]', text: 'text-[#EF4444]', label: 'Rejected' },
        };

        const status = statusMap[s.toLowerCase()] || { bg: 'bg-gray-100', text: 'text-gray-800', label: s };
        return <Badge className={`${status.bg} ${status.text} border-0`}>{status.label}</Badge>;
    };

    const maskAccountNumber = (accountNumber: string): string => {
        if (!accountNumber || accountNumber.length < 4) return accountNumber;
        return '****' + accountNumber.slice(-4);
    };

    const getBankDetails = (withdrawal: StudentWithdrawalRow): string => {
        const bankName = withdrawal.payment_method?.bank_name || withdrawal.bank_name || 'N/A';
        const accountNumber = withdrawal.payment_method?.account_number || withdrawal.account_number || '';
        const maskedNumber = accountNumber ? maskAccountNumber(accountNumber) : '';
        
        return maskedNumber ? `${bankName} - ${maskedNumber}` : bankName;
    };

    const handleApproveClick = (withdrawal: StudentWithdrawalRow) => {
        setSelectedWithdrawal(withdrawal);
        setShowApproveModal(true);
    };

    const handleRejectClick = (withdrawal: StudentWithdrawalRow) => {
        setSelectedWithdrawal(withdrawal);
        setRejectReason('');
        setShowRejectModal(true);
    };

    const handleApproveConfirm = async () => {
        if (!selectedWithdrawal) return;

        setIsApproving(true);
        try {
            const response = await axios.post(`/admin/financial/student-withdrawals/${selectedWithdrawal.id}/approve`);
            
            if (response.data.success) {
                toast.success(response.data.message || 'Withdrawal approved successfully!');
                
                // Optimistically update the local state
                setLocalWithdrawals(prev => 
                    prev.map(w => 
                        w.id === selectedWithdrawal.id 
                            ? { ...w, status: 'approved' }
                            : w
                    )
                );
                
                setShowApproveModal(false);
                setSelectedWithdrawal(null);
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
        if (!selectedWithdrawal) return;

        if (!rejectReason.trim()) {
            toast.error('Please provide a reason for rejection');
            return;
        }

        setIsRejecting(true);
        try {
            const response = await axios.post(`/admin/financial/student-withdrawals/${selectedWithdrawal.id}/reject`, {
                reason: rejectReason
            });
            
            if (response.data.success) {
                toast.success(response.data.message || 'Withdrawal rejected successfully!');
                
                // Optimistically update the local state
                setLocalWithdrawals(prev => 
                    prev.map(w => 
                        w.id === selectedWithdrawal.id 
                            ? { ...w, status: 'rejected' }
                            : w
                    )
                );
                
                setShowRejectModal(false);
                setSelectedWithdrawal(null);
                setRejectReason('');
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

    const handleCancelApprove = () => {
        setShowApproveModal(false);
        setSelectedWithdrawal(null);
    };

    const handleCancelReject = () => {
        setShowRejectModal(false);
        setSelectedWithdrawal(null);
        setRejectReason('');
    };

    return (
        <div>
            {/* Section title */}
            <div className="mt-[28px]">
                <h2 className="text-[20px] font-semibold text-[#0F172A]">Student Withdrawal Requests</h2>
            </div>

            {/* Filters */}
            <div className="mt-[18px] flex flex-wrap items-center gap-[12px]">
                <div className="flex items-center bg-white rounded-full border border-[#E2E8F0] px-[16px] h-[48px] w-[340px]">
                    <Search className="w-[18px] h-[18px] text-[#94A3B8]" />
                    <Input
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder="Search by Name / Email"
                        className="border-0 focus-visible:ring-0 h-[46px] ml-[8px] placeholder:text-[#94A3B8]"
                    />
                </div>

                <Select value={status} onValueChange={setStatus}>
                    <SelectTrigger className="w-[150px] h-[48px] rounded-full border-[#E2E8F0] text-[#64748B]">
                        <SelectValue placeholder="Select Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All</SelectItem>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="approved">Approved</SelectItem>
                        <SelectItem value="processing">Processing</SelectItem>
                        <SelectItem value="completed">Completed</SelectItem>
                        <SelectItem value="rejected">Rejected</SelectItem>
                    </SelectContent>
                </Select>

                <Button variant="outline" className="h-[48px] rounded-full text-[#64748B] border-[#E2E8F0] px-[20px]">
                    <CalendarDays className="w-[16px] h-[16px] mr-[8px]" />
                    Date Range
                </Button>

                <Button className="h-[48px] rounded-full px-[28px] bg-[#14B8A6] hover:bg-[#129c8e] text-white font-medium">
                    Search
                </Button>
            </div>

            {/* Table */}
            <div className="mt-[18px] bg-white rounded-lg overflow-hidden border border-[#E2E8F0]">
                <div className="bg-[#F6F7F9] h-[44px] flex items-center px-[20px] text-[12px] text-[#64748B] font-medium">
                    <div className="w-[36px] flex items-center"><Checkbox /></div>
                    <div className="w-[100px]">Request ID</div>
                    <div className="w-[200px]">Student Name</div>
                    <div className="w-[120px]">Amount</div>
                    <div className="w-[220px]">Bank Details</div>
                    <div className="w-[140px]">Date</div>
                    <div className="w-[120px]">Status</div>
                    <div className="w-[80px] text-right">Actions</div>
                </div>

                {(filtered.length === 0) && (
                    <div className="px-[20px] py-[36px] text-sm text-[#64748B]">
                        No withdrawal requests found.
                    </div>
                )}

                {filtered.map((r) => (
                    <div
                        key={r.id}
                        className="h-[64px] flex items-center px-[20px] border-t border-[#F1F5F9] text-[14px] text-[#0F172A]"
                    >
                        <div className="w-[36px] flex items-center"><Checkbox /></div>
                        <div className="w-[100px] text-[#475569]">#{r.id}</div>
                        <div className="w-[200px]">{r.user?.name || r.student_name || 'N/A'}</div>
                        <div className="w-[120px] font-medium">₦{r.amount.toLocaleString()}</div>
                        <div className="w-[220px] text-[#475569]">{getBankDetails(r)}</div>
                        <div className="w-[140px]">
                            {new Date(r.request_date).toLocaleDateString(undefined, {
                                month: 'short',
                                day: '2-digit',
                                year: 'numeric'
                            })}
                        </div>
                        <div className="w-[120px]">{statusBadge(r.status)}</div>
                        <div className="w-[80px] text-right">
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <button className="inline-flex items-center justify-center w-[28px] h-[28px] rounded-full hover:bg-[#F1F5F9]">
                                        <MoreVertical className="w-[16px] h-[16px]" />
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" className="w-[200px] h-auto">
                                    {/* Only show Approve for pending requests */}
                                    {r.status === 'pending' && (
                                        <DropdownMenuItem
                                            className="flex items-center gap-3 py-3 cursor-pointer"
                                            onClick={() => handleApproveClick(r)}
                                        >
                                            <CheckCircle className="w-[18px] h-[18px] text-[#10B981]" />
                                            <span>Approve</span>
                                        </DropdownMenuItem>
                                    )}

                                    {/* Only show Reject for pending requests */}
                                    {r.status === 'pending' && (
                                        <DropdownMenuItem
                                            className="flex items-center gap-3 py-3 text-red-600 cursor-pointer"
                                            onClick={() => handleRejectClick(r)}
                                        >
                                            <XCircle className="w-[18px] h-[18px]" />
                                            <span>Reject</span>
                                        </DropdownMenuItem>
                                    )}

                                    {/* Always show View Details */}
                                    <DropdownMenuItem
                                        className="flex items-center gap-3 py-3 cursor-pointer"
                                        onClick={() => router.visit(route('admin.financial.student-withdrawals.show', r.id))}
                                    >
                                        <Eye className="w-[18px] h-[18px] text-[#64748B]" />
                                        <span>View Details</span>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                ))}
            </div>

            {/* Approve Confirmation Modal */}
            <Dialog open={showApproveModal} onOpenChange={setShowApproveModal}>
                <DialogContent className="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle className="text-xl font-semibold text-[#0F172A]">
                            Approve Withdrawal Request
                        </DialogTitle>
                        <DialogDescription className="text-[#64748B]">
                            Are you sure you want to approve this withdrawal request?
                        </DialogDescription>
                    </DialogHeader>

                    {selectedWithdrawal && (
                        <div className="py-4 space-y-3">
                            <div className="flex justify-between items-center py-2 border-b border-[#F1F5F9]">
                                <span className="text-sm text-[#64748B]">Student Name:</span>
                                <span className="text-sm font-medium text-[#0F172A]">
                                    {selectedWithdrawal.user?.name || selectedWithdrawal.student_name}
                                </span>
                            </div>
                            <div className="flex justify-between items-center py-2 border-b border-[#F1F5F9]">
                                <span className="text-sm text-[#64748B]">Amount:</span>
                                <span className="text-sm font-semibold text-[#0F172A]">
                                    ₦{selectedWithdrawal.amount.toLocaleString()}
                                </span>
                            </div>
                            <div className="flex justify-between items-center py-2 border-b border-[#F1F5F9]">
                                <span className="text-sm text-[#64748B]">Bank Details:</span>
                                <span className="text-sm text-[#0F172A]">
                                    {getBankDetails(selectedWithdrawal)}
                                </span>
                            </div>
                            <div className="mt-4 p-3 bg-[#E6FAF2] rounded-lg">
                                <p className="text-xs text-[#0E9F6E]">
                                    The withdrawal will be processed and the student will be notified. 
                                    Processing time: 1-3 business days.
                                </p>
                            </div>
                        </div>
                    )}

                    <DialogFooter className="gap-2">
                        <Button
                            variant="outline"
                            onClick={handleCancelApprove}
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
                            Please provide a reason for rejecting this withdrawal request.
                        </DialogDescription>
                    </DialogHeader>

                    {selectedWithdrawal && (
                        <div className="py-4 space-y-4">
                            <div className="space-y-3">
                                <div className="flex justify-between items-center py-2 border-b border-[#F1F5F9]">
                                    <span className="text-sm text-[#64748B]">Student Name:</span>
                                    <span className="text-sm font-medium text-[#0F172A]">
                                        {selectedWithdrawal.user?.name || selectedWithdrawal.student_name}
                                    </span>
                                </div>
                                <div className="flex justify-between items-center py-2 border-b border-[#F1F5F9]">
                                    <span className="text-sm text-[#64748B]">Amount:</span>
                                    <span className="text-sm font-semibold text-[#0F172A]">
                                        ₦{selectedWithdrawal.amount.toLocaleString()}
                                    </span>
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="reject-reason" className="text-sm font-medium text-[#0F172A]">
                                    Reason for Rejection *
                                </Label>
                                <Textarea
                                    id="reject-reason"
                                    placeholder="Enter the reason for rejecting this withdrawal request..."
                                    value={rejectReason}
                                    onChange={(e) => setRejectReason(e.target.value)}
                                    className="min-h-[100px] resize-none"
                                    disabled={isRejecting}
                                />
                                <p className="text-xs text-[#64748B]">
                                    This reason will be sent to the student via notification.
                                </p>
                            </div>

                            <div className="p-3 bg-[#FEE2E2] rounded-lg">
                                <p className="text-xs text-[#EF4444]">
                                    The withdrawal amount will be refunded to the student's wallet immediately.
                                </p>
                            </div>
                        </div>
                    )}

                    <DialogFooter className="gap-2">
                        <Button
                            variant="outline"
                            onClick={handleCancelReject}
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
        </div>
    );
}
