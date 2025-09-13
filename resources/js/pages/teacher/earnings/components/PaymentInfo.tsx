import React, { useState, useEffect } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Check, Plus, ChevronRight } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import AddWithdrawalModal from './AddWithdrawalModal';

interface PaymentMethod {
    id: number;
    type: 'bank_transfer' | 'mobile_money' | 'card';
    name: string;
    details: {
        bank_name?: string;
        account_holder?: string;
        account_number?: string;
        provider?: string;
        phone_number?: string;
    };
    is_default: boolean;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export default function PaymentInfo() {
    const pageProps = usePage().props as any;
    const [paymentMethods, setPaymentMethods] = useState<PaymentMethod[]>([]);
    const [showAddModal, setShowAddModal] = useState(false);

    // Get payment methods from props
    useEffect(() => {
        if (pageProps.paymentMethods) {
            setPaymentMethods(pageProps.paymentMethods);
        }
    }, [pageProps.paymentMethods]);

    // Check if user has any payment methods
    const hasPaymentMethods = paymentMethods.length > 0;

    const handleAddPaymentMethod = () => {
        setShowAddModal(true);
    };

    const handleCloseModal = () => {
        setShowAddModal(false);
    };

    const handleAddBankTransfer = () => {
        // Handle adding bank transfer
        console.log('Add bank transfer');
        setShowAddModal(false);
    };

    const handleAddMobileWallet = () => {
        // Handle adding mobile wallet
        console.log('Add mobile wallet');
        setShowAddModal(false);
    };

    const handleChangePaymentMethod = (methodId: number) => {
        // Open AddWithdrawalModal for changing payment method
        setShowAddModal(true);
    };

    return (
        <div className="space-y-6">
            {!hasPaymentMethods ? (
                /* Empty State - matches first image */
                <Card className="border-2 border-dashed border-gray-200">
                    <CardContent className="flex flex-col items-center justify-center py-16">
                        <div className="text-center">
                            <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <Plus className="h-8 w-8 text-gray-400" />
                            </div>
                            <h3 className="text-lg font-medium text-gray-900 mb-2">
                                No Withdraw Info added yet
                            </h3>
                            <p className="text-gray-500 mb-6 max-w-sm">
                                Add your bank account details to start receiving payments for your teaching sessions.
                            </p>
                            <Button 
                                onClick={handleAddPaymentMethod}
                                className="bg-[#338078] hover:bg-[#338078]/80 text-white px-6 py-2"
                            >
                                Add Withdrawal Info
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            ) : (
                /* Payment Methods List - matches second image exactly */
                <div className="space-y-4">
                    {paymentMethods.map((method) => (
                        <div 
                            key={method.id} 
                            className="bg-white rounded-[28px] p-6"
                        >
                            <div className="flex items-center justify-between">
                                {/* Left side - checkmark and title */}
                                <div className="flex items-center space-x-3">
                                    <div className="w-4 h-4 rounded-full bg-[#338078] flex items-center justify-center">
                                        <Check className="h-3 w-3 text-white" />
                                    </div>
                                    <h3 className="font-bold text-gray-900 text-sm">
                                        {method.type === 'bank_transfer' ? '1. Bank Transfer' : 
                                         method.type === 'mobile_money' ? '1. Mobile Money' : '1. Card'}
                                    </h3>
                                </div>
                                
                                {/* Right side - Change button */}
                                <button
                                    onClick={() => handleChangePaymentMethod(method.id)}
                                    className="text-[#338078] text-sm font-medium hover:underline flex items-center gap-1"
                                >
                                    Change
                                    <ChevronRight className="h-4 w-4" />
                                </button>
                            </div>
                            
                            {/* Bank details below */}
                            <div className="mt-2">
                                        
                                {method.type === 'bank_transfer' && (
                                    <div className="space-y-1">
                                        <p className="text-gray-900 font-medium text-base">
                                            {method.details.bank_name || 'Bank Name'}
                                        </p>
                                        <p className="text-gray-600 text-sm">
                                            {method.details.account_holder || 'Account Holder'} | {method.details.account_number || 'Account Number'}
                                        </p>
                                    </div>
                                )}
                                
                                {method.type === 'mobile_money' && (
                                    <div className="space-y-1">
                                        <p className="text-gray-900 font-medium text-base">
                                            {method.details.provider || 'Provider'}
                                        </p>
                                        <p className="text-gray-600 text-sm">
                                            {method.details.phone_number || 'Phone Number'}
                                        </p>
                                    </div>
                                )}
                                
                                {method.type === 'card' && (
                                    <p className="text-gray-900 font-medium text-base">
                                        {method.name}
                                    </p>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Add Withdrawal Modal */}
            <AddWithdrawalModal
                isOpen={showAddModal}
                onClose={handleCloseModal}
                onAddBankTransfer={handleAddBankTransfer}
                onAddMobileWallet={handleAddMobileWallet}
            />
        </div>
    );
}