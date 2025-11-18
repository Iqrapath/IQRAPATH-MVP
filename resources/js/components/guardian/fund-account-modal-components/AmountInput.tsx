import { PAYMENT_CONFIG } from './PaymentConfig';

interface AmountInputProps {
    amount: string;
    currency: string;
    validationError: string | null;
    isLoading: boolean;
    onChange: (value: string) => void;
}

export default function AmountInput({
    amount,
    currency,
    validationError,
    isLoading,
    onChange
}: AmountInputProps) {
    return (
        <div className="mb-4 sm:mb-6">
            <label className="block text-sm font-medium text-gray-700 mb-2">
                Amount to Fund
            </label>
            <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span className="text-gray-500 text-base sm:text-lg">{currency}</span>
                </div>
                <input
                    type="number"
                    value={amount}
                    onChange={(e) => onChange(e.target.value)}
                    placeholder="Enter amount"
                    min={PAYMENT_CONFIG.MIN_AMOUNT}
                    max={PAYMENT_CONFIG.MAX_AMOUNT}
                    step="0.01"
                    disabled={isLoading}
                    className={`w-full pl-8 sm:pl-10 pr-4 py-2 sm:py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-[#2C7870] focus:border-transparent text-sm sm:text-base disabled:bg-gray-100 disabled:cursor-not-allowed ${
                        validationError ? 'border-red-300' : 'border-gray-300'
                    }`}
                />
            </div>
            {validationError ? (
                <p className="text-xs text-red-600 mt-1">{validationError}</p>
            ) : (
                <p className="text-xs text-gray-500 mt-1">
                    Minimum: {PAYMENT_CONFIG.CURRENCY_SYMBOL}{PAYMENT_CONFIG.MIN_AMOUNT.toLocaleString()}, 
                    Maximum: {PAYMENT_CONFIG.CURRENCY_SYMBOL}{PAYMENT_CONFIG.MAX_AMOUNT.toLocaleString()}
                </p>
            )}
        </div>
    );
}

