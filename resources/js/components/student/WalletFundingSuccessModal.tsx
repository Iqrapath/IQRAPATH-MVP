/**
 * 🎨 FIGMA REFERENCE
 * Wallet Funding Success Modal
 * 
 * EXACT SPECS FROM FIGMA:
 * - Green checkmark icon with background
 * - Success message with amount
 * - Proceed to class payment button
 * - Cancel button
 */

import React from 'react';
import { Check } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface WalletFundingSuccessModalProps {
    isOpen: boolean;
    onClose: () => void;
    onProceedToPayment: () => void;
    fundedAmount: number;
    currency: string;
}

export default function WalletFundingSuccessModal({
    isOpen,
    onClose,
    onProceedToPayment,
    fundedAmount,
    currency = '₦'
}: WalletFundingSuccessModalProps) {
    if (!isOpen) return null;

    const handleProceedToPayment = () => {
        onProceedToPayment();
        onClose();
    };

    const handleCancel = () => {
        onClose();
    };

    const formatAmount = (amount: number): string => {
        return amount.toLocaleString();
    };

    return (
        <div className="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl text-center">
                {/* Success Icon */}
                <div className="mb-6 flex justify-center">
                    <div className="relative">
                        {/* Background circles */}
                        <div className="w-24 h-24 bg-teal-100 rounded-full absolute -top-2 -left-2"></div>
                        <div className="w-20 h-20 bg-teal-600 rounded-full flex items-center justify-center relative z-10">
                            <Check className="w-10 h-10 text-white stroke-[3]" />
                        </div>
                    </div>
                </div>

                {/* Success Message */}
                <h2 className="text-xl font-semibold text-gray-900 mb-3 leading-tight">
                    You have successfully Fund {currency}{formatAmount(fundedAmount)} to your wallet
                </h2>

                {/* Subtitle */}
                <p className="text-gray-600 text-sm mb-8">
                    Kindly proceed to make your class payment
                </p>

                {/* Action Buttons */}
                <div className="space-y-3">
                    <Button
                        onClick={handleProceedToPayment}
                        className="w-full bg-teal-600 hover:bg-teal-700 text-white py-3 rounded-full font-medium transition-colors"
                    >
                        Proceed Your Class Payment
                    </Button>
                    
                    <Button
                        onClick={handleCancel}
                        variant="outline"
                        className="w-full border-gray-300 text-gray-700 hover:bg-gray-50 py-3 rounded-full font-medium transition-colors"
                    >
                        Cancel
                    </Button>
                </div>
            </div>
        </div>
    );
}
