import { PAYMENT_CONFIG } from './PaymentConfig';

interface PaymentConfirmationProps {
    amount: string;
    paymentMethod: 'credit_card' | 'bank_transfer' | 'paypal';
    rememberCard?: boolean;
    bankDetails?: {
        accountNumber: string;
        bankName: string;
        beneficiaryName: string;
    };
    isLoading: boolean;
    onConfirm: () => void;
    onCancel: () => void;
}

export default function PaymentConfirmation({
    amount,
    paymentMethod,
    rememberCard = false,
    bankDetails,
    isLoading,
    onConfirm,
    onCancel
}: PaymentConfirmationProps) {
    const getPaymentMethodLabel = () => {
        switch (paymentMethod) {
            case 'credit_card':
                return 'Credit/Debit Card';
            case 'bank_transfer':
                return 'Bank Transfer';
            case 'paypal':
                return 'PayPal';
            default:
                return 'Unknown';
        }
    };

    const getConfirmationMessage = () => {
        switch (paymentMethod) {
            case 'credit_card':
                return 'Are you sure you want to proceed with this payment?';
            case 'bank_transfer':
                return 'Please confirm that you have completed the bank transfer to the account shown above. Your wallet will be credited automatically within a few minutes once Paystack verifies your payment.';
            case 'paypal':
                return 'You will be redirected to PayPal to complete the payment.';
            default:
                return 'Are you sure you want to proceed?';
        }
    };

    const getConfirmButtonText = () => {
        if (isLoading) return 'Processing...';

        switch (paymentMethod) {
            case 'credit_card':
                return 'Confirm Payment';
            case 'bank_transfer':
                return 'I Have Made This Transfer';
            case 'paypal':
                return 'Continue to PayPal';
            default:
                return 'Confirm';
        }
    };

    return (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[60] p-4">
            <div className="bg-white rounded-2xl p-6 max-w-md w-full shadow-2xl">
                <h3 className="text-xl font-bold text-gray-900 mb-4">Confirm Payment</h3>

                <div className="space-y-3 mb-6">
                    {/* Amount */}
                    {amount && parseFloat(amount) > 0 && (
                        <div className="flex justify-between">
                            <span className="text-gray-600">Amount:</span>
                            <span className="font-semibold text-gray-900">
                                {PAYMENT_CONFIG.CURRENCY_SYMBOL}{parseFloat(amount).toLocaleString()}
                            </span>
                        </div>
                    )}

                    {/* Payment Method */}
                    <div className="flex justify-between">
                        <span className="text-gray-600">Payment Method:</span>
                        <span className="font-semibold text-gray-900">{getPaymentMethodLabel()}</span>
                    </div>

                    {/* Bank Transfer Details */}
                    {paymentMethod === 'bank_transfer' && bankDetails && (
                        <div className="mt-4 p-4 bg-gray-50 rounded-lg space-y-2">
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-600">Account Number:</span>
                                <span className="font-mono font-semibold text-gray-900">{bankDetails.accountNumber}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-600">Bank Name:</span>
                                <span className="font-semibold text-gray-900">{bankDetails.bankName}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-600">Beneficiary:</span>
                                <span className="font-semibold text-gray-900">{bankDetails.beneficiaryName}</span>
                            </div>
                        </div>
                    )}

                    {/* Save Card Option (Credit Card Only) */}
                    {paymentMethod === 'credit_card' && rememberCard && (
                        <div className="flex justify-between">
                            <span className="text-gray-600">Save Card:</span>
                            <span className="font-semibold text-gray-900">Yes</span>
                        </div>
                    )}
                </div>

                {/* Confirmation Message */}
                <p className="text-sm text-gray-600 mb-6">
                    {getConfirmationMessage()}
                </p>

                {/* Action Buttons */}
                <div className="flex gap-3">
                    <button
                        onClick={onConfirm}
                        disabled={isLoading}
                        className="flex-1 bg-[#2C7870] hover:bg-[#236158] disabled:bg-gray-300 text-white py-3 px-4 rounded-lg font-medium transition-colors disabled:cursor-not-allowed"
                    >
                        {getConfirmButtonText()}
                    </button>
                    <button
                        onClick={onCancel}
                        disabled={isLoading}
                        className="flex-1 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 py-3 px-4 rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    );
}
