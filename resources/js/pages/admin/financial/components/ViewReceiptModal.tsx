import { useEffect } from 'react';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { X, Download, Printer, Mail } from 'lucide-react';
import { toast } from 'sonner';

// Ensure body scroll is restored when modal closes
const restoreBodyScroll = () => {
    document.body.style.overflow = '';
    document.body.style.pointerEvents = '';
};

interface PayoutRequest {
    id: number;
    request_uuid?: string;
    teacher?: {
        name: string;
        email: string;
    };
    teacher_name?: string;
    email?: string;
    amount: number;
    currency?: string;
    payment_method: string;
    request_date: string;
    processed_date?: string;
    processed_at?: string;
    external_reference?: string;
    external_transfer_code?: string;
    transaction?: {
        id: number;
        reference?: string;
        external_reference?: string;
    };
    payment_details?: {
        bank_name?: string;
        account_name?: string;
        account_number?: string;
        last_four?: string;
    };
}

interface ViewReceiptModalProps {
    isOpen: boolean;
    onClose: () => void;
    payout: PayoutRequest | null;
}

export default function ViewReceiptModal({ isOpen, onClose, payout }: ViewReceiptModalProps) {
    // Restore body scroll when modal closes
    useEffect(() => {
        if (!isOpen) {
            restoreBodyScroll();
        }
    }, [isOpen]);

    // Cleanup on unmount
    useEffect(() => {
        return () => {
            restoreBodyScroll();
        };
    }, []);

    if (!isOpen || !payout) return null;

    const teacherName = payout.teacher?.name || payout.teacher_name || 'N/A';
    const teacherEmail = payout.teacher?.email || payout.email || 'N/A';
    const currency = payout.currency || 'NGN';
    const receiptNumber = payout.request_uuid || `RCP-${payout.id}`;
    
    // Try multiple sources for transaction ID (in order of preference):
    // 1. external_reference - Set when admin manually marks as completed
    // 2. external_transfer_code - Set by PayStack for automatic transfers
    // 3. transaction.reference - From related transaction record
    // 4. transaction.external_reference - Alternative transaction reference
    // 5. Fallback to payout ID if none available
    const transactionId = payout.external_reference 
        || payout.external_transfer_code 
        || payout.transaction?.reference 
        || payout.transaction?.external_reference 
        || `PAYOUT-${payout.id}`;

    const formatPaymentMethod = (method: string): string => {
        return method
            .split('_')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ');
    };

    const formatDate = (dateString: string): string => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const handlePrint = () => {
        window.print();
        toast.success('Print dialog opened');
    };

    const handleDownloadPDF = () => {
        // TODO: Implement PDF generation
        toast.info('PDF download will be implemented soon');
    };

    const handleEmailReceipt = () => {
        // TODO: Implement email sending
        toast.info('Email receipt will be implemented soon');
    };

    const handleClose = () => {
        restoreBodyScroll();
        onClose();
    };

    return (
        <Dialog 
            open={isOpen} 
            onOpenChange={(open) => {
                if (!open) {
                    handleClose();
                }
            }}
            modal={true}
        >
            <DialogContent 
                className="max-w-[800px] max-h-[90vh] overflow-y-auto p-0 print:max-h-none print:overflow-visible print:max-w-none"
                onPointerDownOutside={(e) => {
                    // Allow closing by clicking outside
                }}
            >
                {/* Header - No Print */}
                <div className="sticky top-0 bg-white border-b border-[#E2E8F0] px-8 py-4 flex items-center justify-between print:hidden">
                    <h2 className="text-xl font-semibold text-[#0F172A]">Payment Receipt</h2>
                    <button
                        onClick={handleClose}
                        className="text-[#64748B] hover:text-[#0F172A] transition-colors"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                {/* Receipt Content - Printable */}
                <div className="p-8 print:p-12 bg-white" id="receipt-content">
                    {/* Company Header */}
                    <div className="text-center mb-8 pb-6 border-b-2 border-[#E2E8F0]">
                        <h1 className="text-3xl font-bold text-[#14B8A6] mb-2">IQRAQUEST</h1>
                        <p className="text-sm text-[#64748B]">Islamic Learning & Quran Memorization Platform</p>
                        <p className="text-xs text-[#64748B] mt-1">www.iqraquest.com | support@iqraquest.com</p>
                    </div>

                    {/* Receipt Title & Number */}
                    <div className="flex items-start justify-between mb-8">
                        <div>
                            <h2 className="text-2xl font-bold text-[#1E293B] mb-1">Payment Receipt</h2>
                            <p className="text-sm text-[#64748B]">Official Transaction Record</p>
                        </div>
                        <div className="text-right">
                            <p className="text-xs text-[#64748B] mb-1">Receipt Number</p>
                            <p className="text-base font-bold text-[#14B8A6]">{receiptNumber}</p>
                        </div>
                    </div>

                    {/* Receipt Details Grid */}
                    <div className="grid grid-cols-2 gap-6 mb-8">
                        {/* Left Column - Recipient Details */}
                        <div>
                            <h3 className="text-sm font-semibold text-[#64748B] uppercase mb-3 pb-2 border-b border-[#E2E8F0]">
                                Paid To
                            </h3>
                            <div className="space-y-2">
                                <div>
                                    <p className="text-xs text-[#64748B]">Name</p>
                                    <p className="text-base font-semibold text-[#1E293B]">{teacherName}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-[#64748B]">Email</p>
                                    <p className="text-sm text-[#475569]">{teacherEmail}</p>
                                </div>
                                {payout.payment_details?.bank_name && (
                                    <>
                                        <div className="pt-2">
                                            <p className="text-xs text-[#64748B]">Bank</p>
                                            <p className="text-sm text-[#1E293B]">{payout.payment_details.bank_name}</p>
                                        </div>
                                        {payout.payment_details.account_name && (
                                            <div>
                                                <p className="text-xs text-[#64748B]">Account Name</p>
                                                <p className="text-sm text-[#1E293B]">{payout.payment_details.account_name}</p>
                                            </div>
                                        )}
                                        {payout.payment_details.account_number && (
                                            <div>
                                                <p className="text-xs text-[#64748B]">Account Number</p>
                                                <p className="text-sm font-mono text-[#1E293B]">{payout.payment_details.account_number}</p>
                                            </div>
                                        )}
                                    </>
                                )}
                            </div>
                        </div>

                        {/* Right Column - Transaction Details */}
                        <div>
                            <h3 className="text-sm font-semibold text-[#64748B] uppercase mb-3 pb-2 border-b border-[#E2E8F0]">
                                Transaction Details
                            </h3>
                            <div className="space-y-2">
                                <div className="flex justify-between">
                                    <span className="text-xs text-[#64748B]">Payment Method</span>
                                    <span className="text-sm font-medium text-[#1E293B]">
                                        {formatPaymentMethod(payout.payment_method)}
                                    </span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-xs text-[#64748B]">Transaction ID</span>
                                    <span className="text-xs font-mono text-[#1E293B]">{transactionId}</span>
                                </div>
                                <div className="flex justify-between pt-2">
                                    <span className="text-xs text-[#64748B]">Request Date</span>
                                    <span className="text-xs text-[#1E293B]">
                                        {formatDate(payout.request_date)}
                                    </span>
                                </div>
                                {payout.processed_at && (
                                    <div className="flex justify-between">
                                        <span className="text-xs text-[#64748B]">Completed Date</span>
                                        <span className="text-xs text-[#1E293B]">
                                            {formatDate(payout.processed_at)}
                                        </span>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Amount Section */}
                    <div className="bg-[#14B8A6] rounded-lg p-6 mb-8 text-center">
                        <p className="text-sm text-white/80 mb-2">Total Amount Paid</p>
                        <div className="flex items-center justify-center gap-2 mb-1">
                            <span className="text-4xl font-bold text-white">
                                {currency === 'NGN' ? '₦' : currency}
                            </span>
                            <span className="text-4xl font-bold text-white">
                                {payout.amount.toLocaleString()}
                            </span>
                        </div>
                        <div className="inline-flex items-center gap-2 bg-white/20 px-4 py-1.5 rounded-full mt-3">
                            <svg className="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                            </svg>
                            <span className="text-xs font-semibold text-white uppercase">Payment Completed</span>
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="border-t border-[#E2E8F0] pt-6 text-center space-y-2">
                        <p className="text-xs text-[#64748B]">
                            This is an official payment receipt from IQRAQUEST. Please retain for your records.
                        </p>
                        <p className="text-xs text-[#64748B]">
                            Generated on {new Date().toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            })}
                        </p>
                        <p className="text-xs text-[#64748B]">
                            © {new Date().getFullYear()} IQRAQUEST. All rights reserved.
                        </p>
                    </div>
                </div>

                {/* Action Buttons - No Print */}
                <div className="sticky bottom-0 bg-white border-t border-[#E2E8F0] px-8 py-4 flex items-center justify-end gap-3 print:hidden">
                    <Button
                        variant="outline"
                        onClick={handleEmailReceipt}
                        className="flex items-center gap-2"
                    >
                        <Mail className="w-4 h-4" />
                        Email Receipt
                    </Button>
                    <Button
                        variant="outline"
                        onClick={handleDownloadPDF}
                        className="flex items-center gap-2"
                    >
                        <Download className="w-4 h-4" />
                        Download PDF
                    </Button>
                    <Button
                        onClick={handlePrint}
                        className="bg-[#14B8A6] hover:bg-[#0F9688] text-white flex items-center gap-2"
                    >
                        <Printer className="w-4 h-4" />
                        Print Receipt
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}
