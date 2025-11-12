/**
 * 🎨 SUBSCRIPTION PAYMENT MODAL
 * Fully functional payment modal for subscription enrollment
 * Supports: Wallet, Credit Card (Stripe/Paystack), Bank Transfer, PayPal
 * 
 * Layout: Two-column design matching FundAccountModal
 * - Left: Payment method selector
 * - Right: Payment form details
 */
import { useState, useEffect, useRef } from 'react';
import { X } from 'lucide-react';
import { toast } from 'sonner';
import {
  CreditCardForm,
  BankTransferForm,
  PaymentConfirmation,
  LoadingState,
  ErrorState,
  UnavailablePaymentMethod
} from './fund-account-modal-components';
import { useStripeInitialization } from './hooks/useStripeInitialization';
import SubscriptionSuccessModal from './SubscriptionSuccessModal';

// Declare Stripe types
declare global {
  interface Window {
    Stripe: any;
  }
}

// Extended type for subscription payments that includes wallet
type SubscriptionPaymentMethodType = 'credit_card' | 'bank_transfer' | 'paypal' | 'wallet';

interface SubscriptionPaymentModalProps {
  isOpen: boolean;
  onClose: () => void;
  subscription: any;
  plan: {
    id: number;
    name: string;
    description?: string;
    price_naira: number;
    price_dollar: number;
  };
  amount: number;
  currency: 'NGN' | 'USD';
  paymentMethod: string;
  user: any;
  onSuccess: () => void;
}

