/**
 * 🎨 FIGMA REFERENCE
 * Fund Account Modal for wallet funding
 * 
 * EXACT SPECS FROM FIGMA:
 * - Withdraw/Fund your Earnings title
 * - Bank transfer selection with change option
 * - Amount input field with currency symbol
 * - Fund Now button
 * - Minimum amount note
 */

import React, { useState, useEffect } from 'react';
import { X, Plus } from 'lucide-react';
import { router } from '@inertiajs/react';
import AddPaymentMethodModal from './AddPaymentMethodModal';
import WalletFundingSuccessModal from './WalletFundingSuccessModal';

interface PaymentMethod {
    id: number;
    type: string;
    name: string;
    display_text: string;
    is_default: boolean;
    details: any;
}

interface FundAccountModalProps {
    isOpen: boolean;
    onClose: () => void;
    onFund: (amount: number) => void;
    onProceedToPayment?: () => void;
    minAmount?: number;
    maxAmount?: number;
    currency?: string;
}

export default function FundAccountModal({
    isOpen,
    onClose,
    onFund,
    onProceedToPayment,
    minAmount = 10,
    maxAmount = 1000000,
    currency = '₦'
}: FundAccountModalProps) {
    const [amount, setAmount] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [paymentMethods, setPaymentMethods] = useState<PaymentMethod[]>([]);
    const [selectedPaymentMethod, setSelectedPaymentMethod] = useState<PaymentMethod | null>(null);
    const [showAddPaymentMethod, setShowAddPaymentMethod] = useState(false);
    const [loadingPaymentMethods, setLoadingPaymentMethods] = useState(true);
    const [showSuccessModal, setShowSuccessModal] = useState(false);
    const [fundedAmount, setFundedAmount] = useState(0);
    
    // Fetch payment methods when modal opens
    useEffect(() => {
        if (isOpen) {
            fetchPaymentMethods();
        }
    }, [isOpen]);

    const fetchPaymentMethods = async () => {
        try {
            setLoadingPaymentMethods(true);
            const response = await window.axios.get('/student/payment-methods');
            const methods = response.data.payment_methods;
            setPaymentMethods(methods);
            
            // Auto-select default payment method or first one
            const defaultMethod = methods.find((method: PaymentMethod) => method.is_default);
            setSelectedPaymentMethod(defaultMethod || methods[0] || null);
        } catch (error) {
            console.error('Failed to fetch payment methods:', error);
        } finally {
            setLoadingPaymentMethods(false);
        }
    };
    
    if (!isOpen) return null;

    const handleAddPaymentMethodSuccess = (newPaymentMethod: PaymentMethod) => {
        setPaymentMethods(prev => [...prev, newPaymentMethod]);
        setSelectedPaymentMethod(newPaymentMethod);
        setShowAddPaymentMethod(false);
    };

    const handleSuccessModalClose = () => {
        setShowSuccessModal(false);
        onClose();
    };

    const handleProceedToPayment = () => {
        if (onProceedToPayment) {
            onProceedToPayment();
        }
        setShowSuccessModal(false);
        onClose();
    };

    const handleFund = async () => {
        const fundAmount = parseFloat(amount);
        if (fundAmount && fundAmount >= minAmount && fundAmount <= maxAmount && selectedPaymentMethod) {
            setIsLoading(true);
            
            try {
                // Make API call to fund wallet
                const response = await window.axios.post('/student/wallet/fund', {
                    amount: fundAmount,
                    payment_method_id: selectedPaymentMethod.id
                });

                if (response.data.success) {
                    // Success callback - show success modal
                    setFundedAmount(fundAmount);
                    setAmount('');
                    setShowSuccessModal(true);
                    onFund(fundAmount);
                } else {
                    throw new Error(response.data.message || 'Failed to fund wallet');
                }
            } catch (error: any) {
                console.error('Funding error:', error);
                
                // Handle validation errors
                if (error.response?.data?.errors) {
                    const errorMessages = Object.values(error.response.data.errors).flat().join('\n');
                    alert('Validation errors:\n' + errorMessages);
                } else {
                    alert(error.response?.data?.message || 'Failed to fund wallet. Please try again.');
                }
            } finally {
                setIsLoading(false);
            }
        }
    };

    const isValidAmount = amount && parseFloat(amount) >= minAmount && parseFloat(amount) <= maxAmount && selectedPaymentMethod;

    return (
        <div className="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl">
                {/* Header */}
                <div className="flex items-center justify-between mb-6">
                    <h2 className="text-xl font-semibold text-gray-900">
                        Withdraw your Earnings
                    </h2>
                    <button
                        onClick={onClose}
                        className="p-1 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        <X className="w-5 h-5 text-gray-500" />
                    </button>
                </div>

                {/* Subtitle */}
                <p className="text-gray-600 text-sm mb-6">
                    Easily transfer your Earning balance to your bank account
                </p>

                {/* Payment Method Section */}
                <div className="mb-6">
                    <div className="flex items-center justify-between mb-3">
                        <h3 className="text-sm font-medium text-gray-900">Payment Method</h3>
                        <button
                            onClick={() => setShowAddPaymentMethod(true)}
                            className="flex items-center gap-1 text-teal-600 text-sm font-medium hover:text-teal-700 transition-colors"
                        >
                            <Plus className="w-4 h-4" />
                            Add New
                        </button>
                    </div>

                    {loadingPaymentMethods ? (
                        <div className="p-4 bg-gray-50 rounded-lg">
                            <p className="text-sm text-gray-500">Loading payment methods...</p>
                        </div>
                    ) : paymentMethods.length === 0 ? (
                        <div className="p-4 bg-gray-50 rounded-lg text-center">
                            <p className="text-sm text-gray-500 mb-3">No payment methods found</p>
                            <button
                                onClick={() => setShowAddPaymentMethod(true)}
                                className="text-teal-600 text-sm font-medium hover:text-teal-700 transition-colors"
                            >
                                Add your first payment method
                            </button>
                        </div>
                    ) : (
                        <div className="space-y-2">
                            {paymentMethods.map((method) => (
                                <div
                                    key={method.id}
                                    onClick={() => setSelectedPaymentMethod(method)}
                                    className={`flex items-center justify-between p-4 rounded-lg cursor-pointer transition-colors ${
                                        selectedPaymentMethod?.id === method.id
                                            ? 'bg-teal-50 border-2 border-teal-600'
                                            : 'bg-gray-50 border-2 border-transparent hover:bg-gray-100'
                                    }`}
                                >
                                    <div className="flex items-center gap-3">
                                        <div className={`w-6 h-6 rounded-full flex items-center justify-center ${
                                            method.type === 'bank_transfer' ? 'bg-teal-600' : 'bg-orange-500'
                                        }`}>
                                            <div className="w-2 h-2 bg-white rounded-full"></div>
                                        </div>
                                        <div>
                                            <p className="font-medium text-gray-900">{method.name}</p>
                                            <p className="text-sm text-gray-600">{method.display_text}</p>
                                            {method.is_default && (
                                                <span className="text-xs text-teal-600 font-medium">Default</span>
                                            )}
                                        </div>
                                    </div>
                                    {selectedPaymentMethod?.id === method.id && (
                                        <div className="w-5 h-5 bg-teal-600 rounded-full flex items-center justify-center">
                                            <div className="w-2 h-2 bg-white rounded-full"></div>
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* Amount Input Section */}
                <div className="mb-6">
                    <div className="bg-teal-50 rounded-lg p-4">
                        <p className="text-teal-700 text-sm mb-4">
                            Please input the amount you wish to fund your wallet.
                        </p>
                        
                        <div className="relative">
                            <div className="absolute left-3 top-1/2 transform -translate-y-1/2 flex items-center gap-2">
                                <span className="text-gray-600">{currency}</span>
                                {/* <span className="text-gray-400 text-sm">Amount</span> */}
                            </div>
                            <input
                                type="number"
                                value={amount}
                                onChange={(e) => setAmount(e.target.value)}
                                placeholder="Amount"
                                className="w-full pl-20 pr-4 py-3 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                            <button
                                onClick={handleFund}
                                disabled={!isValidAmount || isLoading}
                                className="absolute right-2 top-1/2 transform -translate-y-1/2 px-4 py-2 bg-teal-600 hover:bg-teal-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-lg text-sm font-medium transition-colors"
                            >
                                {isLoading ? 'Processing...' : 'Fund Now'}
                            </button>
                        </div>
                        
                        <p className="text-xs text-gray-500 mt-2">
                            <span className="font-medium">Note:</span> You can only fund between <span className="font-medium">{currency}{minAmount.toLocaleString()}</span> and <span className="font-medium">{currency}{maxAmount.toLocaleString()}</span>
                        </p>
                    </div>
                </div>
            </div>

            {/* Add Payment Method Modal */}
            <AddPaymentMethodModal
                isOpen={showAddPaymentMethod}
                onClose={() => setShowAddPaymentMethod(false)}
                onSuccess={handleAddPaymentMethodSuccess}
            />

            {/* Wallet Funding Success Modal */}
            <WalletFundingSuccessModal
                isOpen={showSuccessModal}
                onClose={handleSuccessModalClose}
                onProceedToPayment={handleProceedToPayment}
                fundedAmount={fundedAmount}
                currency={currency}
            />
        </div>
    );
}
