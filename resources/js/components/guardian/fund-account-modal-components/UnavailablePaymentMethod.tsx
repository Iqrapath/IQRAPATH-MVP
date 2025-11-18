interface UnavailablePaymentMethodProps {
    method: 'bank_transfer' | 'paypal';
    onUseCreditCard: () => void;
    onCancel: () => void;
}

export default function UnavailablePaymentMethod({
    method,
    onUseCreditCard,
    onCancel
}: UnavailablePaymentMethodProps) {
    return (
        <div className="text-center py-6 sm:py-8">
            <div className="w-12 h-12 sm:w-16 sm:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 sm:mb-4">
                <div className="text-gray-400 text-xl sm:text-2xl">
                    {method === 'bank_transfer' ? '🏦' : '💳'}
                </div>
            </div>
            <h4 className="text-base sm:text-lg font-semibold text-gray-900 mb-2">
                {method === 'bank_transfer' ? 'Bank Transfer' : 'PayPal'} Not Available
            </h4>
            <p className="text-sm sm:text-base text-gray-600 mb-4 sm:mb-6 px-2">
                {method === 'bank_transfer'
                    ? 'Bank transfer payment method is not available yet. Please use Credit/Debit Card for now.'
                    : 'PayPal payment method is not available yet. Please use Credit/Debit Card for now.'
                }
            </p>
            <div className="flex flex-col sm:flex-row gap-2 sm:gap-3 justify-center">
                <button
                    onClick={onUseCreditCard}
                    className="bg-[#2C7870] hover:bg-[#236158] text-white py-2 sm:py-3 px-4 sm:px-6 rounded-lg font-medium transition-colors text-sm sm:text-base"
                >
                    Use Credit/Debit Card
                </button>
                <button
                    onClick={onCancel}
                    className="bg-white border border-[#2C7870] text-[#2C7870] hover:bg-[#2C7870] hover:text-white py-2 sm:py-3 px-4 sm:px-6 rounded-lg font-medium transition-colors text-sm sm:text-base"
                >
                    Cancel
                </button>
            </div>
        </div>
    );
}

