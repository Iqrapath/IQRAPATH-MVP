import { useState } from 'react';
import { X, ArrowLeftRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { CreditCardIcon } from '@/components/icons/credit-card-icon';
import AddBankDetailsModal from './AddBankDetailsModal';
import AddCreditCardModal from './AddCreditCardModal';

interface AddPaymentMethodModalProps {
    isOpen: boolean;
    onClose: () => void;
}

export default function AddPaymentMethodModal({ isOpen, onClose }: AddPaymentMethodModalProps) {
    const [showBankDetails, setShowBankDetails] = useState(false);
    const [showCreditCard, setShowCreditCard] = useState(false);

    if (!isOpen) return null;

    const handleAddBankCard = () => {
        setShowCreditCard(true);
    };

    const handleAddBankAccount = () => {
        setShowBankDetails(true);
    };

    const handleAddMobileWallet = () => {
        // Handle mobile wallet addition
        console.log('Add mobile wallet');
    };

    const handleCloseBankDetails = () => {
        setShowBankDetails(false);
    };

    const handleBackFromBankDetails = () => {
        setShowBankDetails(false);
    };

    return (
        <div className="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 p-4 sm:p-6">
            <div className="bg-white rounded-2xl w-full max-w-2xl mx-4 p-8">
                {/* Header */}
                <div className="flex items-start justify-between mb-6">
                    <div>
                        <h2 className="text-3xl font-bold text-gray-900 mb-2">
                            Add your Payment Method
                        </h2>
                        <p className="text-gray-500">
                            Select how you'd like to pay online.
                        </p>
                    </div>
                    <button
                        onClick={onClose}
                        className="text-gray-400 hover:text-gray-600 transition-colors"
                    >
                        <X className="w-6 h-6" />
                    </button>
                </div>

                {/* Payment Methods */}
                <div className="space-y-4">
                    {/* Bank Account - Opens AddBankDetailsModal */}
                    <div className="flex items-center justify-between p-6 border-2 border-gray-200 rounded-2xl hover:border-gray-300 transition-colors">
                        <div className="flex items-center gap-4">
                            <div className="w-12 h-12 flex items-center justify-center">
                                <ArrowLeftRight className="h-8 w-8 text-gray-900" />
                            </div>
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900">
                                    Bank Account (Direct Withdrawal)
                                </h3>
                                <p className="text-sm text-gray-500">
                                    Add your bank account for direct withdrawals
                                </p>
                            </div>
                        </div>
                        <Button
                            onClick={handleAddBankAccount}
                            className="bg-[#2C7870] hover:bg-[#236158] text-white px-6 py-2 rounded-full"
                        >
                            Add Bank Account
                        </Button>
                    </div>

                    {/* Bank Transfer - Opens AddCreditCardModal */}
                    <div className="flex items-center justify-between p-6 border-2 border-gray-200 rounded-2xl hover:border-gray-300 transition-colors">
                        <div className="flex items-center gap-4">
                            <div className="w-12 h-12 flex items-center justify-center">
                                <CreditCardIcon className="h-8 w-8" />
                            </div>
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900">
                                    Bank Transfer (Direct Withdrawal)
                                </h3>
                                <p className="text-sm text-gray-500">
                                    Add your card for quick payments and transfers
                                </p>
                            </div>
                        </div>
                        <Button
                            onClick={handleAddBankCard}
                            className="bg-[#2C7870] hover:bg-[#236158] text-white px-6 py-2 rounded-full"
                        >
                            Add Bank Card
                        </Button>
                    </div>

                    {/* Mobile Wallets */}
                    <div className="flex items-center justify-between p-6 border-2 border-gray-200 rounded-2xl hover:border-gray-300 transition-colors">
                        <div className="flex items-center gap-4">
                            <div className="w-12 h-12 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24">
                                    <path fill="currentColor" d="M4 9h2V3h2l3.42 6H16V3h2v6h2v2h-2v2h2v2h-2v6h-2l-3.43-6H8v6H6v-6H4v-2h2v-2H4zm4 0h1.13L8 7.03zm0 2v2h3.42l-1.14-2zm8 6v-2h-1.15zm-3.44-6l1.15 2H16v-2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900">
                                    Mobile Wallets (PayPal, Payoneer)
                                </h3>
                                <p className="text-sm text-gray-500">
                                    fund your earnings via PayPal, Payoneer, or Flutterwave.
                                </p>
                            </div>
                        </div>
                        <Button
                            onClick={handleAddMobileWallet}
                            className="bg-[#2C7870] hover:bg-[#236158] text-white px-6 py-2 rounded-full"
                        >
                            Add Mobile Wallet
                        </Button>
                    </div>
                </div>
            </div>

            <AddBankDetailsModal
                isOpen={showBankDetails}
                onClose={handleCloseBankDetails}
                onBack={handleBackFromBankDetails}
            />

            <AddCreditCardModal
                isOpen={showCreditCard}
                onClose={() => {
                    setShowCreditCard(false);
                    onClose();
                }}
                onBack={() => setShowCreditCard(false)}
            />
        </div>
    );
}

