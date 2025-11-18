import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { ArrowLeftRight, Plus, X, CreditCard, Check } from 'lucide-react';
import { usePage, router } from '@inertiajs/react';
import { toast } from 'sonner';
import AddPaymentMethodModal from '@/components/guardian/AddPaymentMethodModal';
import AddCreditCardModal from '@/components/guardian/AddCreditCardModal';
import AddPayPalModal from '@/pages/teacher/earnings/components/AddPayPalModal';
import ConfirmationModal from '@/components/ui/confirmation-modal';
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
    exp_month: number | null;
    exp_year: number | null;
    stripe_payment_method_id: string | null;

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

interface PaymentMethodProps {
    onPaymentMethodsUpdated?: () => void;
}

// Helper function to get card brand icon - Using colored logos from CDN
const getCardBrandIcon = (brand: string) => {
    const brandLower = brand?.toLowerCase() || 'unknown';

    const brandLogos: Record<string, string> = {
        'visa': 'https://brandeps.com/logo/V/Visa-01',
        'mastercard': 'https://brandeps.com/icon/M/Mastercard-04',
        'amex': 'https://brandeps.com/logo/A/American-Express-02',
        'discover': 'https://brandeps.com/logo/D/Discover-Card-01',
        'diners': 'https://brandeps.com/logo/D/Diners-Club-01',
        'jcb': 'https://brandeps.com/logo/J/JCB-02',
        'unionpay': 'https://brandeps.com/icon/U/Unionpay-01',
    };

    const logoUrl = brandLogos[brandLower];

    return (
        <img
            src={logoUrl || 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="%23666"%3E%3Crect x="2" y="5" width="20" height="14" rx="2" stroke-width="2"/%3E%3Cline x1="2" y1="10" x2="22" y2="10" stroke-width="2"/%3E%3C/svg%3E'}
            alt={brand}
            className="w-10 h-7 object-contain"
            onError={(e) => {
                e.currentTarget.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="%23666"%3E%3Crect x="2" y="5" width="20" height="14" rx="2" stroke-width="2"/%3E%3Cline x1="2" y1="10" x2="22" y2="10" stroke-width="2"/%3E%3C/svg%3E';
            }}
        />
    );
};

// Helper function to get card brand display name
const getCardBrandName = (brand: string): string => {
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
        case 'diners':
            return 'Diners Club';
        case 'jcb':
            return 'JCB';
        case 'unionpay':
            return 'UnionPay';
        default:
            return 'Card';
    }
};

