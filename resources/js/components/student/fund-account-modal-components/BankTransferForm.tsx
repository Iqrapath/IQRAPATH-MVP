/**
 * 🎨 FIGMA DESIGN REFERENCE
 * 
 * Component: BankTransferForm
 * Design: Bank transfer payment form with account details and countdown timer
 * 
 * 📏 EXACT SPECIFICATIONS:
 * - Background: White card with rounded corners
 * - Padding: 40px all sides
 * - Border Radius: 32px
 * - Timer: Orange countdown (07:59:99 format)
 * - Account Details: Account Number, Bank Name, Beneficiary
 * - Copy Icon: Teal color next to account number
 * - Buttons: Teal filled "I have made this bank transfer", White outlined "Cancel"
 * - Typography: Inter font family
 * - Colors: #2C7870 (primary teal), #64748B (text gray), #F59E0B (orange timer)
 * 
 * 📱 RESPONSIVE: Desktop-first design
 * 🎯 STATES: Default, hover, focus, disabled, loading
 */

import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Copy, Check } from 'lucide-react';
import { toast } from 'sonner';

interface BankTransferFormProps {
    accountNumber: string;
    bankName: string;
    beneficiaryName: string;
    amount: string;
    isLoading: boolean;
    onConfirm: () => void;
    onCancel: () => void;
    onUseCreditCard?: () => void;
    isFeatureUnavailable?: boolean;
}

