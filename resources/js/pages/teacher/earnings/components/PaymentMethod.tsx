import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { ArrowLeftRight, Check, Plus, ChevronRight, X, CreditCard } from 'lucide-react';
import { usePage, router } from '@inertiajs/react';
import { toast } from 'sonner';
import AddWithdrawalModal from './AddWithdrawalModal';
import AddPayPalModal from './AddPayPalModal';
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

interface PaymentMethodProps {
    onPaymentMethodsUpdated?: () => void;
}

export default function PaymentMethod({ onPaymentMethodsUpdated }: PaymentMethodProps) {
    const pageProps = usePage().props as any;
    const [paymentMethods, setPaymentMethods] = useState<PaymentMethod[]>([]);
    const [showAddModal, setShowAddModal] = useState(false);
    const [showAddNewOptions, setShowAddNewOptions] = useState(false);
    const [showPayPalModal, setShowPayPalModal] = useState(false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [methodToDelete, setMethodToDelete] = useState<number | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    // Fetch payment methods from API
    const fetchPaymentMethods = async () => {
        try {
            setLoading(true);
            setError(null);
            const response = await fetch('/teacher/payment-methods', {
                headers: {
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Failed to fetch payment methods');
            }

            const data = await response.json();
            console.log('Fetched payment methods:', data);
            if (data.length > 0) {
                console.log('First payment method full details:', JSON.stringify(data[0], null, 2));
            }
            setPaymentMethods(data);
        } catch (err) {
            console.error('Error fetching payment methods:', err);
            setError('Failed to load payment methods');
        } finally {
            setLoading(false);
        }
    };

    // Get payment methods from props or fetch from API
    useEffect(() => {
        if (pageProps.paymentMethods) {
            setPaymentMethods(pageProps.paymentMethods);
        } else {
            fetchPaymentMethods();
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

    const handlePayPalSuccess = () => {
        setShowPayPalModal(false);
        // Refresh payment methods list
        fetchPaymentMethods();
        // Notify parent component to refresh PaymentInfo tab
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
        // Notify parent component to refresh PaymentInfo tab
        if (onPaymentMethodsUpdated) {
            onPaymentMethodsUpdated();
        }
    };

    const handleAddMobileWallet = () => {
        // Refresh payment methods after mobile wallet is added
        fetchPaymentMethods();
        // Notify parent component to refresh PaymentInfo tab
        if (onPaymentMethodsUpdated) {
            onPaymentMethodsUpdated();
        }
    };

    const handleChangePaymentMethod = (_methodId: number) => {
        // TODO: Load specific method details for editing
        setShowAddModal(true);
    };

    const handleRetryVerification = async (methodId: number) => {
        try {
            setLoading(true);

            // Get CSRF token from meta tag
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const url = `${window.location.origin}/teacher/payment-methods/${methodId}/verify`;

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // Success - refresh payment methods
                await fetchPaymentMethods();
                toast.success('Bank account verified successfully!');
            } else {
                // Failed - show error
                toast.error(data.message || 'Verification failed. Please try again later.');
            }
        } catch (error) {
            console.error('Error retrying verification:', error);
            toast.error('An error occurred. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    const handleSetDefault = (methodId: number) => {
        setLoading(true);

        router.patch(`/teacher/payment-methods/${methodId}/set-default`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                fetchPaymentMethods();
                toast.success('Default payment method updated!');
                setLoading(false);
                // Notify parent component to refresh PaymentInfo tab
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
        router.delete(`/teacher/payment-methods/${methodToDelete}`, {
            preserveScroll: true,
            onSuccess: () => {
                fetchPaymentMethods();
                toast.success('Payment method deleted successfully!');
                setShowDeleteModal(false);
                setMethodToDelete(null);
                setLoading(false);
                // Notify parent component to refresh PaymentInfo tab
                if (onPaymentMethodsUpdated) {
                    onPaymentMethodsUpdated();
                }
            },
            onError: (errors) => {
                console.error('Delete errors:', errors);
                toast.error(errors.error || 'Failed to delete payment method.');
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
                                <div className="flex items-center gap-2">
                                    <span className="text-xs bg-red-100 text-red-800 px-2 py-1 rounded-full font-medium">
                                        Failed
                                    </span>
                                    <button
                                        onClick={() => handleRetryVerification(defaultPaymentMethod.id)}
                                        className="text-xs text-[#338078] hover:underline font-medium"
                                    >
                                        Retry
                                    </button>
                                </div>
                            ) : (
                                <div className="flex items-center gap-2">
                                    <span className="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full font-medium">
                                        Pending
                                    </span>
                                    <button
                                        onClick={() => handleRetryVerification(defaultPaymentMethod.id)}
                                        className="text-xs text-[#338078] hover:underline font-medium"
                                    >
                                        Retry
                                    </button>
                                </div>
                            )}
                        </div>
                        <div className="w-4 h-4 bg-[#338078] rounded-full flex items-center justify-center">
                            <div className="w-2 h-2 bg-white rounded-full"></div>
                        </div>
                    </div>
                </div>
            )}

            {/* All Payment Methods List */}
            {paymentMethods.length > 0 ? (
                <div className="mb-6 space-y-3">
                    <h3 className="font-bold text-gray-900 text-sm mb-3">Your Payment Methods</h3>

                    {paymentMethods.map((method, index) => (
                        <div
                            key={method.id}
                            className={`border rounded-xl p-4 ${method.is_default ? 'border-[#338078] bg-[#338078]/5' : 'border-gray-200'}`}
                        >
                            <div className="flex items-start justify-between">
                                <div className="flex-1">
                                    {/* Method Type and Status */}
                                    <div className="flex items-center gap-2 mb-2">
                                        <span className="font-semibold text-gray-900 text-sm">
                                            {index + 1}. {method.type === 'bank_transfer' ? 'Bank Account' :
                                                method.type === 'mobile_money' ? 'Mobile Money' :
                                                    method.type === 'paypal' ? 'PayPal' : 'Card'}
                                        </span>

                                        {method.is_default && (
                                            <span className="text-xs bg-[#338078] text-white px-2 py-0.5 rounded-full font-medium">
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

                                    {/* Method Details */}
                                    {method.type === 'bank_transfer' && (
                                        <>
                                            <p className="text-gray-900 font-medium text-sm">
                                                {method.bank_name ||
                                                    (method.details as any)?.bank_name ||
                                                    'Bank Name'}
                                            </p>
                                            <p className="text-gray-600 text-xs mt-1">
                                                {method.account_name ||
                                                    (method.details as any)?.account_holder ||
                                                    'Account Holder'} |
                                                {method.last_four
                                                    ? ` ...${method.last_four}`
                                                    : (method.details as any)?.account_number
                                                        ? ` ...${(method.details as any).account_number.slice(-4)}`
                                                        : ` Account Number`}
                                            </p>
                                        </>
                                    )}

                                    {method.type === 'mobile_money' && (
                                        <>
                                            <p className="text-gray-900 font-medium text-sm">
                                                {method.provider || 'Provider'}
                                            </p>
                                            <p className="text-gray-600 text-xs mt-1">
                                                {method.phone_number || 'Phone Number'}
                                            </p>
                                        </>
                                    )}

                                    {method.type === 'paypal' && (
                                        <p className="text-gray-900 font-medium text-sm">
                                            {method.name}
                                        </p>
                                    )}

                                    {/* Action Buttons */}
                                    <div className="flex items-center gap-3 mt-3">
                                        {!method.is_default && method.is_verified && (
                                            <button
                                                onClick={() => handleSetDefault(method.id)}
                                                className="text-xs text-[#338078] hover:underline font-medium"
                                            >
                                                Set as Default
                                            </button>
                                        )}

                                        {!method.is_verified && (
                                            <button
                                                onClick={() => handleRetryVerification(method.id)}
                                                className="text-xs text-[#338078] hover:underline font-medium"
                                            >
                                                Retry Verification
                                            </button>
                                        )}

                                        {!method.is_default && (
                                            <button
                                                onClick={() => handleDeleteMethod(method.id)}
                                                className="text-xs text-red-600 hover:underline font-medium"
                                            >
                                                Delete
                                            </button>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            ) : (
                <div className="mb-6 text-center py-8">
                    <p className="text-gray-500 text-sm">No payment methods added yet</p>
                    <p className="text-gray-400 text-xs mt-1">Add your first payment method to receive payouts</p>
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
        </div>
    );
}
