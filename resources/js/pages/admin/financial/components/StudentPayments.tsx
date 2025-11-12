import { useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Search, MoreVertical, Eye } from 'lucide-react';

/**
 * 🎨 FIGMA DESIGN REFERENCE
 * 
 * Component: Student Payments List
 * Based on the provided design image
 * 
 * 📏 EXACT SPECIFICATIONS:
 * - Section title size: 20px, weight: 600
 * - Search input: height 48px, rounded-full, border #E2E8F0
 * - Filter dropdowns: height 48px, rounded-full
 * - Table row height: 64px; header bg: #F6F7F9
 * - Status colors: Successful #10B981, Failed #EF4444
 * 
 * 📱 RESPONSIVE: Desktop layout primary
 * 🎯 STATES: Filters focus, row actions menu
 */

interface StudentPaymentRow {
    id: number;
    date: string;
    student_name: string;
    student_email?: string;
    plan: string;
    amount: number;
    currency?: string;
    payment_method: string;
    status: 'successful' | 'failed' | 'pending' | 'refunded' | string;
}

interface StudentPaymentsProps {
    payments: StudentPaymentRow[];
}

export default function StudentPayments({ payments }: StudentPaymentsProps) {
    const [planType, setPlanType] = useState<string>('');
    const [paymentMethod, setPaymentMethod] = useState<string>('');
    const [userType, setUserType] = useState<string>('');
    const [currencyFilter, setCurrencyFilter] = useState<string>('');
    const [query, setQuery] = useState<string>('');

    const filtered = useMemo(() => {
        return (payments || []).filter((payment) => {
            const q = query.trim().toLowerCase();
            const studentName = payment.student_name.toLowerCase();
            const studentEmail = (payment.student_email || '').toLowerCase();
            const matchesQuery = !q || studentName.includes(q) || studentEmail.includes(q);
            
            const matchesPlan = !planType || planType === 'all' || payment.plan.toLowerCase().includes(planType.toLowerCase());
            const matchesPaymentMethod = !paymentMethod || paymentMethod === 'all' || payment.payment_method.toLowerCase().includes(paymentMethod.toLowerCase());
            const matchesCurrency = !currencyFilter || currencyFilter === 'all' || (payment.currency || 'NGN') === currencyFilter;

            return matchesQuery && matchesPlan && matchesPaymentMethod && matchesCurrency;
        });
    }, [payments, query, planType, paymentMethod, currencyFilter]);

    const statusBadge = (status: string) => {
        const statusMap: Record<string, { bg: string; text: string; label: string }> = {
            'successful': { bg: 'bg-[#D1FAE5]', text: 'text-[#059669]', label: 'Successful' },
            'failed': { bg: 'bg-[#FEE2E2]', text: 'text-[#EF4444]', label: 'Failed' },
            'pending': { bg: 'bg-[#FFF7E6]', text: 'text-[#F59E0B]', label: 'Pending' },
            'refunded': { bg: 'bg-[#F3F4F6]', text: 'text-[#6B7280]', label: 'Refunded' },
        };

        const s = statusMap[status.toLowerCase()] || { bg: 'bg-gray-100', text: 'text-gray-800', label: status };
        return <Badge className={`${s.bg} ${s.text} border-0`}>{s.label}</Badge>;
    };

    const formatDate = (dateString: string): string => {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric'
        });
    };

    const formatCurrency = (amount: number, currency: string = 'NGN'): string => {
        if (currency === 'NGN') {
            return `₦${amount.toLocaleString()}`;
        } else if (currency === 'USD') {
            return `$${amount.toLocaleString()}`;
        }
        return `${currency} ${amount.toLocaleString()}`;
    };

    return (
        <div>
            {/* Section title */}
            <div className="mt-[28px]">
                <h2 className="text-[20px] font-semibold text-[#0F172A]">Payment list</h2>
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

                <Select value={planType} onValueChange={setPlanType}>
                    <SelectTrigger className="w-[150px] h-[48px] rounded-full border-[#E2E8F0] text-[#64748B]">
                        <SelectValue placeholder="Plan Type" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Plans</SelectItem>
                        <SelectItem value="quran">Quran Plans</SelectItem>
                        <SelectItem value="amma">Juz' Amma</SelectItem>
                        <SelectItem value="full">Full Quran</SelectItem>
                    </SelectContent>
                </Select>

                <Select value={paymentMethod} onValueChange={setPaymentMethod}>
                    <SelectTrigger className="w-[180px] h-[48px] rounded-full border-[#E2E8F0] text-[#64748B]">
                        <SelectValue placeholder="Payment Method" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Methods</SelectItem>
                        <SelectItem value="card">Debit/Credit Card</SelectItem>
                        <SelectItem value="bank">Bank Transfer</SelectItem>
                        <SelectItem value="paypal">PayPal</SelectItem>
                    </SelectContent>
                </Select>

                <Select value={userType} onValueChange={setUserType}>
                    <SelectTrigger className="w-[150px] h-[48px] rounded-full border-[#E2E8F0] text-[#64748B]">
                        <SelectValue placeholder="User Type" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Users</SelectItem>
                        <SelectItem value="student">Student</SelectItem>
                        <SelectItem value="guardian">Guardian</SelectItem>
                    </SelectContent>
                </Select>

                <Select value={currencyFilter} onValueChange={setCurrencyFilter}>
                    <SelectTrigger className="w-[160px] h-[48px] rounded-full border-[#E2E8F0] text-[#64748B]">
                        <SelectValue placeholder="Currency Filter" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Currencies</SelectItem>
                        <SelectItem value="NGN">NGN (₦)</SelectItem>
                        <SelectItem value="USD">USD ($)</SelectItem>
                    </SelectContent>
                </Select>

                <Button className="h-[48px] rounded-full px-[28px] bg-[#14B8A6] hover:bg-[#129c8e] text-white font-medium">
                    Search
                </Button>
            </div>

            {/* Table */}
            <div className="mt-[18px] bg-white rounded-lg overflow-hidden border border-[#E2E8F0]">
                <div className="bg-[#F6F7F9] h-[44px] flex items-center px-[20px] text-[12px] text-[#64748B] font-medium">
                    <div className="w-[36px] flex items-center"><Checkbox /></div>
                    <div className="w-[100px]">Date</div>
                    <div className="w-[200px]">Name</div>
                    <div className="w-[180px]">Plan</div>
                    <div className="w-[140px]">Amount</div>
                    <div className="w-[180px]">Payment Method</div>
                    <div className="w-[120px]">Status</div>
                    <div className="w-[80px] text-right">Action</div>
                </div>

                {(filtered.length === 0) && (
                    <div className="px-[20px] py-[36px] text-sm text-[#64748B]">
                        No payments found.
                    </div>
                )}

                {filtered.map((payment) => (
                    <div
                        key={payment.id}
                        className="h-[64px] flex items-center px-[20px] border-t border-[#F1F5F9] text-[14px] text-[#0F172A]"
                    >
                        <div className="w-[36px] flex items-center"><Checkbox /></div>
                        <div className="w-[100px] text-[#475569]">{formatDate(payment.date)}</div>
                        <div className="w-[200px]">{payment.student_name}</div>
                        <div className="w-[180px] text-[#64748B]">{payment.plan}</div>
                        <div className="w-[140px] font-medium">{formatCurrency(payment.amount, payment.currency)}</div>
                        <div className="w-[180px] text-[#64748B]">{payment.payment_method}</div>
                        <div className="w-[120px]">{statusBadge(payment.status)}</div>
                        <div className="w-[80px] text-right">
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <button className="inline-flex items-center justify-center w-[28px] h-[28px] rounded-full hover:bg-[#F1F5F9]">
                                        <MoreVertical className="w-[16px] h-[16px]" />
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" className="w-[200px]">
                                    <DropdownMenuItem
                                        className="flex items-center gap-3 py-3 cursor-pointer"
                                        onClick={() => router.visit(route('admin.financial.payments.show', payment.id))}
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
        </div>
    );
}