export default function BankTransferForm({
    accountNumber,
    bankName,
    beneficiaryName,
    amount,
    isLoading,
    onConfirm,
    onCancel,
    onUseCreditCard,
    isFeatureUnavailable = false
}: BankTransferFormProps) {
    const [timeLeft, setTimeLeft] = useState(480); // 8 minutes in seconds
    const [copied, setCopied] = useState(false);

    // Countdown timer
    useEffect(() => {
        if (timeLeft <= 0) return;

        const timer = setInterval(() => {
            setTimeLeft((prev) => {
                if (prev <= 1) {
                    clearInterval(timer);
                    return 0;
                }
                return prev - 1;
            });
        }, 1000);

        return () => clearInterval(timer);
    }, [timeLeft]);

    // Format time as MM:SS
    const formatTime = (seconds: number): string => {
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    };

    // Copy account number to clipboard
    const handleCopy = async () => {
        try {
            await navigator.clipboard.writeText(accountNumber);
            setCopied(true);
            toast.success('Account number copied to clipboard');
            setTimeout(() => setCopied(false), 2000);
        } catch (error) {
            toast.error('Failed to copy account number');
        }
    };

    // Show feature unavailable message if needed
    if (isFeatureUnavailable || !accountNumber) {
        return (
            <>
                {/* Header */}
                <h3 className="text-[#1E293B] text-2xl font-normal mb-6">
                    Bank Transfer Payment
                </h3>

                {/* Info Card */}
                <div className="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-[32px] p-10 shadow-[0_4px_24px_rgba(0,0,0,0.08)] border border-blue-100">
                    {/* Icon */}
                    <div className="flex justify-center mb-6">
                        <div className="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg className="w-10 h-10 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>

                    {/* Title */}
                    <h4 className="text-2xl font-semibold text-[#1E293B] text-center mb-4">
                        Bank Transfer Coming Soon!
                    </h4>

                    {/* Description */}
                    <p className="text-[#64748B] text-center text-lg mb-8 leading-relaxed">
                        We're setting up automatic bank transfer payments with unique account numbers for each user. 
                        This feature will be available very soon!
                    </p>

                    {/* Features List */}
                    <div className="bg-white rounded-2xl p-6 mb-8">
                        <h5 className="text-lg font-semibold text-[#1E293B] mb-4">What to expect:</h5>
                        <ul className="space-y-3">
                            <li className="flex items-start gap-3">
                                <svg className="w-6 h-6 text-green-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                </svg>
                                <span className="text-[#64748B]">Your own unique bank account number</span>
                            </li>
                            <li className="flex items-start gap-3">
                                <svg className="w-6 h-6 text-green-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                </svg>
                                <span className="text-[#64748B]">Instant wallet crediting after transfer</span>
                            </li>
                            <li className="flex items-start gap-3">
                                <svg className="w-6 h-6 text-green-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                </svg>
                                <span className="text-[#64748B]">No manual verification needed</span>
                            </li>
                            <li className="flex items-start gap-3">
                                <svg className="w-6 h-6 text-green-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                </svg>
                                <span className="text-[#64748B]">Transfer from any Nigerian bank</span>
                            </li>
                        </ul>
                    </div>

                    {/* Current Option */}
                    <div className="bg-gradient-to-r from-[#2C7870] to-[#236158] rounded-2xl p-6 text-white mb-8">
                        <h5 className="text-lg font-semibold mb-2">For now, please use:</h5>
                        <p className="text-white/90 text-base">
                            💳 Credit/Debit Card payment - Fast, secure, and instant wallet crediting
                        </p>
                    </div>

                    {/* Action Buttons */}
                    <div className="flex items-center gap-4">
                        <Button
                            onClick={onUseCreditCard || onCancel}
                            className="flex-1 bg-[#2C7870] hover:bg-[#236158] text-white py-4 px-8 rounded-full font-semibold transition-all text-base shadow-sm hover:shadow-md"
                            aria-label="Use card payment"
                        >
                            Use Card Payment
                        </Button>
                        <Button
                            onClick={onCancel}
                            className="flex-1 bg-white border-2 border-[#2C7870] text-[#2C7870] hover:bg-[#F0F9FF] py-4 px-8 rounded-full font-semibold transition-all text-base"
                            aria-label="Cancel"
                        >
                            Cancel
                        </Button>
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            {/* Header with Timer */}
            <div className="mb-6">
                <h3 className="text-[#1E293B] text-xl font-normal mb-2">
                    Please complete your payment in your banking app within{' '}
                    <span className="text-[#F59E0B] font-semibold">{formatTime(timeLeft)}</span>
                </h3>
            </div>

            {/* White Card Container */}
            <div className="bg-white rounded-[32px] p-10 shadow-[0_4px_24px_rgba(0,0,0,0.08)]">
                {/* Account Number */}
                <div className="mb-6">
                    <label className="block text-[#64748B] text-sm font-normal mb-2">
                        Account Number:
                    </label>
                    <div className="flex items-center gap-3">
                        <span className="text-[#1E293B] text-2xl font-semibold">
                            {accountNumber}
                        </span>
                        <button
                            onClick={handleCopy}
                            className="p-2 hover:bg-[#F0F9FF] rounded-lg transition-colors"
                            aria-label="Copy account number"
                        >
                            {copied ? (
                                <Check className="w-5 h-5 text-[#2C7870]" />
                            ) : (
                                <Copy className="w-5 h-5 text-[#2C7870]" />
                            )}
                        </button>
                    </div>
                </div>

                {/* Bank Name */}
                <div className="mb-6">
                    <label className="block text-[#64748B] text-sm font-normal mb-2">
                        Bank Name:
                    </label>
                    <span className="text-[#1E293B] text-xl font-semibold">
                        {bankName}
                    </span>
                </div>

                {/* Beneficiary */}
                <div className="mb-8">
                    <label className="block text-[#64748B] text-sm font-normal mb-2">
                        Beneficiary:
                    </label>
                    <span className="text-[#1E293B] text-xl font-semibold">
                        {beneficiaryName}
                    </span>
                </div>

                {/* Divider */}
                <div className="border-t border-[#E2E8F0] mb-8"></div>

                {/* Info Text */}
                <p className="text-[#64748B] text-base mb-8 leading-relaxed">
                    If you haven't paid with Bank Transfer, you can change your payment method to or another payment method to receive your items faster.
                </p>

                {/* Action Buttons */}
                <div className="flex items-center gap-4">
                    <Button
                        onClick={onConfirm}
                        disabled={isLoading || timeLeft === 0}
                        className="bg-[#2C7870] hover:bg-[#236158] disabled:bg-[#CBD5E1] disabled:cursor-not-allowed text-white py-4 px-8 rounded-full font-semibold transition-all text-base shadow-sm hover:shadow-md disabled:shadow-none"
                        aria-label="Confirm bank transfer"
                    >
                        {isLoading ? 'Processing...' : 'I have made this bank transfer'}
                    </Button>
                    <Button
                        onClick={onCancel}
                        disabled={isLoading}
                        className="bg-white border-2 border-[#2C7870] text-[#2C7870] hover:bg-[#F0F9FF] py-4 px-10 rounded-full font-semibold transition-all text-base disabled:opacity-50 disabled:cursor-not-allowed"
                        aria-label="Cancel"
                    >
                        Cancel
                    </Button>
                </div>
            </div>
        </>
    );
}
