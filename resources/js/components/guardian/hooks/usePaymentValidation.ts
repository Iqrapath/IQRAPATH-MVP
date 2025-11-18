import { useState } from 'react';
import { PAYMENT_CONFIG } from '../fund-account-modal-components/PaymentConfig';

export function usePaymentValidation(setFundingAmount: (value: string) => void) {
    const [validationError, setValidationError] = useState<string | null>(null);

    const validateAmount = (value: string): { valid: boolean; error?: string } => {
        if (!value.trim()) {
            return { valid: false, error: 'Please enter an amount' };
        }
        
        const num = parseFloat(value);
        
        if (isNaN(num)) {
            return { valid: false, error: 'Please enter a valid number' };
        }
        
        if (num < PAYMENT_CONFIG.MIN_AMOUNT) {
            return { 
                valid: false, 
                error: `Minimum amount is ${PAYMENT_CONFIG.CURRENCY_SYMBOL}${PAYMENT_CONFIG.MIN_AMOUNT.toLocaleString()}` 
            };
        }
        
        if (num > PAYMENT_CONFIG.MAX_AMOUNT) {
            return { 
                valid: false, 
                error: `Maximum amount is ${PAYMENT_CONFIG.CURRENCY_SYMBOL}${PAYMENT_CONFIG.MAX_AMOUNT.toLocaleString()}` 
            };
        }
        
        // Check for too many decimal places
        if (value.includes('.') && value.split('.')[1].length > 2) {
            return { valid: false, error: 'Maximum 2 decimal places allowed' };
        }
        
        return { valid: true };
    };

    const handleAmountChange = (value: string) => {
        setFundingAmount(value);
        const validation = validateAmount(value);
        setValidationError(validation.error || null);
    };

    return {
        validationError,
        validateAmount,
        handleAmountChange
    };
}

