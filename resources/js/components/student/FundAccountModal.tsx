/**
 * 🎨 FIGMA REFERENCE
 * URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAQUEST?node-id=542-68353&t=O1w7ozri9pYud8IO-0
 * Export: Fund Account Modal with exact payment interface design
 * 
 * REFACTORED: Broken into smaller components for better maintainability
 */
import { useState, useEffect, useRef } from 'react';
import { X } from 'lucide-react';
import { toast } from 'sonner';
import { router } from '@inertiajs/react';
import {
    AmountInput,
    PaymentMethodSelector,
    CreditCardForm,
    PaymentConfirmation,
    LoadingState,
    ErrorState,
    UnavailablePaymentMethod,
    PAYMENT_CONFIG,
    FundAccountModalProps
} from './fund-account-modal-components';
import { useStripeInitialization } from './hooks/useStripeInitialization';
import { usePaymentValidation } from './hooks/usePaymentValidation';
import { usePaymentProcessing } from './hooks/usePaymentProcessing';

// Declare Stripe types
declare global {
    interface Window {
        Stripe: any;
    }
}

export default function FundAccountModal({
    isOpen,
    onClose,
    onPayment,
    amount = 0,
    currency = PAYMENT_CONFIG.CURRENCY_SYMBOL,
    user
}: FundAccountModalProps) {
    // State management
    const [selectedPaymentMethod, setSelectedPaymentMethod] = useState<'credit_card' | 'bank_transfer' | 'paypal'>('credit_card');
    const [fundingAmount, setFundingAmount] = useState(amount > 0 ? amount.toString() : '');
    const [rememberCard, setRememberCard] = useState(false);
    const [showConfirmation, setShowConfirmation] = useState(false);

    // Refs for separate Stripe elements
    const cardNumberElement = useRef<any>(null);
    const cardExpiryElement = useRef<any>(null);
    const cardCvcElement = useRef<any>(null);

    // Custom hooks
    const {
        stripe,
        elements,
        stripeLoading,
        stripeError
    } = useStripeInitialization(isOpen);

    const {
        validationError,
        validateAmount,
        handleAmountChange
    } = usePaymentValidation(setFundingAmount);

    const {
        isLoading,
        handleMakePayment
    } = usePaymentProcessing(
        stripe,
        cardNumberElement,
        user,
        onPayment,
        onClose
    );

    // Cleanup
    useEffect(() => {
        return () => {
            if (cardNumberElement.current) {
                cardNumberElement.current.destroy();
                cardNumberElement.current = null;
            }
            if (cardExpiryElement.current) {
                cardExpiryElement.current.destroy();
                cardExpiryElement.current = null;
            }
            if (cardCvcElement.current) {
                cardCvcElement.current.destroy();
                cardCvcElement.current = null;
            }
        };
    }, []);

    if (!isOpen) return null;

    // Form validation
    const isFormValid = (): boolean => {
        if (selectedPaymentMethod !== 'credit_card') return false;
        if (stripeLoading || stripeError) return false;
        if (!stripe || !elements) return false;
        if (!cardNumberElement.current || !cardExpiryElement.current || !cardCvcElement.current) return false;

        const validation = validateAmount(fundingAmount);
        if (!validation.valid) return false;

        return true;
    };

    // Handle payment confirmation
    const handleConfirmPayment = () => {
        const validation = validateAmount(fundingAmount);
        if (!validation.valid) {
            toast.error(validation.error);
            return;
        }

        if (!isFormValid()) {
            toast.error('Please complete all required fields');
            return;
        }

        setShowConfirmation(true);
    };

    // Handle final payment
    const handleFinalizePayment = async () => {
        setShowConfirmation(false);
        await handleMakePayment(
            fundingAmount,
            rememberCard
        );
    };

    return (
        <div className="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 p-4 sm:p-6">
            <div className="bg-white rounded-2xl p-4 sm:p-6 max-w-5xl w-full max-h-[90vh] overflow-y-auto shadow-2xl">
                {/* Header */}
                <div className="flex items-center justify-between mb-4 sm:mb-6">
                    <h2 className="text-lg sm:text-xl font-semibold text-gray-900">Fund Account</h2>
                    <button
                        onClick={onClose}
                        className="p-1 hover:bg-gray-100 rounded-lg transition-colors"
                        aria-label="Close modal"
                    >
                        <X className="w-5 h-5 text-gray-500" />
                    </button>
                </div>

                {/* Loading State */}
                {stripeLoading && <LoadingState />}

                {/* Error State */}
                {stripeError && !stripeLoading && <ErrorState error={stripeError} />}

                {/* Main Content */}
                {!stripeLoading && !stripeError && (
                    <>
                        {/* Amount Input */}
                        <AmountInput
                            amount={fundingAmount}
                            currency={currency}
                            validationError={validationError}
                            isLoading={isLoading}
                            onChange={(value) => handleAmountChange(value)}
                        />

                        {/* Two-Column Layout */}
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
                            {/* Payment Method Selector */}
                            <PaymentMethodSelector
                                selectedMethod={selectedPaymentMethod}
                                onSelect={setSelectedPaymentMethod}
                            />

                            {/* Payment Details */}
                            <div className="lg:col-span-2">
                                {selectedPaymentMethod === 'credit_card' ? (
                                    <CreditCardForm
                                        elements={elements}
                                        cardNumberElement={cardNumberElement}
                                        cardExpiryElement={cardExpiryElement}
                                        cardCvcElement={cardCvcElement}
                                        rememberCard={rememberCard}
                                        isLoading={isLoading}
                                        isFormValid={isFormValid()}
                                        onRememberCardChange={setRememberCard}
                                        onSubmit={handleConfirmPayment}
                                        onCancel={onClose}
                                        setValidationError={(error) => handleAmountChange(fundingAmount)}
                                    />
                                ) : (
                                    <>
                                        <h3 className="text-base sm:text-lg font-bold text-gray-900 mb-3 sm:mb-4">
                                            {selectedPaymentMethod === 'bank_transfer'
                                                ? 'Bank Transfer Details'
                                                : 'PayPal Payment Details'
                                            }
                                        </h3>
                                        <div className="bg-white border border-gray-200 rounded-lg p-4 sm:p-6 shadow-sm">
                                            <UnavailablePaymentMethod
                                                method={selectedPaymentMethod}
                                                onUseCreditCard={() => setSelectedPaymentMethod('credit_card')}
                                                onCancel={onClose}
                                            />
                                        </div>
                                    </>
                                )}
                            </div>
                        </div>
                    </>
                )}

                {/* Confirmation Dialog */}
                {showConfirmation && (
                    <PaymentConfirmation
                        amount={fundingAmount}
                        rememberCard={rememberCard}
                        isLoading={isLoading}
                        onConfirm={handleFinalizePayment}
                        onCancel={() => setShowConfirmation(false)}
                    />
                )}
            </div>
        </div>
    );
}
