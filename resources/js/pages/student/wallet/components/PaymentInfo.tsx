import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Check, ChevronRight } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import AddPaymentMethodModal from '@/components/student/AddPaymentMethodModal';
import EditBankDetailsForm from '@/components/student/EditBankDetailsForm';
import EditCreditCardForm from '@/components/student/EditCreditCardForm';
import { VisaCardIcon } from '@/components/icons/visa-card-icon';
import { MasterCardIcon } from '@/components/icons/master-card-icon';
import { AmericanExpressIconProps } from '@/components/icons/american-express-icon';
import { DiscoverIconProps } from '@/components/icons/discover-icon';
import { CreditCardIcon } from '@/components/icons/credit-card-icon';

interface PaymentMethod {
    id: number;
    type: 'bank_transfer' | 'mobile_money' | 'card' | 'paypal';
    name: string;
    // New secure fields
    bank_code?: string;
    bank_name?: string;
    account_name?: string;
    last_four?: string;
    // Card-specific fields
    card_brand?: string;
    card_number_prefix?: string;
    card_number_middle?: string;
    exp_month?: number;
    exp_year?: number;
    // Legacy details field (for backward compatibility)
    details?: {
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

// Card brand icon helper - Using custom icons
const getCardBrandIcon = (brand: string) => {
    switch (brand?.toLowerCase()) {
        case 'visa':
            return <VisaCardIcon className="w-10 h-7" />;
        case 'mastercard':
            return <MasterCardIcon className="w-10 h-7" />;
        case 'amex':
        case 'american express':
            return <AmericanExpressIconProps className="w-10 h-7" />;
        case 'discover':
            return <DiscoverIconProps className="w-10 h-7" />;
        default:
            return <CreditCardIcon className="w-10 h-7" />;
    }
};

// Get card brand display name
const getCardBrandName = (brand: string) => {
    switch (brand?.toLowerCase()) {
        case 'visa':
            return 'Visa';
        case 'mastercard':
            return 'Mastercard';
        case 'amex':
        case 'american express':
            return 'American Express';
        case 'discover':
            return 'Discover';
        case 'verve':
            return 'Verve';
        default:
            return 'Card';
    }
};

export default function PaymentInfo() {
    const pageProps = usePage().props as any;
    const [paymentMethods, setPaymentMethods] = useState<PaymentMethod[]>([]);
    const [loading, setLoading] = useState(false);
    const [showAddModal, setShowAddModal] = useState(false);
    const [showEditForm, setShowEditForm] = useState(false);
    const [editingMethod, setEditingMethod] = useState<PaymentMethod | null>(null);

    // Fetch payment methods from API
    const fetchPaymentMethods = async () => {
        setLoading(true);
        try {
            const response = await fetch('/student/wallet/payment-methods', {
                headers: {
                    'Accept': 'application/json',
                },
                signal: AbortSignal.timeout(10000), // 10 second timeout
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Payment Methods API Response:', data);
            if (data.payment_methods && Array.isArray(data.payment_methods)) {
                console.log('Payment Methods:', data.payment_methods);
                setPaymentMethods(data.payment_methods);
                return data.payment_methods;
            }
        } catch (error) {
            console.error('Error fetching payment methods:', error);
            // Silently fail - use props data if available
            // This handles network errors gracefully
        } finally {
            setLoading(false);
        }
        return [];
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

    const getPaymentMethodIcon = (type: string) => {
        switch (type) {
            case 'bank_transfer':
                return '🏦';
            case 'mobile_money':
                return '📱';
            case 'card':
                return '💳';
            case 'paypal':
                return '💰';
            default:
                return '💳';
        }
    };

    const getPaymentMethodDisplay = (method: PaymentMethod) => {
        if (method.type === 'bank_transfer' && method.details) {
            return `${method.details.bank_name} - ${method.details.account_number?.slice(-4)}`;
        }
        if (method.type === 'mobile_money' && method.details) {
            return `${method.details.provider} - ${method.details.phone_number}`;
        }
        return method.name;
    };

    // If editing, show the appropriate edit form based on payment method type
    if (showEditForm && editingMethod) {
        const isCard = editingMethod.type === 'card';

        if (isCard) {
            return (
                <EditCreditCardForm
                    key={`${editingMethod.id}-${editingMethod.updated_at}`}
                    paymentMethod={editingMethod}
                    onCancel={() => {
                        setShowEditForm(false);
                        setEditingMethod(null);
                    }}
                    onSuccess={async () => {
                        // Refresh payment methods to get updated data
                        const freshMethods = await fetchPaymentMethods();
                        // Update the editing method with fresh data
                        const updatedMethod = freshMethods.find((m: PaymentMethod) => m.id === editingMethod.id);
                        if (updatedMethod) {
                            setEditingMethod(updatedMethod);
                        }
                    }}
                    onAddNew={() => {
                        setShowEditForm(false);
                        setEditingMethod(null);
                        setShowAddModal(true);
                    }}
                />
            );
        } else {
            return (
                <EditBankDetailsForm
                    paymentMethod={editingMethod}
                    onCancel={() => {
                        setShowEditForm(false);
                        setEditingMethod(null);
                    }}
                    onSuccess={async () => {
                        // Refresh payment methods to get updated data
                        const freshMethods = await fetchPaymentMethods();
                        // Update the editing method with fresh data
                        const updatedMethod = freshMethods.find((m: PaymentMethod) => m.id === editingMethod.id);
                        if (updatedMethod) {
                            setEditingMethod(updatedMethod);
                        }
                    }}
                    onAddNew={() => {
                        setShowEditForm(false);
                        setEditingMethod(null);
                        setShowAddModal(true);
                    }}
                />
            );
        }
    }

    return (
        <div>
            {loading ? (
                <div className="text-center py-8 text-gray-500">
                    Loading payment methods...
                </div>
            ) : !hasPaymentMethods ? (
                <div className="text-center py-16">
                    <p className="text-gray-400 text-lg mb-6">
                        No Withdraw Info added yet
                    </p>
                    <Button
                        onClick={() => setShowAddModal(true)}
                        className="bg-[#2C7870] hover:bg-[#236158] text-white px-6 py-2 rounded-lg"
                    >
                        Add Payment Info
                    </Button>
                </div>
            ) : (
                <div className="space-y-3">
                    {paymentMethods.map((method, index) => {
                        const isCard = method.type === 'card';
                        const isBankTransfer = method.type === 'bank_transfer';

                        return (
                            <div
                                key={method.id}
                                className="flex items-center justify-between p-4 bg-white rounded-lg border border-gray-200"
                            >
                                <div className="flex items-center gap-3">
                                    {/* Check icon */}
                                    <div className="w-8 h-8 bg-[#10B981] rounded-full flex items-center justify-center flex-shrink-0">
                                        <Check className="w-5 h-5 text-white" />
                                    </div>

                                    <div className="flex-1">
                                        {/* Title with number */}
                                        <h3 className="text-base font-semibold text-gray-900 mb-1">
                                            {index + 1}. {isCard ? 'Bank Card' : 'Bank Transfer'}
                                        </h3>

                                        {/* Display based on type */}
                                        {isCard ? (
                                            <div className="flex items-center gap-3">
                                                {/* Card Brand Icon */}
                                                <div className="flex-shrink-0">
                                                    {getCardBrandIcon(method.card_brand || '')}
                                                </div>

                                                <div className="flex-1">
                                                    {/* Card Brand Name */}
                                                    <p className="text-gray-900 font-medium">
                                                        {getCardBrandName(method.card_brand || '')}
                                                    </p>
                                                    {/* Card Holder and Last 4 */}
                                                    <p className="text-gray-500 text-sm">
                                                        {method.account_name || pageProps.auth?.user?.name || 'Card Holder'} | **** **** **** {method.last_four || '****'}
                                                    </p>
                                                </div>
                                            </div>
                                        ) : isBankTransfer ? (
                                            <>
                                                {/* Bank name */}
                                                <p className="text-gray-700 font-medium">
                                                    {method.bank_name || method.details?.bank_name || 'Bank'}
                                                </p>
                                                {/* Account holder and number */}
                                                <p className="text-gray-500 text-sm">
                                                    {method.account_name || method.details?.account_holder || 'Account Holder'} |
                                                    {method.last_four ? ` ****${method.last_four}` : (method.details?.account_number ? ` ****${method.details.account_number.slice(-4)}` : '')}
                                                </p>
                                            </>
                                        ) : (
                                            <p className="text-gray-700 font-medium">{method.name}</p>
                                        )}
                                    </div>
                                </div>

                                {/* Change button - for both cards and bank transfers */}
                                {(isCard || isBankTransfer) && (
                                    <button
                                        onClick={() => {
                                            setEditingMethod(method);
                                            setShowEditForm(true);
                                        }}
                                        className="text-[#2C7870] hover:text-[#236158] font-medium flex items-center gap-1 transition-colors"
                                    >
                                        Change
                                        <ChevronRight className="w-4 h-4" />
                                    </button>
                                )}
                            </div>
                        );
                    })}
                </div>
            )}

            {hasPaymentMethods && (
                <div className="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <p className="text-sm text-blue-900">
                        <strong>Note:</strong> Your payment methods are securely stored and can be used for quick wallet top-ups and automatic payments.
                    </p>
                </div>
            )}

            <AddPaymentMethodModal
                isOpen={showAddModal}
                onClose={() => {
                    setShowAddModal(false);
                    fetchPaymentMethods(); // Refresh list after adding
                }}
            />
        </div>
    );
}
