import React, { useState, useEffect } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Check, Plus, ChevronRight } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import AddWithdrawalModal from './AddWithdrawalModal';

interface PaymentMethod {
    id: number;
    type: 'bank_transfer' | 'mobile_money' | 'card' | 'paypal';
    name: string;
    bank_name?: string | null;
    account_name?: string | null;
    last_four?: string | null;
    provider?: string | null;
    phone_number?: string | null;
    metadata?: {
        paypal_email?: string;
    } | null;
    details: {
        bank_name?: string;
        account_holder?: string;
        account_number?: string;
        provider?: string;
        phone_number?: string;
    } | null;
    is_default: boolean;
    is_active: boolean;
    is_verified: boolean;
    verification_status: 'pending' | 'verified' | 'failed';
    created_at: string;
    updated_at: string;
}

export default function PaymentInfo() {
    const pageProps = usePage().props as any;
    const [paymentMethods, setPaymentMethods] = useState<PaymentMethod[]>([]);
    const [showAddModal, setShowAddModal] = useState(false);
    const [loading, setLoading] = useState(false);

    // Fetch payment methods from API
    const fetchPaymentMethods = async () => {
        setLoading(true);
        try {
            const response = await fetch('/teacher/payment-methods', {
                headers: {
                    'Accept': 'application/json',
                }
            });
            const data = await response.json();
            // The API returns the array directly, not wrapped in an object
            if (Array.isArray(data)) {
                setPaymentMethods(data);
            }
        } catch (error) {
            console.error('Error fetching payment methods:', error);
        } finally {
            setLoading(false);
        }
    };

    // Initial load from props, then fetch from API
    useEffect(() => {
        if (pageProps.paymentMethods) {
            setPaymentMethods(pageProps.paymentMethods);
        }
        // Also fetch fresh data
        fetchPaymentMethods();
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
                /* All Payment Methods - Numbered and Secure */
                <div className="space-y-4">
                    {paymentMethods.map((method, index) => (
                        <div
                            key={method.id}
                            className="bg-white rounded-[28px] p-6"
                        >
                            <div className="flex items-center justify-between">
                                {/* Left side - checkmark (if verified) and numbered title */}
                                <div className="flex items-center space-x-3">
                                    {method.is_verified ? (
                                        <div className="w-4 h-4 rounded-full bg-[#338078] flex items-center justify-center">
                                            <Check className="h-3 w-3 text-white" />
                                        </div>
                                    ) : (
                                        <div className="w-4 h-4" />
                                    )}
                                    <div className="flex items-center gap-2">
                                        <h3 className="font-bold text-gray-900 text-sm">
                                            {index + 1}. {method.type === 'bank_transfer' ? 'Bank Transfer' :
                                                method.type === 'mobile_money' ? 'Mobile Money' :
                                                method.type === 'paypal' ? 'PayPal' : 'Card'}
                                        </h3>
                                        {method.is_default && (
                                            <span className="text-xs bg-[#338078] text-white px-2 py-0.5 rounded-full font-medium">
                                                Default
                                            </span>
                                        )}
                                        {/* Verification Status Badge */}
                                        {!method.is_verified && (
                                            <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${method.verification_status === 'failed'
                                                    ? 'bg-red-100 text-red-800'
                                                    : 'bg-yellow-100 text-yellow-800'
                                                }`}>
                                                {method.verification_status === 'failed' ? 'Failed' : 'Pending'}
                                            </span>
                                        )}
                                    </div>
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
                                            {method.bank_name ||
                                                (method.details as any)?.bank_name ||
                                                'Bank Name'}
                                        </p>
                                        <p className="text-gray-600 text-sm">
                                            {method.account_name ||
                                                (method.details as any)?.account_holder ||
                                                'Account Holder'} | {' '}
                                            {method.last_four
                                                ? `...${method.last_four}`
                                                : (method.details as any)?.account_number
                                                    ? `...${(method.details as any).account_number.slice(-4)}`
                                                    : 'Account Number'}
                                        </p>
                                    </div>
                                )}

                                {method.type === 'mobile_money' && (
                                    <div className="space-y-1">
                                        <p className="text-gray-900 font-medium text-base">
                                            {method.provider ||
                                                (method.details as any)?.provider ||
                                                'Provider'}
                                        </p>
                                        <p className="text-gray-600 text-sm">
                                            {method.phone_number ||
                                                (method.details as any)?.phone_number ||
                                                'Phone Number'}
                                        </p>
                                    </div>
                                )}

                                {method.type === 'paypal' && (
                                    <div className="space-y-1">
                                        <p className="text-gray-900 font-medium text-base">
                                            PayPal
                                        </p>
                                        <p className="text-gray-600 text-sm">
                                            {method.metadata?.paypal_email 
                                                ? `${method.metadata.paypal_email.substring(0, 3)}***@${method.metadata.paypal_email.split('@')[1]}`
                                                : method.name}
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