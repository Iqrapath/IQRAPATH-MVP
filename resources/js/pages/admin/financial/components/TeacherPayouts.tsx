import React, { useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { CalendarDays, MoreVertical, Search, CheckCircle, Eye, Edit, Users, MessageSquare, XCircle } from 'lucide-react';
import ApprovePayoutModal from './ApprovePayoutModal';

interface PayoutRequestRow {
    id: number;
    teacher_name?: string;
    email?: string;
    teacher?: {
        name: string;
        email: string;
    };
    amount: number;
    request_date: string;
    payment_method: string;
    status: 'pending' | 'approved' | 'missed' | string;
}

interface TeacherPayoutsProps {
    pendingPayoutRequests: PayoutRequestRow[];
}

export default function TeacherPayouts({ pendingPayoutRequests }: TeacherPayoutsProps) {
    const [status, setStatus] = useState<string>('');
    const [method, setMethod] = useState<string>('');
    const [dateRange, setDateRange] = useState<string>('');
    const [query, setQuery] = useState<string>('');
    const [showApproveModal, setShowApproveModal] = useState(false);
    const [selectedPayout, setSelectedPayout] = useState<PayoutRequestRow | null>(null);

    const filtered = useMemo(() => {
        return (pendingPayoutRequests || []).filter((r) => {
            const q = query.trim().toLowerCase();
            const teacherName = (r.teacher?.name || r.teacher_name || '').toLowerCase();
            const teacherEmail = (r.teacher?.email || r.email || '').toLowerCase();
            const matchesQuery = !q || teacherName.includes(q) || teacherEmail.includes(q);
            const matchesStatus = !status || status === 'all' || r.status === status;

            // Normalize payment method for comparison (handle both "Bank Transfer" and "bank_transfer")
            const normalizeMethod = (m: string) => m.toLowerCase().replace(/[_\s]/g, '');
            const matchesMethod = !method || method === 'all' ||
                normalizeMethod(r.payment_method || '') === normalizeMethod(method);

            // dateRange UI only for now
            return matchesQuery && matchesStatus && matchesMethod;
        });
    }, [pendingPayoutRequests, query, status, method]);

    const formatPaymentMethod = (method: string): string => {
        return method
            .split('_')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ');
    };

    const statusBadge = (s: string) => {
        const statusMap: Record<string, { bg: string; text: string; label: string }> = {
            'pending': { bg: 'bg-[#FFF7E6]', text: 'text-[#F59E0B]', label: 'Pending' },
            'approved': { bg: 'bg-[#E6FAF2]', text: 'text-[#0E9F6E]', label: 'Approved' },
            'processing': { bg: 'bg-[#E0F2FE]', text: 'text-[#0284C7]', label: 'Processing' },
            'completed': { bg: 'bg-[#D1FAE5]', text: 'text-[#059669]', label: 'Completed' },
            'rejected': { bg: 'bg-[#FEE2E2]', text: 'text-[#EF4444]', label: 'Rejected' },
            'failed': { bg: 'bg-[#FEE2E2]', text: 'text-[#DC2626]', label: 'Failed' },
            'cancelled': { bg: 'bg-[#F3F4F6]', text: 'text-[#6B7280]', label: 'Cancelled' },
        };

        const status = statusMap[s.toLowerCase()] || { bg: 'bg-gray-100', text: 'text-gray-800', label: s };
        return <Badge className={`${status.bg} ${status.text} border-0`}>{status.label}</Badge>;
    };

    const handleApprove = (payout: PayoutRequestRow) => {
        setSelectedPayout(payout);
        setShowApproveModal(true);
    };

    const handleApproveSuccess = () => {
        // Refresh the page data without full reload (preserves toast)
        router.reload({ only: ['pendingPayoutRequests'] });
    };

    return (
        <div>
            {/* Section title */}
            <div className="mt-[28px]">
                <h2 className="text-[20px] font-semibold text-[#0F172A]">Withdrawal Request</h2>
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
                        <SelectItem value="failed">Failed</SelectItem>
                    </SelectContent>
                </Select>

                <Select value={method} onValueChange={setMethod}>
                    <SelectTrigger className="w-[180px] h-[48px] rounded-full border-[#E2E8F0] text-[#64748B]">
                        <SelectValue placeholder="Payment Method" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All</SelectItem>
                        <SelectItem value="Bank Transfer">Bank Transfer</SelectItem>
                        <SelectItem value="Credit/Debit Card">Credit/Debit Card</SelectItem>
                        <SelectItem value="Mobile Wallet">Mobile Wallet</SelectItem>
                        <SelectItem value="PayPal">PayPal</SelectItem>
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
                    <div className="w-[220px]">Teacher Name</div>
                    <div className="w-[220px]">Email</div>
                    <div className="w-[120px]">Amount</div>
                    <div className="w-[160px]">Requested On</div>
                    <div className="w-[160px]">Payment Method</div>
                    <div className="w-[120px]">Status</div>
                    <div className="w-[80px] text-right">Actions</div>
                </div>

                {(filtered.length === 0) && (
                    <div className="px-[20px] py-[36px] text-sm text-[#64748B]">
                        No payout requests found.
                    </div>
                )}

                {filtered.map((r) => (
                    <div
                        key={r.id}
                        className="h-[64px] flex items-center px-[20px] border-t border-[#F1F5F9] text-[14px] text-[#0F172A]"
                    >
                        <div className="w-[36px] flex items-center"><Checkbox /></div>
                        <div className="w-[220px]">{r.teacher?.name || r.teacher_name || 'N/A'}</div>
                        <div className="w-[220px] text-[#475569]">{r.teacher?.email || r.email || 'N/A'}</div>
                        <div className="w-[120px]">₦{r.amount.toLocaleString()}</div>
                        <div className="w-[160px]">
                            {new Date(r.request_date).toLocaleDateString(undefined, {
                                month: 'short',
                                day: '2-digit',
                                year: 'numeric'
                            })}
                        </div>
                        <div className="w-[160px]">{formatPaymentMethod(r.payment_method)}</div>
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
                                            className="flex items-center gap-3 py-3"
                                            onClick={() => handleApprove(r)}
                                        >
                                            <CheckCircle className="w-[18px] h-[18px] text-[#10B981]" />
                                            <span>Approve</span>
                                        </DropdownMenuItem>
                                    )}

                                    {/* Only show Reject for pending requests */}
                                    {r.status === 'pending' && (
                                        <DropdownMenuItem
                                            className="flex items-center gap-3 py-3 text-red-600"
                                        >
                                            <XCircle className="w-[18px] h-[18px]" />
                                            <span>Reject</span>
                                        </DropdownMenuItem>
                                    )}

                                    {/* Always show View Details */}
                                    <DropdownMenuItem
                                        onClick={() => router.visit(route('admin.financial.payout-requests.show', r.id))}
                                        className="flex items-center gap-3 py-3"
                                    >
                                        <Eye className="w-[18px] h-[18px] text-[#64748B]" />
                                        <span>View Details</span>
                                    </DropdownMenuItem>

                                    {/* Show status-specific actions */}
                                    {r.status === 'approved' && (
                                        <DropdownMenuItem className="flex items-center gap-3 py-3 text-blue-600">
                                            <MessageSquare className="w-[18px] h-[18px]" />
                                            <span>Check Status</span>
                                        </DropdownMenuItem>
                                    )}

                                    {r.status === 'completed' && (
                                        <DropdownMenuItem className="flex items-center gap-3 py-3 text-green-600">
                                            <CheckCircle className="w-[18px] h-[18px]" />
                                            <span>View Receipt</span>
                                        </DropdownMenuItem>
                                    )}
                                    <DropdownMenuItem className="flex items-center gap-3 py-3">
                                        <MessageSquare className="w-[18px] h-[18px] text-[#64748B]" />
                                        <span>Send Payout</span>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem className="flex items-center gap-3 py-3 text-[#EF4444]">
                                        <XCircle className="w-[18px] h-[18px] text-[#EF4444]" />
                                        <span>Reject</span>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                ))}
            </div>

            {/* Approve Modal */}
            <ApprovePayoutModal
                isOpen={showApproveModal}
                onClose={() => setShowApproveModal(false)}
                onSuccess={handleApproveSuccess}
                payout={selectedPayout}
            />
        </div>
    );
}
