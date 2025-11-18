// Payment configuration constants
export const PAYMENT_CONFIG = {
    MIN_AMOUNT: 1000,
    MAX_AMOUNT: 1000000,
    CURRENCY: 'NGN',
    CURRENCY_SYMBOL: '₦',
    TIMEOUT_MS: 30000,
    STRIPE_INIT_TIMEOUT: 10000,
} as const;

export interface PaymentMethod {
    id: string;
    type: 'credit_card' | 'bank_transfer' | 'paypal';
    name: string;
    icon: React.ReactNode;
}

export interface FundAccountModalProps {
    isOpen: boolean;
    onClose: () => void;
    onPayment: (paymentData: any) => void;
    amount?: number;
    currency?: string;
    user?: {
        id: number;
        name: string;
        email: string;
        country: string;
    };
}

