import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { X, CreditCard } from 'lucide-react';
import AddBankTransferModal from './AddBankTransferModal';

interface AddWithdrawalModalProps {
    isOpen: boolean;
    onClose: () => void;
    onAddBankTransfer: () => void;
    onAddMobileWallet: () => void;
}

export default function AddWithdrawalModal({ 
    isOpen, 
    onClose, 
    onAddBankTransfer, 
    onAddMobileWallet 
}: AddWithdrawalModalProps) {
    const [showBankTransferModal, setShowBankTransferModal] = useState(false);

    if (!isOpen) return null;

    const handleAddBankTransfer = () => {
        setShowBankTransferModal(true);
    };

    const handleBankTransferSuccess = () => {
        setShowBankTransferModal(false);
        onAddBankTransfer();
    };

    const handleCloseBankTransfer = () => {
        setShowBankTransferModal(false);
    };

    return (
        <div className="fixed inset-0 bg-transparent backdrop-blur-sm flex items-center justify-center z-50">
            <div className="bg-white rounded-4xl p-6 w-full max-w-xl mx-4">
                {/* Header */}
                <div className="flex justify-between items-start mb-6">
                    <div>
                        <h2 className="text-xl font-bold text-gray-900 mb-1">
                            Add your withdrawal Method
                        </h2>
                        <p className="text-gray-600 text-sm">
                            Select how you'd like to pay online.
                        </p>
                    </div>
                    <button
                        onClick={onClose}
                        className="text-gray-500 hover:text-gray-700 transition-colors"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                {/* Payment Method Options */}
                <div className="space-y-4">
                    {/* Bank Account Option */}
                    <div className="border border-gray-200 rounded-[28px] p-4 hover:border-gray-300 transition-colors">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center space-x-4">
                                {/* Bank Icon - matches image */}
                                <div className="w-12 h-12 rounded-full flex items-center justify-center">
                                    <CreditCard className="h-6 w-6 text-gray-600" />
                                </div>
                                
                                <div className="flex-1">
                                    <h3 className="font-semibold text-gray-900 text-base mb-1">
                                        Bank Account (Direct Withdrawal)
                                    </h3>
                                    <p className="text-gray-600 text-sm">
                                        Withdraw your earnings directly to a linked bank account.
                                    </p>
                                </div>
                            </div>
                            
                            <Button
                                onClick={handleAddBankTransfer}
                                className="bg-[#338078] hover:bg-[#338078]/80 text-white px-4 py-2 text-sm rounded-full ml-4"
                            >
                                Add Bank Transfer
                            </Button>
                        </div>
                    </div>

                    {/* Mobile Wallets Option */}
                    <div className="border border-gray-200 rounded-[28px] p-4 hover:border-gray-300 transition-colors">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center space-x-4">
                                {/* Mobile Wallet Icon - Naira symbol */}
                                <div className="w-12 h-12 rounded-lg flex items-center justify-center">
                                    <span className="text-2xl font-bold text-gray-600">₦</span>
                                </div>
                                
                                <div className="flex-1">
                                    <h3 className="font-semibold text-gray-900 text-base mb-1">
                                        Mobile Wallets (PayPal, Payoneer)
                                    </h3>
                                    <p className="text-gray-600 text-sm">
                                        Withdraw your earnings via PayPal, Payoneer, or Flutterwave.
                                    </p>
                                </div>
                            </div>
                            
                            <Button
                                onClick={onAddMobileWallet}
                                className="bg-[#338078] hover:bg-[#338078]/80 text-white px-4 py-2 text-sm rounded-full ml-4"
                            >
                                Add Mobile Wallet
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Bank Transfer Modal */}
            <AddBankTransferModal
                isOpen={showBankTransferModal}
                onClose={handleCloseBankTransfer}
                onSuccess={handleBankTransferSuccess}
            />
        </div>
    );
}
