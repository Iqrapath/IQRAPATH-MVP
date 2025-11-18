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
  BankTransferForm,
  PaymentConfirmation,
  PaymentSuccessModal,
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
  const [bankTransferSettings, setBankTransferSettings] = useState({
    accountNumber: '',
    bankName: '',
    beneficiaryName: ''
  });
  const [loadingVirtualAccount, setLoadingVirtualAccount] = useState(false);
  const [bankTransferUnavailable, setBankTransferUnavailable] = useState(false);
  const [showSuccessModal, setShowSuccessModal] = useState(false);
  const [successData, setSuccessData] = useState<{
    amount?: string;
    newBalance?: number;
    transactionId?: string;
  }>({});

  // Refs for separate Stripe elements
  const cardNumberElement = useRef<any>(null);
  const cardExpiryElement = useRef<any>(null);
  const cardCvcElement = useRef<any>(null);

  // Custom hooks - only initialize Stripe for credit card payments
  const {
    stripe,
    elements,
    stripeLoading,
    stripeError
  } = useStripeInitialization(isOpen && selectedPaymentMethod === 'credit_card');

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

  // Fetch virtual account when bank transfer is selected
  useEffect(() => {
    if (selectedPaymentMethod === 'bank_transfer' && isOpen) {
      const fetchVirtualAccount = async () => {
        setLoadingVirtualAccount(true);
        try {
          const endpoint = '/guardian/payment/virtual-account';

          const response = await window.axios.get(endpoint);

          if (response.data.success) {
            setBankTransferSettings({
              accountNumber: response.data.data.account_number,
              bankName: response.data.data.bank_name,
              beneficiaryName: response.data.data.account_name
            });
          } else {
            toast.error(response.data.message || 'Failed to load bank transfer details');
          }
        } catch (error: any) {
          console.error('Failed to fetch virtual account:', error);

          // Check if it's a feature unavailable error
          if (error.response?.data?.feature_unavailable) {
            setBankTransferUnavailable(true);
            // Don't show toast, the component will show a nice message
          } else {
            toast.error('Failed to load bank transfer details. Please try again.');
            setBankTransferUnavailable(true);
          }
        } finally {
          setLoadingVirtualAccount(false);
        }
      };

      fetchVirtualAccount();
    }
  }, [selectedPaymentMethod, isOpen]);

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
    // For credit card, validate amount and form
    if (selectedPaymentMethod === 'credit_card') {
      const validation = validateAmount(fundingAmount);
      if (!validation.valid) {
        toast.error(validation.error);
        return;
      }

      if (!isFormValid()) {
        toast.error('Please complete all required fields');
        return;
      }
    }

    // For PayPal, validate amount
    if (selectedPaymentMethod === 'paypal') {
      const amount = parseFloat(fundingAmount);
      if (!fundingAmount || isNaN(amount) || amount < 1) {
        toast.error('Please enter a valid amount (minimum $1 USD)');
        return;
      }
    }

    // For bank transfer, just show confirmation
    // (user has already made the transfer)
    setShowConfirmation(true);
  };

  // Handle final payment
  const handleFinalizePayment = async () => {
    setShowConfirmation(false);

    if (selectedPaymentMethod === 'credit_card') {
      // Process credit card payment
      const result = await handleMakePayment(fundingAmount, rememberCard);

      // Show success modal if payment was successful
      if (result) {
        setSuccessData({
          amount: fundingAmount,
          newBalance: result.newBalance,
          transactionId: result.transactionId
        });
        setShowSuccessModal(true);
      }
    } else if (selectedPaymentMethod === 'bank_transfer') {
      // For bank transfer, show success modal with pending message
      setSuccessData({
        amount: fundingAmount
      });
      setShowSuccessModal(true);
    } else if (selectedPaymentMethod === 'paypal') {
      // Redirect to PayPal with loading state
      try {
        // Show loading toast
        toast.loading('Initializing PayPal payment...', { id: 'paypal-init' });

        const endpoint = '/guardian/wallet/fund/paypal';

        const response = await window.axios.post(endpoint, {
          amount: parseFloat(fundingAmount),
          currency: 'USD' // PayPal uses USD
        });

        if (response.data.success && response.data.data.approval_url) {
          // Success - show redirecting message
          toast.success('Redirecting to PayPal...', { id: 'paypal-init' });
          
          // Small delay to show the success message
          setTimeout(() => {
            window.location.href = response.data.data.approval_url;
          }, 500);
        } else {
          toast.error('Failed to initialize PayPal payment', { id: 'paypal-init' });
        }
      } catch (error: any) {
        console.error('PayPal payment error:', error);
        toast.error(error.response?.data?.message || 'Failed to initialize PayPal payment. Please try again.', { id: 'paypal-init' });
      }
    }
  };

  // Handle success modal close
  const handleSuccessClose = () => {
    setShowSuccessModal(false);
    setSuccessData({});
    onClose();
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

        {/* Loading State - only for credit card */}
        {selectedPaymentMethod === 'credit_card' && stripeLoading && <LoadingState />}

        {/* Error State - only for credit card */}
        {selectedPaymentMethod === 'credit_card' && stripeError && !stripeLoading && <ErrorState error={stripeError} />}

        {/* Main Content */}
        {(selectedPaymentMethod !== 'credit_card' || (!stripeLoading && !stripeError)) && (
          <>
            {/* Amount Input - Only for credit card */}
            {selectedPaymentMethod === 'credit_card' && (
              <AmountInput
                amount={fundingAmount}
                currency={currency}
                validationError={validationError}
                isLoading={isLoading}
                onChange={(value) => handleAmountChange(value)}
              />
            )}

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
                ) : selectedPaymentMethod === 'bank_transfer' ? (
                  loadingVirtualAccount ? (
                    <div className="flex items-center justify-center py-12">
                      <div className="text-center">
                        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#2C7870] mx-auto mb-4"></div>
                        <p className="text-gray-600">Loading bank transfer details...</p>
                      </div>
                    </div>
                  ) : (
                    <BankTransferForm
                      accountNumber={bankTransferSettings.accountNumber}
                      bankName={bankTransferSettings.bankName}
                      beneficiaryName={bankTransferSettings.beneficiaryName}
                      amount={fundingAmount}
                      isLoading={isLoading}
                      isFeatureUnavailable={bankTransferUnavailable}
                      onConfirm={handleConfirmPayment}
                      onCancel={onClose}
                      onUseCreditCard={() => setSelectedPaymentMethod('credit_card')}
                    />
                  )
                ) : (
                  <>
                    <h3 className="text-base sm:text-lg font-bold text-gray-900 mb-3 sm:mb-4">
                      PayPal Payment
                    </h3>
                    <div className="bg-white border border-gray-200 rounded-lg p-4 sm:p-6 shadow-sm">
                      {/* Amount Input for PayPal */}
                      <div className="mb-6">
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                          Amount to Fund (USD)
                        </label>
                        <div className="relative">
                          <span className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-medium">$</span>
                          <input
                            type="number"
                            min="1"
                            step="0.01"
                            value={fundingAmount}
                            onChange={(e) => handleAmountChange(e.target.value)}
                            placeholder="0.00"
                            disabled={isLoading}
                            className="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2C7870] focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
                          />
                        </div>
                        {validationError && (
                          <p className="text-sm text-red-600 mt-2">{validationError}</p>
                        )}
                      </div>

                      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <p className="text-sm text-blue-900">
                          You will be redirected to PayPal to complete your payment securely. PayPal only supports USD currency.
                        </p>
                      </div>

                      {/* Action Buttons */}
                      <div className="flex items-center gap-4">
                        <button
                          onClick={handleConfirmPayment}
                          disabled={isLoading || !fundingAmount || parseFloat(fundingAmount) < 1}
                          className="bg-[#2C7870] hover:bg-[#236158] disabled:bg-[#CBD5E1] disabled:cursor-not-allowed text-white py-4 px-10 rounded-full font-semibold transition-all text-base shadow-sm hover:shadow-md disabled:shadow-none flex items-center justify-center gap-2"
                        >
                          {isLoading && (
                            <svg className="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                              <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                              <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                          )}
                          {isLoading ? 'Redirecting to PayPal...' : `Pay $${fundingAmount || '0.00'} with PayPal`}
                        </button>
                        <button
                          onClick={onClose}
                          disabled={isLoading}
                          className="bg-white border-2 border-[#2C7870] text-[#2C7870] hover:bg-[#F0F9FF] py-4 px-10 rounded-full font-semibold transition-all text-base disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                          Cancel
                        </button>
                      </div>
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
            paymentMethod={selectedPaymentMethod}
            rememberCard={rememberCard}
            bankDetails={selectedPaymentMethod === 'bank_transfer' ? bankTransferSettings : undefined}
            isLoading={isLoading}
            onConfirm={handleFinalizePayment}
            onCancel={() => setShowConfirmation(false)}
          />
        )}

        {/* Success Modal */}
        <PaymentSuccessModal
          isOpen={showSuccessModal}
          amount={successData.amount}
          paymentMethod={selectedPaymentMethod}
          newBalance={successData.newBalance}
          transactionId={successData.transactionId}
          onClose={handleSuccessClose}
        />
      </div>
    </div>
  );
}