export default function PaymentMethod({ onPaymentMethodsUpdated }: PaymentMethodProps) {
    const pageProps = usePage().props as any;
    const [paymentMethods, setPaymentMethods] = useState<PaymentMethod[]>([]);
    const [showAddModal, setShowAddModal] = useState(false);
    const [showAddNewOptions, setShowAddNewOptions] = useState(false);
    const [showCreditCardModal, setShowCreditCardModal] = useState(false);
    const [showPayPalModal, setShowPayPalModal] = useState(false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [methodToDelete, setMethodToDelete] = useState<number | null>(null);
    const [loading, setLoading] = useState(true);

    // Fetch payment methods from API
    const fetchPaymentMethods = async () => {
        setLoading(true);
        try {
            const response = await fetch('/guardian/payment/methods', {
                headers: {
                    'Accept': 'application/json',
                },
                signal: AbortSignal.timeout(10000), // 10 second timeout
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('[PaymentMethod] Fetched data:', data);
            if (data.payment_methods && Array.isArray(data.payment_methods)) {
                console.log('[PaymentMethod] Payment methods:', data.payment_methods);
                setPaymentMethods(data.payment_methods);
                return data.payment_methods;
            }
        } catch (error) {
            console.error('[PaymentMethod] Error fetching payment methods:', error);
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

    // Get the default payment method
    const defaultPaymentMethod = paymentMethods.find(method => method.is_default && method.is_active);

    const handleAddPaymentMethod = () => {
        setShowAddNewOptions(true);
    };

    const handleCloseAddNewOptions = () => {
        setShowAddNewOptions(false);
    };

    const handleSelectBankTransfer = () => {
        setShowAddNewOptions(false);
        setShowAddModal(true);
    };

    const handleSelectCreditCard = () => {
        setShowAddNewOptions(false);
        setShowCreditCardModal(true);
    };

    const handleSelectPayPal = () => {
        setShowAddNewOptions(false);
        setShowPayPalModal(true);
    };

    const handleCloseCreditCardModal = () => {
        setShowCreditCardModal(false);
    };

    const handleClosePayPalModal = () => {
        setShowPayPalModal(false);
    };

    const handleCreditCardSuccess = () => {
        setShowCreditCardModal(false);
        // Refresh payment methods list
        fetchPaymentMethods();
        // Notify parent component
        if (onPaymentMethodsUpdated) {
            onPaymentMethodsUpdated();
        }
    };

    const handlePayPalSuccess = () => {
        setShowPayPalModal(false);
        // Refresh payment methods list
        fetchPaymentMethods();
        // Notify parent component
        if (onPaymentMethodsUpdated) {
            onPaymentMethodsUpdated();
        }
    };

    const handleSaveChanges = () => {
        console.log('Save changes');
        setShowAddNewOptions(false);
    };

    const handleCloseModal = () => {
        setShowAddModal(false);
    };

    const handleAddBankTransfer = () => {
        // Refresh payment methods after bank transfer is added
        fetchPaymentMethods();
        // Notify parent component
        if (onPaymentMethodsUpdated) {
            onPaymentMethodsUpdated();
        }
    };

    const handleSetDefault = (methodId: number) => {
        setLoading(true);

        router.post(`/guardian/payment/methods/${methodId}/default`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                fetchPaymentMethods();
                toast.success('Default payment method updated!');
                setLoading(false);
                // Notify parent component
                if (onPaymentMethodsUpdated) {
                    onPaymentMethodsUpdated();
                }
            },
            onError: (errors) => {
                console.error('Set default errors:', errors);
                toast.error('Failed to set as default. Please try again.');
                setLoading(false);
            },
            onFinish: () => {
                setLoading(false);
            }
        });
    };

    const handleDeleteMethod = (methodId: number) => {
        setMethodToDelete(methodId);
        setShowDeleteModal(true);
    };

    const handleConfirmDelete = () => {
        if (!methodToDelete) return;

        setLoading(true);

        // Use Inertia router for proper routing
        router.delete(`/guardian/payment/methods/${methodToDelete}`, {
            preserveScroll: true,
            onSuccess: () => {
                fetchPaymentMethods();
                toast.success('Payment method deleted successfully!');
                setShowDeleteModal(false);
                setMethodToDelete(null);
                setLoading(false);
                // Notify parent component
                if (onPaymentMethodsUpdated) {
                    onPaymentMethodsUpdated();
                }
            },
            onError: (errors) => {
                console.error('Delete errors:', errors);
                toast.error('Failed to delete payment method.');
                setLoading(false);
            },
            onFinish: () => {
                setLoading(false);
            }
        });
    };

    const handleCancelDelete = () => {
        setShowDeleteModal(false);
        setMethodToDelete(null);
    };

    return (
        <div className="bg-white rounded-2xl p-6 border border-gray-200 relative">
            {loading ? (
                <div className="text-center py-8 text-gray-500">
                    Loading payment methods...
                </div>
            ) : (
                <>
                    {/* Header Description */}
                    <p className="text-gray-700 text-sm mb-6">
                        Easily manage your payment methods through our secure system.
                    </p>

                    {/* Loading State */}
                    {loading && paymentMethods.length === 0 ? (
                        <div className="text-center py-12">
                            
                            <div className="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-[#2C7870]"></div>
                            <p className="text-gray-500 mt-4">Loading payment methods...</p>
                        </div>
                    ) : (
                        <>
                            {/* Selected Payment Method Card */}
                            {defaultPaymentMethod && (
                                <div className="bg-gray-100 rounded-2xl p-4 mb-4">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center space-x-3">
                                            <ArrowLeftRight className="h-5 w-5 text-gray-900" />
                                            <span className="font-bold text-gray-900">
                                                {defaultPaymentMethod.type === 'bank_transfer' ? 'Bank Account' :
                                                    defaultPaymentMethod.type === 'mobile_money' ? 'Mobile Money' :
                                                        defaultPaymentMethod.type === 'card' ? 'Credit/Debit Card' :
                                                            defaultPaymentMethod.type === 'paypal' ? 'PayPal' : 'Payment Method'}
                                            </span>
                                            {/* Default Badge */}
                                            <span className="text-xs bg-[#2C7870] text-white px-2 py-1 rounded-full font-medium">
                                                Default
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
                                        <div className="w-4 h-4 bg-[#2C7870] rounded-full flex items-center justify-center">
                                            <div className="w-2 h-2 bg-white rounded-full"></div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* All Payment Methods List */}
                            {paymentMethods.length > 0 ? (
                                <div className="mb-6 space-y-3">
                                    <h3 className="font-bold text-gray-900 text-sm mb-3">Your Payment Methods</h3>

                                    {paymentMethods.map((method, index) => {
                                        const isCard = method.type === 'card';
                                        const isBankTransfer = method.type === 'bank_transfer';
                                        const isPayPal = method.type === 'paypal';

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
                                                        {/* Title with number and badges */}
                                                        <div className="flex items-center gap-2 mb-1">
                                                            <h3 className="text-base font-semibold text-gray-900">
                                                                {index + 1}. {isCard ? 'Bank Card' : isBankTransfer ? 'Bank Transfer' : isPayPal ? 'PayPal' : 'Payment Method'}
                                                            </h3>

                                                            {/* Default Badge */}
                                                            {method.is_default && (
                                                                <span className="text-xs bg-[#2C7870] text-white px-2 py-0.5 rounded-full font-medium">
                                                                    Default
                                                                </span>
                                                            )}

                                                            {/* Verification Badge */}
                                                            {method.is_verified ? (
                                                                <span className="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded-full font-medium">
                                                                    Verified
                                                                </span>
                                                            ) : method.verification_status === 'failed' ? (
                                                                <span className="text-xs bg-red-100 text-red-800 px-2 py-0.5 rounded-full font-medium">
                                                                    Failed
                                                                </span>
                                                            ) : (
                                                                <span className="text-xs bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded-full font-medium">
                                                                    Pending
                                                                </span>
                                                            )}
                                                        </div>

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
                                                        ) : isPayPal ? (
                                                            <p className="text-gray-700 font-medium">
                                                                {method.name}
                                                            </p>
                                                        ) : null}
                                                    </div>
                                                </div>

                                                {/* Change button */}
                                                <div className="flex items-center gap-2">
                                                    {!method.is_default && method.is_verified && (
                                                        <button
                                                            onClick={() => handleSetDefault(method.id)}
                                                            className="text-[#2C7870] hover:text-[#236158] font-medium text-sm transition-colors"
                                                        >
                                                            Set Default
                                                        </button>
                                                    )}
                                                    {!method.is_default && (
                                                        <button
                                                            onClick={() => handleDeleteMethod(method.id)}
                                                            className="text-red-600 hover:text-red-700 font-medium text-sm transition-colors"
                                                        >
                                                            Delete
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            ) : (
                                <div className="mb-6 text-center py-8">
                                    <p className="text-gray-500 text-sm">No payment methods added yet</p>
                                    <p className="text-gray-400 text-xs mt-1">Add your first payment method for easy wallet top-ups</p>
                                </div>
                            )}

                            {/* Add New Payment Method */}
                            <div
                                onClick={handleAddPaymentMethod}
                                className="flex items-center space-x-2 text-[#2C7870] cursor-pointer hover:underline mb-6"
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
                                    {/* Bank Transfer Option */}
                                    <div
                                        onClick={handleSelectBankTransfer}
                                        className="border border-gray-600 rounded-lg p-2 hover:border-gray-600 transition-colors cursor-pointer w-[50%]"
                                    >
                                        <div className="flex items-center space-x-4">
                                            <div className="w-12 h-12 flex items-center justify-center">
                                                <svg className="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                                </svg>
                                            </div>
                                            <div className="flex-1">
                                                <h3 className="font-semibold text-gray-900 text-base">
                                                    Bank Transfer
                                                </h3>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Credit Card Option */}
                                    <div
                                        onClick={handleSelectCreditCard}
                                        className="border border-gray-600 rounded-lg p-2 hover:border-gray-600 transition-colors cursor-pointer w-[50%]"
                                    >
                                        <div className="flex items-center space-x-4">
                                            <div className="w-12 h-12 flex items-center justify-center">
                                                <CreditCard className="h-6 w-6 text-gray-600" />
                                            </div>
                                            <div className="flex-1">
                                                <h3 className="font-semibold text-gray-900 text-base">
                                                    Credit/Debit Card
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
                                        className="bg-[#2C7870] hover:bg-[#2C7870]/80 text-white px-6 py-2 rounded-lg"
                                    >
                                        Save Changes
                                    </Button>
                                </div>
                            )}

                        </>
                    )}

                    {/* Add Bank Transfer Modal */}
                    {showAddModal && (
                        <AddPaymentMethodModal
                            isOpen={showAddModal}
                            onClose={() => {
                                handleCloseModal();
                                handleAddBankTransfer();
                            }}
                        />
                    )}

                    {/* Add Credit Card Modal */}
                    {showCreditCardModal && (
                        <AddCreditCardModal
                            isOpen={showCreditCardModal}
                            onClose={() => {
                                handleCloseCreditCardModal();
                                handleCreditCardSuccess();
                            }}
                            onBack={() => {
                                handleCloseCreditCardModal();
                                setShowAddNewOptions(true);
                            }}
                        />
                    )}

                    {/* Add PayPal Modal */}
                    <AddPayPalModal
                        isOpen={showPayPalModal}
                        onClose={handleClosePayPalModal}
                        onSuccess={handlePayPalSuccess}
                    />

                    {/* Delete Confirmation Modal */}
                    <ConfirmationModal
                        isOpen={showDeleteModal}
                        onClose={handleCancelDelete}
                        onConfirm={handleConfirmDelete}
                        title="Delete Payment Method"
                        message="Are you sure you want to delete this payment method? This action cannot be undone."
                        confirmText="Delete"
                        cancelText="Cancel"
                        variant="danger"
                        isLoading={loading}
                    />
                </>
            )}
        </div>
    );
}

