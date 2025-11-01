import { PAYMENT_CONFIG } from './PaymentConfig';

interface PaymentConfirmationProps {
    amount: string;
    rememberCard: boolean;
    isLoading: boolean;
    onConfirm: () => void;
    onCancel: () => void;
}

export default function PaymentConfirmation({
    amount,
    rememberCard,
    isLoading,
    onConfirm,
    onCancel
}: PaymentConfirmationProps) {
    return (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[60] p-4">
            <div className="bg-white rounded-2xl p-6 max-w-md w-full shadow-2xl">
                <h3 className="text-xl font-bold text-gray-900 mb-4">Confirm Payment</h3>
                <div className="space-y-3 mb-6">
                    <div className="flex justify-between">
                        <span className="text-gray-600">Amount:</span>
                        <span className="font-semibold text-gray-900">
                            {PAYMENT_CONFIG.CURRENCY_SYMBOL}{parseFloat(amount).toLocaleString()}
                        </span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-gray-600">Payment Method:</span>
                        <span className="font-semibold text-gray-900">Credit/Debit Card</span>
                    </div>
                    {rememberCard && (
                        <div className="flex justify-between">
                            <span className="text-gray-600">Save Card:</span>
                            <span className="font-semibold text-gray-900">Yes</span>
                        </div>
                    )}
                </div>
                <p className="text-sm text-gray-600 mb-6">
                    Are you sure you want to proceed with this payment?
                </p>
                <div className="flex gap-3">
                    <button
                        onClick={onConfirm}
                        disabled={isLoading}
                        className="flex-1 bg-[#2C7870] hover:bg-[#236158] disabled:bg-gray-300 text-white py-3 px-4 rounded-lg font-medium transition-colors disabled:cursor-not-allowed"
                    >
                        {isLoading ? 'Processing...' : 'Confirm Payment'}
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
