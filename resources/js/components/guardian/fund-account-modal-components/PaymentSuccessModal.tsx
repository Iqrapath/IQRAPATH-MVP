/**
 * 🎨 FIGMA DESIGN REFERENCE
 * 
 * Component: PaymentSuccessModal
 * Design: Success confirmation with checkmark, amount, and message
 * 
 * 📏 EXACT SPECIFICATIONS:
 * - Background: White with light teal decorative circles
 * - Checkmark: Teal circle (#2C7870) with white check
 * - Typography: Large amount display, success message
 * - Button: Teal rounded-full "Got It, JazakaAllahu Khair!"
 * - Colors: #2C7870 (teal), #1E293B (text), #E0F2F1 (light teal bg)
 * 
 * 📱 RESPONSIVE: Desktop-first design
 * 🎯 STATES: Success animation, auto-close option
 */

import { useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Check } from 'lucide-react';
import { PAYMENT_CONFIG } from './PaymentConfig';

interface PaymentSuccessModalProps {
    isOpen: boolean;
    amount?: string;
    paymentMethod: 'credit_card' | 'bank_transfer' | 'paypal';
    newBalance?: number;
    transactionId?: string;
    onClose: () => void;
}

export default function PaymentSuccessModal({
    isOpen,
    amount,
    paymentMethod,
    newBalance,
    transactionId,
    onClose
}: PaymentSuccessModalProps) {
    // Auto-close after 5 seconds (optional)
    useEffect(() => {
        if (isOpen && paymentMethod === 'credit_card') {
            const timer = setTimeout(() => {
                onClose();
            }, 5000);
            return () => clearTimeout(timer);
        }
    }, [isOpen, paymentMethod, onClose]);

    if (!isOpen) return null;

    const getSuccessTitle = () => {
        switch (paymentMethod) {
            case 'credit_card':
                return amount ? `₦${parseFloat(amount).toLocaleString()} Top up successfully` : 'Payment Successful!';
            case 'bank_transfer':
                return 'Transfer Confirmation Received!';
            case 'paypal':
                return 'PayPal Payment Successful!';
            default:
                return 'Success!';
        }
    };

    const getSuccessMessage = () => {
        switch (paymentMethod) {
            case 'credit_card':
                return 'Your wallet has been credited successfully. You can now book sessions with your teachers.';
            case 'bank_transfer':
                return 'Your wallet will be credited automatically within a few minutes once Paystack verifies your payment. You will receive a notification.';
            case 'paypal':
                return 'Your wallet has been credited successfully via PayPal.';
            default:
                return 'Your transaction was completed successfully.';
        }
    };

    return (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[70] p-4">
            <div className="bg-white rounded-3xl p-8 sm:p-12 max-w-md w-full shadow-2xl relative overflow-hidden">
                {/* Decorative Background Circles */}
                <div className="absolute top-0 left-0 w-32 h-32 bg-[#E0F2F1] rounded-full -translate-x-16 -translate-y-16 opacity-50"></div>
                <div className="absolute bottom-0 right-0 w-40 h-40 bg-[#E0F2F1] rounded-full translate-x-20 translate-y-20 opacity-50"></div>
                
                {/* Content */}
                <div className="relative z-10">
                    {/* Success Icon with Animation */}
                    <div className="flex justify-center mb-6">
                        <div className="relative">
                            {/* Animated Ring */}
                            <div className="absolute inset-0 bg-[#2C7870] rounded-full animate-ping opacity-20"></div>
                            
                            {/* Main Circle */}
                            <div className="relative w-24 h-24 bg-[#2C7870] rounded-full flex items-center justify-center shadow-lg">
                                <Check className="w-12 h-12 text-white stroke-[3]" />
                            </div>
                        </div>
                    </div>

                    {/* Success Title */}
                    <h2 className="text-2xl sm:text-3xl font-semibold text-[#1E293B] text-center mb-4">
                        {getSuccessTitle()}
                    </h2>

                    {/* Success Message */}
                    <p className="text-[#64748B] text-center text-base mb-6 leading-relaxed">
                        {getSuccessMessage()}
                    </p>

                    {/* Transaction Details */}
                    {(newBalance !== undefined || transactionId) && (
                        <div className="bg-[#F8FAFC] rounded-2xl p-4 mb-6 space-y-2">
                            {newBalance !== undefined && (
                                <div className="flex justify-between items-center">
                                    <span className="text-[#64748B] text-sm">New Balance:</span>
                                    <span className="text-[#1E293B] font-semibold text-lg">
                                        {PAYMENT_CONFIG.CURRENCY_SYMBOL}{newBalance.toLocaleString()}
                                    </span>
                                </div>
                            )}
                            {transactionId && (
                                <div className="flex justify-between items-center">
                                    <span className="text-[#64748B] text-sm">Transaction ID:</span>
                                    <span className="text-[#1E293B] font-mono text-xs">
                                        {transactionId && typeof transactionId === 'string' 
                                            ? `${transactionId.substring(0, 12)}...` 
                                            : transactionId || 'N/A'}
                                    </span>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Action Button */}
                    <Button
                        onClick={onClose}
                        className="w-full bg-[#2C7870] hover:bg-[#236158] text-white py-4 px-8 rounded-full font-semibold text-base shadow-lg hover:shadow-xl transition-all"
                    >
                        Got It, JazakaAllahu Khair!
                    </Button>

                    {/* Auto-close indicator for credit card */}
                    {paymentMethod === 'credit_card' && (
                        <p className="text-[#94A3B8] text-xs text-center mt-4">
                            This dialog will close automatically in 5 seconds
                        </p>
                    )}
                </div>
            </div>
        </div>
    );
}

