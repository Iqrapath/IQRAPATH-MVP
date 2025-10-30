import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import { X, Loader2, AlertCircle, CheckCircle } from 'lucide-react';
import { toast } from 'sonner';
import axios from 'axios';

interface PaymentMethod {
    id: number;
    type: string;
    name: string;
    bank_name?: string;
    account_name?: string;
    last_four?: string;
    is_verified: boolean;
    verification_status: string;
    metadata?: {
        paypal_email?: string;
        provider?: string;
        mobile_number?: string;
    };
}

interface WithdrawFundsModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSuccess: () => void;
    availableBalance: number;
    preferredCurrency: string;
    paymentMethods: PaymentMethod[];
}

export default function WithdrawFundsModal({
    isOpen,
    onClose,
    onSuccess,
    availableBalance,
    preferredCurrency,
    paymentMethods
}: WithdrawFundsModalProps) {
    const [loading, setLoading] = useState(false);
    const [amount, setAmount] = useState('');
    const [selectedMethodId, setSelectedMethodId] = useState<number | null>(null);
    const [error, setError] = useState('');
    const [isEditingMethod, setIsEditingMethod] = useState(false);

    // Filter only verified payment methods
    const verifiedMethods = paymentMethods.filter(m => m.is_verified);

    // Auto-select first verified method if available
    useEffect(() => {
        if (isOpen && verifiedMethods.length > 0 && selectedMethodId === null) {
            setSelectedMethodId(verifiedMethods[0].id);
        }
    }, [isOpen, verifiedMethods.length, selectedMethodId]);

    if (!isOpen) return null;

    const selectedMethod = verifiedMethods.find(m => m.id === selectedMethodId);

    // Get currency symbol based on preferred currency
    const getCurrencySymbol = (currency: string): string => {
        switch (currency) {
            case 'NGN': return '₦';
            case 'USD': return '$';
            case 'EUR': return '€';
            case 'GBP': return '£';
            default: return '₦';
        }
    };

    const currencySymbol = getCurrencySymbol(preferredCurrency);
    const minWithdrawal = preferredCurrency === 'NGN' ? 5000 : 10;
    const minWithdrawalFormatted = `${currencySymbol}${minWithdrawal.toLocaleString()}`;

    const validateAmount = (value: string): boolean => {
        const numValue = parseFloat(value);

        if (!value || isNaN(numValue)) {
            setError('Please enter a valid amount');
            return false;
        }

        if (numValue < minWithdrawal) {
            setError(`Minimum withdrawal amount is ${minWithdrawalFormatted}`);
            return false;
        }

        if (numValue > availableBalance) {
            setError(`Amount cannot exceed available balance (${currencySymbol}${availableBalance.toLocaleString()})`);
            return false;
        }

        setError('');
        return true;
    };

    const handleAmountChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        setAmount(value);
        if (value) {
            validateAmount(value);
        } else {
            setError('');
        }
    };

    const handleWithdraw = async () => {
        if (!validateAmount(amount)) return;

        if (!selectedMethodId) {
            toast.error('Please select a payment method');
            return;
        }

        setLoading(true);
        try {
            const response = await axios.post('/teacher/earnings/request-payout', {
                amount: parseFloat(amount),
                payment_method_id: selectedMethodId,
                notes: ''
            }, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            if (response.data.success) {
                toast.success('Withdrawal request submitted!', {
                    description: 'Your request is being processed',
                    duration: 4000,
                });

                setAmount('');
                setSelectedMethodId(null);
                onSuccess();
                onClose();
            }
        } catch (error: any) {
            console.error('Error requesting withdrawal:', error);

            // Handle Laravel validation errors
            if (error.response?.status === 422 && error.response?.data?.errors) {
                const validationErrors = error.response.data.errors;
                const firstError = Object.values(validationErrors)[0];
                const errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
                setError(errorMessage as string);
                toast.error('Validation Error', {
                    description: errorMessage as string,
                    duration: 5000,
                });
            } else {
                const errorMessage = error.response?.data?.message || 'Failed to submit withdrawal request';
                toast.error('Withdrawal request failed', {
                    description: errorMessage,
                    duration: 5000,
                });
            }
        } finally {
            setLoading(false);
        }
    };

    const handleClose = () => {
        if (!loading) {
            setAmount('');
            setError('');
            setSelectedMethodId(null);
            onClose();
        }
    };

    return (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-3xl p-6 w-full max-w-2xl mx-4 relative">
                {/* Close Button */}
                <button
                    onClick={handleClose}
                    className="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors"
                    disabled={loading}
                >
                    <X className="h-6 w-6" />
                </button>

                {/* Header */}
                <div className="mb-6">
                    <h2 className="text-2xl font-bold text-gray-900 mb-1">
                        Withdraw your Earnings
                    </h2>
                    <p className="text-gray-500 text-sm">
                        Easily transfer your Earning balance to your bank account
                    </p>
                </div>

                {/* Check for verified payment methods */}
                {verifiedMethods.length === 0 ? (
                    <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                        <div className="flex items-start space-x-3">
                            <AlertCircle className="h-5 w-5 text-yellow-600 mt-0.5 flex-shrink-0" />
                            <div>
                                <p className="text-sm font-medium text-yellow-800">
                                    No verified payment methods
                                </p>
                                <p className="text-sm text-yellow-700 mt-1">
                                    Please add and verify a payment method before requesting a withdrawal.
                                </p>
                            </div>
                        </div>
                    </div>
                ) : (
                    <>
                        {/* Payment Method Selection */}
                        <div className="mb-5">
                            <div className="flex items-center justify-between mb-3">
                                <div className="flex items-center space-x-2">
                                    <CheckCircle className="h-5 w-5 text-[#338078]" />
                                    <span className="font-semibold text-gray-900">
                                        1. Payment Method
                                    </span>
                                </div>
                                <button
                                    onClick={() => setIsEditingMethod(!isEditingMethod)}
                                    className="text-[#338078] text-sm font-medium hover:underline"
                                    type="button"
                                >
                                    {isEditingMethod ? 'Done' : 'Edit'}
                                </button>
                            </div>

                            {!isEditingMethod && selectedMethod ? (
                                <div className="bg-gray-50 rounded-lg p-3">
                                    <p className="font-medium text-gray-900">
                                        {selectedMethod.bank_name || selectedMethod.name}
                                    </p>
                                    <p className="text-gray-600 text-sm mt-1">
                                        {selectedMethod.account_name}
                                        {selectedMethod.last_four && ` | ****${selectedMethod.last_four}`}
                                        {selectedMethod.metadata?.paypal_email && selectedMethod.metadata.paypal_email}
                                    </p>
                                </div>
                            ) : (
                                <ScrollArea className="h-[240px]">
                                    <div className="space-y-2 pr-4">
                                        {verifiedMethods.map((method) => (
                                            <button
                                                key={method.id}
                                                onClick={() => {
                                                    setSelectedMethodId(method.id);
                                                    setIsEditingMethod(false);
                                                }}
                                                className={`w-full text-left p-3 rounded-lg border-2 transition-all ${selectedMethodId === method.id
                                                    ? 'border-[#338078] bg-[#E8F5F3]'
                                                    : 'border-gray-200 bg-gray-50 hover:border-gray-300'
                                                    }`}
                                                type="button"
                                            >
                                                <div className="flex items-start justify-between">
                                                    <div className="flex-1">
                                                        <p className="font-medium text-gray-900">
                                                            {method.bank_name || method.name}
                                                        </p>
                                                        <p className="text-gray-600 text-sm mt-1">
                                                            {method.account_name}
                                                            {method.last_four && ` | ****${method.last_four}`}
                                                            {method.metadata?.paypal_email && method.metadata.paypal_email}
                                                        </p>
                                                        <div className="flex items-center space-x-2 mt-2">
                                                            <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                                Verified
                                                            </span>
                                                            <span className="text-xs text-gray-500 capitalize">
                                                                {method.type}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    {selectedMethodId === method.id && (
                                                        <CheckCircle className="h-5 w-5 text-[#338078] flex-shrink-0 ml-3" />
                                                    )}
                                                </div>
                                            </button>
                                        ))}
                                    </div>
                                </ScrollArea>
                            )}
                        </div>

                        {/* Withdrawal Amount */}
                        <div className="bg-[#E8F5F3] rounded-2xl p-5">
                            <p className="text-gray-700 mb-3 text-sm">
                                Please input the amount you wish to withdraw from your wallet.
                            </p>

                            <div className="flex items-center space-x-4">
                                <div className="flex-1 relative">
                                    <div className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-lg font-medium pointer-events-none z-10">
                                        {currencySymbol}
                                    </div>
                                    <Input
                                        type="number"
                                        placeholder="0.00"
                                        value={amount}
                                        onChange={handleAmountChange}
                                        className={`pl-12 pr-4 py-5 text-lg rounded-full border-2 ${error ? 'border-red-500' : 'border-gray-200'
                                            } focus:border-[#338078] focus:ring-0 placeholder:text-gray-400`}
                                        disabled={loading}
                                    />
                                </div>

                                <Button
                                    onClick={handleWithdraw}
                                    className="bg-[#338078] hover:bg-[#338078]/90 text-white px-8 py-5 rounded-full text-base font-medium"
                                    disabled={loading || !amount || !!error}
                                >
                                    {loading ? (
                                        <>
                                            <Loader2 className="h-5 w-5 mr-2 animate-spin" />
                                            Processing...
                                        </>
                                    ) : (
                                        'Withdraw Now'
                                    )}
                                </Button>
                            </div>

                            {/* Error Message */}
                            {error && (
                                <p className="text-red-500 text-sm mt-2 ml-4">{error}</p>
                            )}

                            {/* Note */}
                            <p className="text-gray-600 text-sm mt-3">
                                <span className="font-medium">Note:</span> You can only withdraw the minimum amount of {minWithdrawalFormatted}
                            </p>
                        </div>
                    </>
                )}
            </div>
        </div>
    );
}
