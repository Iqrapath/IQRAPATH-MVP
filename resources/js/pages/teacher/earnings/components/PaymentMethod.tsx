import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { ArrowLeftRight, Check, Plus, ChevronRight, X, CreditCard } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import AddWithdrawalModal from './AddWithdrawalModal';
import AddPayPalModal from './AddPayPalModal';
import { PaypalIcon } from '@/components/icons/paypal-icon';

interface PaymentMethod {
    id: number;
    user_id: number;
    type: 'bank_transfer' | 'mobile_money' | 'card' | 'paypal';
    name: string;
    
    // Gateway fields
    gateway: 'stripe' | 'paystack' | 'paypal' | null;
    gateway_token: string | null;
    gateway_customer_id: string | null;
    
    // Card fields
    last_four: string | null;
    card_brand: string | null;
    expiry_month: number | null;
    expiry_year: number | null;
    
    // Bank fields
    bank_name: string | null;
    bank_code: string | null;
    account_name: string | null;
    account_number: string | null;
    
    // Mobile money fields
    phone_number: string | null;
    provider: string | null;
    
    // Status fields
    currency: string;
    is_default: boolean;
    is_active: boolean;
    is_verified: boolean;
    verification_status: 'pending' | 'verified' | 'failed';
    verified_at: string | null;
    verification_notes: string | null;
    
    // Timestamps
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    
    // Legacy field (for backward compatibility)
    details: Record<string, any> | null;
}