export default function SubscriptionPaymentModal({
  isOpen,
  onClose,
  subscription,
  plan,
  amount,
  currency,
  paymentMethod: initialPaymentMethod,
  user,
  onSuccess
}: SubscriptionPaymentModalProps) {
  // State management
  const [selectedPaymentMethod, setSelectedPaymentMethod] = useState<SubscriptionPaymentMethodType>(
    initialPaymentMethod === 'card' ? 'credit_card' :
      initialPaymentMethod === 'bank_transfer' ? 'bank_transfer' :
        initialPaymentMethod === 'paypal' ? 'paypal' : 'wallet'
  );
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
    transactionId?: string;
  }>({});
  const [isProcessing, setIsProcessing] = useState(false);
  const [validationError, setValidationError] = useState<string | null>(null);
  const [cardNumberComplete, setCardNumberComplete] = useState(false);
  const [cardExpiryComplete, setCardExpiryComplete] = useState(false);
  const [cardCvcComplete, setCardCvcComplete] = useState(false);

  // Refs for Stripe elements
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

  // Fetch virtual account when bank transfer is selected
  useEffect(() => {
    if (selectedPaymentMethod === 'bank_transfer' && isOpen) {
      const fetchVirtualAccount = async () => {
        setLoadingVirtualAccount(true);
        try {
          const response = await window.axios.get('/student/payment/virtual-account');

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

          if (error.response?.data?.feature_unavailable) {
            setBankTransferUnavailable(true);
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

  // Setup Stripe element listeners for validation
  useEffect(() => {
    if (elements && selectedPaymentMethod === 'credit_card') {
      const cardNumber = elements.create('cardNumber', {
        style: {
          base: {
            fontSize: '16px',
            color: '#1f2937',
            '::placeholder': { color: '#9ca3af' }
          }
        }
      });

      const cardExpiry = elements.create('cardExpiry', {
        style: {
          base: {
            fontSize: '16px',
            color: '#1f2937',
            '::placeholder': { color: '#9ca3af' }
          }
        }
      });

      const cardCvc = elements.create('cardCvc', {
        style: {
          base: {
            fontSize: '16px',
            color: '#1f2937',
            '::placeholder': { color: '#9ca3af' }
          }
        }
      });

      cardNumber.mount('#card-number-element');
      cardExpiry.mount('#card-expiry-element');
      cardCvc.mount('#card-cvc-element');

      cardNumberElement.current = cardNumber;
      cardExpiryElement.current = cardExpiry;
      cardCvcElement.current = cardCvc;

      // Listen for changes to enable/disable button
      cardNumber.on('change', (event: any) => {
        setCardNumberComplete(event.complete);
        if (event.error) {
          setValidationError(event.error.message);
        } else {
          setValidationError(null);
        }
      });

      cardExpiry.on('change', (event: any) => {
        setCardExpiryComplete(event.complete);
        if (event.error) {
          setValidationError(event.error.message);
        } else {
          setValidationError(null);
        }
      });

      cardCvc.on('change', (event: any) => {
        setCardCvcComplete(event.complete);
        if (event.error) {
          setValidationError(event.error.message);
        } else {
          setValidationError(null);
        }
      });
    }
  }, [elements, selectedPaymentMethod]);

  // Cleanup Stripe elements
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

  // Handle payment confirmation
  const handleConfirmPayment = () => {
    if (selectedPaymentMethod === 'credit_card') {
      if (stripeLoading || stripeError || !stripe || !elements) {
        toast.error('Payment system not ready. Please wait or try again.');
        return;
      }

      if (!cardNumberElement.current || !cardExpiryElement.current || !cardCvcElement.current) {
        toast.error('Please complete all card details');
        return;
      }
    }

    setShowConfirmation(true);
  };

  // Handle final payment processing
  const handleFinalizePayment = async () => {
    setShowConfirmation(false);
    setIsProcessing(true);

    try {
      if (selectedPaymentMethod === 'wallet') {
        // Process wallet payment for subscription
        const response = await window.axios.post('/student/plans/payment/wallet', {
          subscription_id: subscription.id,
          amount: parseFloat(amount.toString()), // Ensure amount is a number
          currency: currency
        });

        if (response.data.success) {
          setSuccessData({
            amount: amount.toString(),
            transactionId: response.data.data.transaction_id
          });
          setShowSuccessModal(true);
        } else {
          toast.error(response.data.message || 'Payment failed');
        }
      } else if (selectedPaymentMethod === 'credit_card') {
        // Process credit card payment via Stripe/Paystack
        if (!stripe || !cardNumberElement.current) {
          toast.error('Payment system not ready');
          return;
        }

        const { error, paymentMethod } = await stripe.createPaymentMethod({
          type: 'card',
          card: cardNumberElement.current,
        });

        if (error) {
          toast.error(error.message || 'Card validation failed');
          return;
        }

        const response = await window.axios.post('/student/plans/payment/card', {
          subscription_id: subscription.id,
          amount: parseFloat(amount.toString()), // Ensure amount is a number
          currency: currency,
          payment_method_id: paymentMethod.id,
          save_card: rememberCard
        });

        if (response.data.success) {
          setSuccessData({
            amount: amount.toString(),
            transactionId: response.data.data.transaction_id
          });
          setShowSuccessModal(true);
        } else {
          toast.error(response.data.message || 'Payment failed');
        }
      } else if (selectedPaymentMethod === 'bank_transfer') {
        // Bank transfer - just show confirmation
        toast.success('Bank transfer instructions sent. Your subscription will be activated once payment is verified.');
        onClose();
      } else if (selectedPaymentMethod === 'paypal') {
        // Redirect to PayPal
        const response = await window.axios.post('/student/plans/payment/paypal', {
          subscription_id: subscription.id,
          amount: parseFloat(amount.toString()), // Ensure amount is a number
          currency: 'USD' // PayPal uses USD
        });

        if (response.data.success && response.data.data.approval_url) {
          window.location.href = response.data.data.approval_url;
        } else {
          toast.error('Failed to initialize PayPal payment');
        }
      }
    } catch (error: any) {
      console.error('Payment error:', error);
      toast.error(error.response?.data?.message || 'Payment failed. Please try again.');
    } finally {
      setIsProcessing(false);
    }
  };

  // Handle success modal close
  const handleSuccessClose = () => {
    setShowSuccessModal(false);
    onSuccess();
  };

  const currencySymbol = currency === 'USD' ? '$' : '₦';
  const formattedAmount = `${currencySymbol}${amount.toLocaleString()}`;

  return (
    <>
      {/* Main Payment Modal */}
      <div className="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 p-4 sm:p-6">
        <div className="bg-white rounded-2xl p-4 sm:p-6 max-w-5xl w-full max-h-[90vh] overflow-y-auto shadow-2xl">
          {/* Header */}
          <div className="flex items-center justify-between mb-4 sm:mb-6">
            <div>
              <h2 className="text-lg sm:text-xl font-semibold text-gray-900">Complete Payment</h2>
              <p className="text-sm text-gray-600 mt-1">
                {plan.name} - {formattedAmount}/{currency === 'USD' ? 'month' : 'month'}
              </p>
            </div>
            <button
              onClick={onClose}
              className="p-1 hover:bg-gray-100 rounded-lg transition-colors"
              disabled={isProcessing}
              aria-label="Close modal"
            >
              <X className="w-5 h-5 text-gray-500" />
            </button>
          </div>

          {/* Amount Display */}
          <div className="bg-teal-50 border border-teal-200 rounded-lg p-4 mb-4 sm:mb-6">
            <div className="flex items-center justify-between">
              <span className="text-sm font-medium text-teal-900">Total Amount</span>
              <span className="text-2xl font-bold text-teal-600">{formattedAmount}</span>
            </div>
          </div>

          {/* Loading State - only for credit card */}
          {selectedPaymentMethod === 'credit_card' && stripeLoading && <LoadingState />}

          {/* Error State - only for credit card */}
          {selectedPaymentMethod === 'credit_card' && stripeError && !stripeLoading && <ErrorState error={stripeError} />}

          {/* Main Content - Two Column Layout */}
          {(selectedPaymentMethod !== 'credit_card' || (!stripeLoading && !stripeError)) && (
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
              {/* Left Column: Payment Method Selector */}
              <div className="lg:col-span-1">
                <h3 className="text-base sm:text-lg font-bold text-gray-900 mb-3 sm:mb-4">Payment Methods:</h3>
                <div className="space-y-2 sm:space-y-3">
                  {/* Wallet Payment */}
                  <div
                    onClick={() => setSelectedPaymentMethod('wallet')}
                    className={`flex items-center gap-2 sm:gap-3 p-3 sm:p-4 rounded-lg cursor-pointer transition-colors border-2 ${selectedPaymentMethod === 'wallet'
                      ? 'bg-green-50 border-green-600'
                      : 'bg-white border-gray-200 hover:bg-gray-50'
                      }`}
                  >
                    <div className="text-gray-600 flex-shrink-0">
                      <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                      </svg>
                    </div>
                    <span className="font-medium text-gray-900 text-sm sm:text-base">My Wallet</span>
                  </div>

                  {/* Credit Card */}
                  <div
                    onClick={() => setSelectedPaymentMethod('credit_card')}
                    className={`flex items-center gap-2 sm:gap-3 p-3 sm:p-4 rounded-lg cursor-pointer transition-colors border-2 ${selectedPaymentMethod === 'credit_card'
                      ? 'bg-green-50 border-green-600'
                      : 'bg-white border-gray-200 hover:bg-gray-50'
                      }`}
                  >
                    <div className="text-gray-600 flex-shrink-0">
                      <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                      </svg>
                    </div>
                    <span className="font-medium text-gray-900 text-sm sm:text-base">Credit/Debit Card</span>
                  </div>

                  {/* Bank Transfer */}
                  <div
                    onClick={() => setSelectedPaymentMethod('bank_transfer')}
                    className={`flex items-center gap-2 sm:gap-3 p-3 sm:p-4 rounded-lg cursor-pointer transition-colors border-2 ${selectedPaymentMethod === 'bank_transfer'
                      ? 'bg-green-50 border-green-600'
                      : 'bg-white border-gray-200 hover:bg-gray-50'
                      }`}
                  >
                    <div className="text-gray-600 flex-shrink-0">
                      <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                      </svg>
                    </div>
                    <span className="font-medium text-gray-900 text-sm sm:text-base">Bank Transfer</span>
                  </div>

                  {/* PayPal */}
                  <div
                    onClick={() => setSelectedPaymentMethod('paypal')}
                    className={`flex items-center gap-2 sm:gap-3 p-3 sm:p-4 rounded-lg cursor-pointer transition-colors border-2 ${selectedPaymentMethod === 'paypal'
                      ? 'bg-green-50 border-green-600'
                      : 'bg-white border-gray-200 hover:bg-gray-50'
                      }`}
                  >
                    <div className="text-gray-600 flex-shrink-0">
                      <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944.901C5.026.382 5.474 0 5.998 0h7.46c2.57 0 4.578.543 5.69 1.81 1.01 1.15 1.304 2.42 1.012 4.287-.023.143-.047.288-.077.437-.983 5.05-4.349 6.797-8.647 6.797h-2.19c-.524 0-.968.382-1.05.9l-1.12 7.106zm14.146-14.42a3.35 3.35 0 0 0-.607-.541c-.013.076-.026.175-.041.254-.93 4.778-4.005 7.201-9.138 7.201h-2.19a.563.563 0 0 0-.556.479l-1.187 7.527h-.506l-.24 1.516a.56.56 0 0 0 .554.647h3.882c.46 0 .85-.334.922-.788.06-.26.76-4.852.76-4.852a.932.932 0 0 1 .922-.788h.58c3.76 0 6.705-1.528 7.565-5.946.36-1.847.174-3.388-.72-4.46z" />
                      </svg>
                    </div>
                    <span className="font-medium text-gray-900 text-sm sm:text-base">Paypal</span>
                  </div>
                </div>
              </div>

              {/* Right Column: Payment Details */}
              <div className="lg:col-span-2">
                {selectedPaymentMethod === 'wallet' ? (
                  <>
                    <h3 className="text-base sm:text-lg font-bold text-gray-900 mb-3 sm:mb-4">
                      Wallet Payment
                    </h3>
                    <div className="bg-white border border-gray-200 rounded-lg p-4 sm:p-6 shadow-sm">
                      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <p className="text-sm text-blue-900">
                          Payment will be deducted from your wallet balance.
                        </p>
                      </div>

                      {/* Action Buttons */}
                      <div className="flex items-center gap-4">
                        <button
                          onClick={handleConfirmPayment}
                          disabled={isProcessing}
                          className="bg-[#2C7870] hover:bg-[#236158] disabled:bg-[#CBD5E1] disabled:cursor-not-allowed text-white py-4 px-10 rounded-full font-semibold transition-all text-base shadow-sm hover:shadow-md disabled:shadow-none"
                        >
                          {isProcessing ? 'Processing...' : `Pay ${formattedAmount}`}
                        </button>
                        <button
                          onClick={onClose}
                          disabled={isProcessing}
                          className="bg-white border-2 border-[#2C7870] text-[#2C7870] hover:bg-[#F0F9FF] py-4 px-10 rounded-full font-semibold transition-all text-base disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                          Cancel
                        </button>
                      </div>
                    </div>
                  </>
                ) : selectedPaymentMethod === 'credit_card' ? (
                  <>
                    <h3 className="text-base sm:text-lg font-bold text-gray-900 mb-3 sm:mb-4">
                      Card Details
                    </h3>
                    <div className="bg-white border border-gray-200 rounded-lg p-4 sm:p-6 shadow-sm">
                      {/* Card Number */}
                      <div className="mb-4">
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                          Card Number
                        </label>
                        <div id="card-number-element" className="border border-gray-300 rounded-lg p-3 bg-white"></div>
                      </div>

                      {/* Expiry and CVC */}
                      <div className="grid grid-cols-2 gap-4 mb-4">
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-2">
                            Expiry Date
                          </label>
                          <div id="card-expiry-element" className="border border-gray-300 rounded-lg p-3 bg-white"></div>
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-2">
                            CVC
                          </label>
                          <div id="card-cvc-element" className="border border-gray-300 rounded-lg p-3 bg-white"></div>
                        </div>
                      </div>

                      {/* Validation Error */}
                      {validationError && (
                        <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                          <p className="text-sm text-red-600">{validationError}</p>
                        </div>
                      )}

                      {/* Remember Card */}
                      <div className="mb-6">
                        <label className="flex items-center gap-2 cursor-pointer">
                          <input
                            type="checkbox"
                            checked={rememberCard}
                            onChange={(e) => setRememberCard(e.target.checked)}
                            className="w-4 h-4 text-[#2C7870] border-gray-300 rounded focus:ring-[#2C7870]"
                          />
                          <span className="text-sm text-gray-700">Remember this card for future payments</span>
                        </label>
                      </div>

                      {/* Action Buttons */}
                      <div className="flex items-center gap-4">
                        <button
                          onClick={handleConfirmPayment}
                          disabled={isProcessing || !cardNumberComplete || !cardExpiryComplete || !cardCvcComplete}
                          className="bg-[#2C7870] hover:bg-[#236158] disabled:bg-[#CBD5E1] disabled:cursor-not-allowed text-white py-4 px-10 rounded-full font-semibold transition-all text-base shadow-sm hover:shadow-md disabled:shadow-none"
                        >
                          {isProcessing ? 'Processing...' : `Pay ${formattedAmount}`}
                        </button>
                        <button
                          onClick={onClose}
                          disabled={isProcessing}
                          className="bg-white border-2 border-[#2C7870] text-[#2C7870] hover:bg-[#F0F9FF] py-4 px-10 rounded-full font-semibold transition-all text-base disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                          Cancel
                        </button>
                      </div>
                    </div>
                  </>
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
                      amount={formattedAmount}
                      isLoading={isProcessing}
                      isFeatureUnavailable={bankTransferUnavailable}
                      onConfirm={handleFinalizePayment}
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
                      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <p className="text-sm text-blue-900">
                          You will be redirected to PayPal to complete your payment securely. PayPal only supports USD currency.
                        </p>
                      </div>

                      {/* Action Buttons */}
                      <div className="flex items-center gap-4">
                        <button
                          onClick={handleConfirmPayment}
                          disabled={isProcessing || currency !== 'USD'}
                          className="bg-[#2C7870] hover:bg-[#236158] disabled:bg-[#CBD5E1] disabled:cursor-not-allowed text-white py-4 px-10 rounded-full font-semibold transition-all text-base shadow-sm hover:shadow-md disabled:shadow-none"
                        >
                          {isProcessing ? 'Processing...' : `Pay ${formattedAmount} with PayPal`}
                        </button>
                        <button
                          onClick={onClose}
                          disabled={isProcessing}
                          className="bg-white border-2 border-[#2C7870] text-[#2C7870] hover:bg-[#F0F9FF] py-4 px-10 rounded-full font-semibold transition-all text-base disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                          Cancel
                        </button>
                      </div>

                      {currency !== 'USD' && (
                        <p className="text-sm text-red-600 mt-4">
                          PayPal only supports USD payments. Please select a different payment method or change your currency to USD.
                        </p>
                      )}
                    </div>
                  </>
                )}
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Payment Confirmation Modal */}
      {showConfirmation && (
        <PaymentConfirmation
          amount={formattedAmount}
          paymentMethod={selectedPaymentMethod === 'wallet' ? 'credit_card' : selectedPaymentMethod}
          isLoading={isProcessing}
          onConfirm={handleFinalizePayment}
          onCancel={() => setShowConfirmation(false)}
        />
      )}

      {/* Success Modal */}
      {showSuccessModal && (
        <SubscriptionSuccessModal
          isOpen={showSuccessModal}
          onClose={handleSuccessClose}
          subscription={subscription}
          transaction={{
            amount: typeof successData.amount === 'number' ? successData.amount : amount,
            currency: currency,
            payment_method: selectedPaymentMethod,
          }}
          userName={user?.name || 'Student'}
        />
      )}
    </>
  );
}
