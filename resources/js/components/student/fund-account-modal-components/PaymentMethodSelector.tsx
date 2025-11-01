import { ArrowLeftRight, CreditCard as CardIcon } from 'lucide-react';
import { toast } from 'sonner';
import { PaymentMethod } from './PaymentConfig';

interface PaymentMethodSelectorProps {
    selectedMethod: 'credit_card' | 'bank_transfer' | 'paypal';
    onSelect: (method: 'credit_card' | 'bank_transfer' | 'paypal') => void;
}

export default function PaymentMethodSelector({
    selectedMethod,
    onSelect
}: PaymentMethodSelectorProps) {
    const paymentMethods: PaymentMethod[] = [
        {
            id: 'credit_card',
            type: 'credit_card',
            name: 'Credit/Debit Card',
            icon: <CardIcon className="w-5 h-5" />
        },
        {
            id: 'bank_transfer',
            type: 'bank_transfer',
            name: 'Bank Transfer',
            icon: <ArrowLeftRight className="w-5 h-5" />
        },
        {
            id: 'paypal',
            type: 'paypal',
            name: 'Paypal',
            icon: <div className="w-5 h-5 bg-blue-600 rounded-sm flex items-center justify-center text-white text-xs font-bold">P</div>
        }
    ];

    const handleSelect = (methodType: 'credit_card' | 'bank_transfer' | 'paypal') => {
        onSelect(methodType);
        
        // Show toast for unavailable methods
        if (methodType === 'bank_transfer' || methodType === 'paypal') {
            toast.error(`${methodType === 'bank_transfer' ? 'Bank Transfer' : 'PayPal'} payment method is not available yet. Please use Credit/Debit Card.`);
        }
    };

    return (
        <div className="lg:col-span-1">
            <h3 className="text-base sm:text-lg font-bold text-gray-900 mb-3 sm:mb-4">Payment Methods:</h3>
            <div className="space-y-2 sm:space-y-3">
                {paymentMethods.map((method) => (
                    <div
                        key={method.id}
                        onClick={() => handleSelect(method.type)}
                        className={`flex items-center gap-2 sm:gap-3 p-3 sm:p-4 rounded-lg cursor-pointer transition-colors border-2 ${
                            selectedMethod === method.type
                                ? 'bg-green-50 border-green-600'
                                : 'bg-white border-gray-200 hover:bg-gray-50'
                        }`}
                    >
                        <div className="text-gray-600 flex-shrink-0">
                            {method.icon}
                        </div>
                        <span className="font-medium text-gray-900 text-sm sm:text-base">{method.name}</span>
                    </div>
                ))}
            </div>
        </div>
    );
}
