import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { HelpCircle, Loader2 } from 'lucide-react';
import { toast } from 'sonner';
import axios from 'axios';

interface PayoutRequest {
    id: number;
    teacher?: {
        name: string;
        email: string;
    };
    teacher_name?: string;
    amount: number;
    payment_method: string;
    payment_details?: {
        payment_method_id?: number;
        bank_name?: string;
        account_name?: string;
        last_four?: string;
    };
}

interface ApprovePayoutModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSuccess: () => void;
    payout: PayoutRequest | null;
}

export default function ApprovePayoutModal({
    isOpen,
    onClose,
    onSuccess,
    payout
}: ApprovePayoutModalProps) {
    const [loading, setLoading] = useState(false);

    if (!isOpen || !payout) return null;

    const teacherName = payout.teacher?.name || payout.teacher_name || 'N/A';
    
    // Format payment method
    const formatPaymentMethod = (method: string): string => {
        return method
            .split('_')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ');
    };
    
    const paymentMethod = formatPaymentMethod(payout.payment_method);
    const bankName = payout.payment_details?.bank_name || 'GTB';
    const lastFour = payout.payment_details?.last_four || '0000';

    const handleApprove = async () => {
        setLoading(true);
        try {
            const response = await axios.post(`/admin/financial/payout-requests/${payout.id}/approve`, {}, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            if (response.data.success) {
                // Show different toast based on whether automatic payout succeeded
                if (response.data.warning) {
                    toast.warning(response.data.message, {
                        description: 'Check the payout notes for details. You may need to process this manually.',
                        duration: 6000,
                    });
                } else {
                    toast.success(response.data.message, {
                        description: 'Payment gateway is processing the transfer.',
                        duration: 4000,
                    });
                }
                onSuccess();
                onClose();
            }
        } catch (error: any) {
            console.error('Error approving payout:', error);
            const errorMessage = error.response?.data?.message || 'Failed to approve payout';
            toast.error(errorMessage);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-[24px] p-12 w-full max-w-[600px] relative">
                {/* Close Button */}
                <button
                    onClick={onClose}
                    className="absolute top-8 right-8 text-gray-500 hover:text-gray-700"
                >
                    <svg xmlns="XXXXXXXXXXXXXXXXXXXXXXXXXX" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                {/* Icon */}
                <div className="flex justify-center mb-8">
                    <div className="w-[80px] h-[80px] rounded-full bg-[#E0F2F1] flex items-center justify-center">
                        <HelpCircle className="w-[40px] h-[40px] text-[#14B8A6]" strokeWidth={2} />
                    </div>
                </div>

                {/* Title */}
                <h2 className="text-[22px] font-normal text-center text-[#64748B] leading-relaxed mb-8">
                    Are you sure you want to approve this withdrawal<br />
                    request for <span className="font-semibold text-gray-900">₦{payout.amount.toLocaleString()}</span>?
                </h2>

                {/* Details */}
                <div className="space-y-4 mb-10">
                    <div className="flex items-center justify-center gap-3">
                        <span className="text-[#64748B] font-normal">Teacher:</span>
                        <span className="bg-[#FFF9E6] text-[#14B8A6] px-5 py-1.5 rounded-full font-medium">
                            {teacherName}
                        </span>
                    </div>

                    <div className="flex items-center justify-center gap-3">
                        <span className="text-[#64748B] font-normal">Payment Method:</span>
                        <span className="bg-[#FFF9E6] text-[#14B8A6] px-5 py-1.5 rounded-full font-medium">
                            {paymentMethod} - {bankName}: {lastFour}
                        </span>
                    </div>
                </div>

                {/* Action Buttons */}
                <div className="flex justify-center gap-4">
                    <Button
                        onClick={handleApprove}
                        disabled={loading}
                        className="bg-[#14B8A6] hover:bg-[#0F9688] text-white px-10 py-6 rounded-full text-[15px] font-medium shadow-none"
                    >
                        {loading ? (
                            <>
                                <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                Processing...
                            </>
                        ) : (
                            "Yes, I'm sure"
                        )}
                    </Button>
                    <Button
                        onClick={onClose}
                        disabled={loading}
                        variant="outline"
                        className="border-2 border-[#14B8A6] text-[#14B8A6] hover:bg-[#E0F2F1] hover:text-[#14B8A6] px-10 py-6 rounded-full text-[15px] font-medium shadow-none"
                    >
                        No, cancel
                    </Button>
                </div>
            </div>
        </div>
    );
}
