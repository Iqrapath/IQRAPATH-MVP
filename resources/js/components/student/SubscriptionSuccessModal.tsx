import { Dialog, DialogContent } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { X } from 'lucide-react';
import { router } from '@inertiajs/react';

/**
 * 🎨 FIGMA DESIGN REFERENCE
 * 
 * Component: Subscription Success Modal
 * Design: Enrollment confirmation modal with payment receipt
 * 
 * 📏 EXACT SPECIFICATIONS:
 * - Modal max-width: 600px
 * - Title: 24px, weight: 600, color: #1E293B
 * - Success message: 18px, weight: 500, color: #14B8A6
 * - Receipt section bg: #F8FAFC, padding: 24px, rounded: 12px
 * - Receipt title: 20px, weight: 600, color: #1E293B
 * - Receipt details: 16px, color: #64748B
 * - Button height: 48px, rounded: 24px
 * - Primary button bg: #14B8A6, text: white
 * - Secondary button: text color #64748B
 * 
 * 📱 RESPONSIVE: Centered modal, mobile-friendly
 * 🎯 STATES: Close button hover, button interactions
 */

interface SubscriptionSuccessModalProps {
    isOpen: boolean;
    onClose: () => void;
    subscription: {
        id: number;
        subscription_uuid: string;
        plan: {
            name: string;
        };
    };
    transaction: {
        amount: number;
        currency: string;
        payment_method: string;
    };
    userName: string;
}

export default function SubscriptionSuccessModal({
    isOpen,
    onClose,
    subscription,
    transaction,
    userName,
}: SubscriptionSuccessModalProps) {
    const formatCurrency = (amount: number, currency: string): string => {
        if (currency === 'NGN') {
            return `₦${amount.toLocaleString()}`;
        } else if (currency === 'USD') {
            return `$${amount.toLocaleString()}`;
        }
        return `${currency} ${amount.toLocaleString()}`;
    };

    const formatPaymentMethod = (method: string): string => {
        const methodMap: Record<string, string> = {
            'card': 'Debit/Credit Card',
            'wallet': 'Wallet',
            'paypal': 'PayPal',
            'bank_transfer': 'Bank Transfer',
        };
        return methodMap[method.toLowerCase()] || method;
    };

    const handleScheduleClass = () => {
        onClose();
        router.visit(route('student.book-class'));
    };

    const handleSkip = () => {
        onClose();
        router.visit(route('student.plans.index'));
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="max-w-[600px] p-0 gap-0 bg-white">
                {/* Close button */}
                <button
                    onClick={onClose}
                    className="absolute right-6 top-6 rounded-full p-2 hover:bg-gray-100 transition-colors z-10"
                >
                    <X className="h-5 w-5 text-gray-500" />
                </button>

                {/* Content */}
                <div className="px-12 py-10">
                    {/* Title */}
                    <h2 className="text-[24px] font-semibold text-[#1E293B] text-center mb-6">
                        Thank you for enrolling, {userName}!
                    </h2>

                    {/* Success message */}
                    <div className="flex items-center justify-center gap-2 mb-8">
                        <span className="text-[24px]">🎉</span>
                        <p className="text-[18px] font-medium text-[#14B8A6] text-center">
                            You've successfully subscribed to the {subscription.plan.name}.
                        </p>
                    </div>

                    {/* Payment Receipt */}
                    <div className="bg-[#F8FAFC] rounded-[12px] p-6 mb-6">
                        <h3 className="text-[20px] font-semibold text-[#1E293B] mb-4">
                            Payment Receipt:
                        </h3>

                        <div className="space-y-3">
                            <div className="flex justify-between items-center">
                                <span className="text-[16px] text-[#64748B]">Amount:</span>
                                <span className="text-[16px] font-medium text-[#1E293B]">
                                    {formatCurrency(transaction.amount, transaction.currency)}
                                </span>
                            </div>

                            <div className="flex justify-between items-center">
                                <span className="text-[16px] text-[#64748B]">Method:</span>
                                <span className="text-[16px] font-medium text-[#1E293B]">
                                    {formatPaymentMethod(transaction.payment_method)}
                                </span>
                            </div>

                            <div className="flex justify-between items-center">
                                <span className="text-[16px] text-[#64748B]">Subscription ID:</span>
                                <span className="text-[16px] font-medium text-[#1E293B] font-mono">
                                    #{subscription.subscription_uuid.substring(0, 12).toUpperCase()}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Schedule First Class section */}
                    <div className="mb-6">
                        <p className="text-[16px] font-medium text-[#14B8A6] mb-2">
                            Schedule First Class
                        </p>
                        <p className="text-[14px] text-[#64748B]">
                            Confirmation sent via Email and SMS.
                        </p>
                    </div>

                    {/* Action buttons */}
                    <div className="flex items-center justify-center gap-4">
                        <Button
                            onClick={handleScheduleClass}
                            className="h-[48px] px-8 rounded-[24px] bg-[#14B8A6] hover:bg-[#129c8e] text-white font-medium text-[16px]"
                        >
                            Schedule First Class
                        </Button>

                        <Button
                            onClick={handleSkip}
                            variant="ghost"
                            className="h-[48px] px-8 rounded-[24px] text-[#64748B] hover:text-[#475569] hover:bg-gray-100 font-medium text-[16px]"
                        >
                            Skip For Now
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