export default function PaymentMethod() {
    const pageProps = usePage().props as any;
    const [paymentMethods, setPaymentMethods] = useState<PaymentMethod[]>([]);
    const [showAddModal, setShowAddModal] = useState(false);
    const [showAddNewOptions, setShowAddNewOptions] = useState(false);
    const [showPayPalModal, setShowPayPalModal] = useState(false);

    // Get payment methods from props
    useEffect(() => {
        if (pageProps.paymentMethods) {
            setPaymentMethods(pageProps.paymentMethods);
        }
    }, [pageProps.paymentMethods]);

    // Get the default payment method
    const defaultPaymentMethod = paymentMethods.find(method => method.is_default && method.is_active);

    const handleAddPaymentMethod = () => {
        setShowAddNewOptions(true);
    };

    const handleCloseAddNewOptions = () => {
        setShowAddNewOptions(false);
    };

    const handleSelectBankCard = () => {
        setShowAddNewOptions(false);
        setShowAddModal(true);
    };

    const handleSelectPayPal = () => {
        setShowAddNewOptions(false);
        setShowPayPalModal(true);
    };

    const handleClosePayPalModal = () => {
        setShowPayPalModal(false);
    };

    const handleConfirmPayPal = () => {
        console.log('PayPal confirmed');
        setShowPayPalModal(false);
    };

    const handleSaveChanges = () => {
        console.log('Save changes');
        setShowAddNewOptions(false);
    };

    const handleCloseModal = () => {
        setShowAddModal(false);
    };

    const handleAddBankTransfer = () => {
        console.log('Add bank transfer');
        setShowAddModal(false);
    };

    const handleAddMobileWallet = () => {
        console.log('Add mobile wallet');
        setShowAddModal(false);
    };

    const handleChangePaymentMethod = (_methodId: number) => {
        // TODO: Load specific method details for editing
        setShowAddModal(true);
    };

    return (
        <div className="bg-white rounded-2xl p-6 border border-gray-200 relative">
            

            {/* Header Description */}
            <p className="text-gray-700 text-sm mb-6">
                Easily manage your payments methods through our secure system.
            </p>

            {/* Selected Payment Method Card */}
            {defaultPaymentMethod && (
                <div className="bg-gray-100 rounded-2xl p-4 mb-4">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-3">
                            <ArrowLeftRight className="h-5 w-5 text-gray-900" />
                            <span className="font-bold text-gray-900">
                                {defaultPaymentMethod.type === 'bank_transfer' ? 'Bank Account (Direct Withdrawal)' :
                                 defaultPaymentMethod.type === 'mobile_money' ? 'Mobile Money' :
                                 defaultPaymentMethod.type === 'paypal' ? 'PayPal' : 'Card Payment'}
                            </span>
                            {/* Verification Status Badge */}
                            {defaultPaymentMethod.is_verified ? (
                                <span className="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full font-medium">
                                    Verified
                                </span>
                            ) : defaultPaymentMethod.verification_status === 'failed' ? (
                                <span className="text-xs bg-red-100 text-red-800 px-2 py-1 rounded-full font-medium">
                                    Failed
                                </span>
                            ) : (
                                <span className="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full font-medium">
                                    Pending
                                </span>
                            )}
                        </div>
                        <div className="w-4 h-4 bg-[#338078] rounded-full flex items-center justify-center">
                            <div className="w-2 h-2 bg-white rounded-full"></div>
                        </div>
                    </div>
                </div>
            )}

            {/* Bank Details Section */}
            {defaultPaymentMethod ? (
                <div className="mb-6">
                    <div className="flex items-center justify-between mb-4">
                        {/* Left side - Checkmark and title */}
                        <div className="flex items-center space-x-3">
                            <div className="w-4 h-4 bg-[#338078] rounded-full flex items-center justify-center">
                                <Check className="h-3 w-3 text-white" />
                            </div>
                            <h3 className="font-bold text-gray-900 text-sm">
                                1. {defaultPaymentMethod.type === 'bank_transfer' ? 'Bank Account' :
                                    defaultPaymentMethod.type === 'mobile_money' ? 'Mobile Money' : 'Card'}
                            </h3>
                        </div>

                        {/* Right side - Change button */}
                        <button 
                            onClick={() => handleChangePaymentMethod(defaultPaymentMethod.id)}
                            className="text-[#338078] text-sm font-medium hover:underline flex items-center gap-1"
                        >
                            Change
                            <ChevronRight className="h-4 w-4" />
                        </button>
                    </div>

                    {/* Payment Method Information */}
                    <div>
                        {defaultPaymentMethod.type === 'bank_transfer' && (
                            <>
                                <p className="text-gray-900 font-medium text-base">
                                    {defaultPaymentMethod.bank_name || 'Bank Name'}
                                </p>
                                <p className="text-gray-600 text-sm mt-1">
                                    {defaultPaymentMethod.account_name || 'Account Holder'} | 
                                    {defaultPaymentMethod.last_four 
                                        ? ` ...${defaultPaymentMethod.last_four}` 
                                        : ` ${defaultPaymentMethod.account_number || 'Account Number'}`}
                                </p>
                            </>
                        )}
                        
                        {defaultPaymentMethod.type === 'mobile_money' && (
                            <>
                                <p className="text-gray-900 font-medium text-base">
                                    {defaultPaymentMethod.provider || 'Provider'}
                                </p>
                                <p className="text-gray-600 text-sm mt-1">
                                    {defaultPaymentMethod.phone_number || 'Phone Number'}
                                </p>
                            </>
                        )}

                        {defaultPaymentMethod.type === 'card' && (
                            <>
                                <p className="text-gray-900 font-medium text-base">
                                    {defaultPaymentMethod.card_brand 
                                        ? `${defaultPaymentMethod.card_brand.toUpperCase()} ending in ${defaultPaymentMethod.last_four}`
                                        : defaultPaymentMethod.name}
                                </p>
                            </>
                        )}

                        {defaultPaymentMethod.type === 'paypal' && (
                            <p className="text-gray-900 font-medium text-base">
                                {defaultPaymentMethod.name}
                            </p>
                        )}
                    </div>
                </div>
            ) : (
                <div className="mb-6 text-center">
                    <p className="text-gray-500 text-sm">No payment method selected</p>
                </div>
            )}

            {/* Add New Payment Method */}
            <div 
                onClick={handleAddPaymentMethod}
                className="flex items-center space-x-2 text-[#338078] cursor-pointer hover:underline mb-6"
            >
                <Plus className="h-4 w-4" />
                <span className="text-sm font-medium">Add New Payment Method</span>
            </div>

            {/* Payment Method Options - Show when Add New is clicked */}
            {showAddNewOptions && (
                <div className="space-y-4 mb-6 relative">
                    {/* Close Button */}
                    <button
                        onClick={handleCloseAddNewOptions}
                        className="absolute -top-2 -right-2 z-10 text-gray-500 hover:text-gray-700 transition-colors bg-white rounded-full p-1 shadow-sm cursor-pointer"
                    >
                        <X className="h-5 w-5" />
                    </button>
                    {/* Bank Card Option */}
                    <div
                        onClick={handleSelectBankCard}
                        className="border border-gray-600 rounded-lg p-2 hover:border-gray-600 transition-colors cursor-pointer w-[50%]"
                    >
                        <div className="flex items-center space-x-4">
                            <div className="w-12 h-12 flex items-center justify-center">
                                <CreditCard className="h-6 w-6 text-gray-600" />
                            </div>
                            <div className="flex-1">
                                <h3 className="font-semibold text-gray-900 text-base">
                                    Bank Card (Direct Withdrawal)
                                </h3>
                            </div>
                        </div>
                    </div>

                    {/* PayPal Option */}
                    <div
                        onClick={handleSelectPayPal}
                        className="border border-gray-600 rounded-lg p-2 hover:border-gray-600 transition-colors cursor-pointer w-[50%]"
                    >
                        <div className="flex items-center space-x-4">
                            <div className="w-12 h-12 flex items-center justify-center">
                                <PaypalIcon className="h-6 w-6 text-[#0070ba]" />
                            </div>
                            <div className="flex-1">
                                <h3 className="font-semibold text-gray-900 text-base">
                                    PayPal
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Save Changes Button - Show when Add New is clicked */}
            {showAddNewOptions && (
                <div className="flex justify-end">
                    <Button
                        onClick={handleSaveChanges}
                        className="bg-[#338078] hover:bg-[#338078]/80 text-white px-6 py-2 rounded-lg"
                    >
                        Save Changes
                    </Button>
                </div>
            )}

            {/* Add Withdrawal Modal */}
            <AddWithdrawalModal
                isOpen={showAddModal}
                onClose={handleCloseModal}
                onAddBankTransfer={handleAddBankTransfer}
                onAddMobileWallet={handleAddMobileWallet}
            />

            {/* Add PayPal Modal */}
            <AddPayPalModal
                isOpen={showPayPalModal}
                onClose={handleClosePayPalModal}
                onConfirm={handleConfirmPayPal}
            />
        </div>
    );
}
