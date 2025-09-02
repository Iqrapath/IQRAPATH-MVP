import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import AppModal from '@/components/common/AppModal';
import FundAccountModal from './FundAccountModal';

interface FundingConfig {
    min_amount: number;
    max_amount: number;
    currency: string;
    payment_method: string;
    bank_details: {
        name: string;
        account_holder: string;
        account_number: string;
    };
}

interface InsufficientFundsModalProps {
    isOpen: boolean;
    onClose: () => void;
    onFundAccount: () => void;
    onBalanceUpdated?: (newBalance: number) => void;
    onProceedToPayment?: () => void;
    currentBalance: number;
    requiredAmount: number;
    currency: 'USD' | 'NGN';
}

export default function InsufficientFundsModal({
    isOpen,
    onClose,
    onFundAccount,
    onBalanceUpdated,
    onProceedToPayment,
    currentBalance,
    requiredAmount,
    currency
}: InsufficientFundsModalProps) {
    const [showFundModal, setShowFundModal] = useState(false);
    const [fundingConfig, setFundingConfig] = useState<FundingConfig | null>(null);
    
    // Fetch funding configuration when component mounts
    useEffect(() => {
        const fetchFundingConfig = async () => {
            try {
                const response = await window.axios.get('/student/wallet/funding-config');
                setFundingConfig(response.data);
            } catch (error) {
                console.error('Failed to fetch funding config:', error);
            }
        };

        fetchFundingConfig();
    }, []);
    
    if (!isOpen) return null;

    const shortfall = requiredAmount - currentBalance;
    const currencySymbol = currency === 'USD' ? '$' : '₦';

    const handleFundAccountClick = () => {
        setShowFundModal(true);
    };

    const handleFundModalClose = () => {
        setShowFundModal(false);
    };

    const handleFund = (amount: number) => {
        // Handle the funding logic here
        console.log('Funding amount:', amount);
        setShowFundModal(false);
        
        // Update balance if callback provided
        if (onBalanceUpdated) {
            const newBalance = currency === 'USD' 
                ? (currentBalance + (amount / 1500)) // Convert NGN to USD
                : (currentBalance + amount); // NGN amount
            onBalanceUpdated(newBalance);
        }
        
        onClose(); // Close the insufficient funds modal
        onFundAccount(); // Call the original fund account handler
    };

    return (
        <div className="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-3xl p-8 max-w-sm w-full shadow-2xl">
                {/* Error Icon */}
                <div className="flex justify-center mb-6">
                    <div className="w-16 h-16 border-2 border-red-500 rounded-full flex items-center justify-center">
                        <span className="text-red-500 text-2xl font-bold leading-none">!</span>
                    </div>
                </div>

                {/* Header */}
                <div className="text-center mb-8">
                    <h2 className="text-xl font-semibold text-gray-900 mb-3">
                        Oops! Insufficient Funds
                    </h2>
                    <p className="text-gray-600 text-sm leading-relaxed">
                        You do not have enough funds in your account to pay for this class. Please 
                        fund your wallet and try again.
                    </p>
                </div>

                {/* Action Buttons */}
                <div className="flex gap-3">
                    <button
                        onClick={handleFundAccountClick}
                        className="flex-1 py-3 px-6 bg-teal-700 hover:bg-teal-800 text-white rounded-xl font-medium transition-colors"
                    >
                        Fund Account
                    </button>
                    <button
                        onClick={onClose}
                        className="flex-1 py-3 px-6 border border-gray-300 text-gray-600 hover:bg-gray-50 rounded-xl font-medium transition-colors"
                    >
                        Cancel
                    </button>
                </div>
            </div>

            {/* Fund Account Modal */}
            <FundAccountModal
                isOpen={showFundModal}
                onClose={handleFundModalClose}
                onFund={handleFund}
                onProceedToPayment={onProceedToPayment}
                minAmount={fundingConfig?.min_amount}
                maxAmount={fundingConfig?.max_amount}
                currency={fundingConfig?.currency}
            />
        </div>
    );
}
