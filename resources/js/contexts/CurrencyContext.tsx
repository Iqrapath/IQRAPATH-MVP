import React, { createContext, useContext, useState, ReactNode } from 'react';
import { toast } from 'sonner';

// Currency conversion rates (example rates - in production, these would come from an API)
const currencyRates = {
    NGN: 1, // Base currency
    USD: 0.0007, // 1 NGN = 0.0007 USD (approximate)
    EUR: 0.0006, // 1 NGN = 0.0006 EUR (approximate)
    GBP: 0.0005, // 1 NGN = 0.0005 GBP (approximate)
    CAD: 0.0009, // 1 NGN = 0.0009 CAD (approximate)
};

const currencySymbols = {
    NGN: '₦',
    USD: '$',
    EUR: '€',
    GBP: '£',
    CAD: 'C$',
};

interface CurrencyContextType {
    selectedCurrency: string;
    currencyRates: typeof currencyRates;
    currencySymbols: typeof currencySymbols;
    setSelectedCurrency: (currency: string) => void;
    convertBalance: (balanceNGN: number) => number;
    formatBalance: (balanceNGN: number) => string;
}

const CurrencyContext = createContext<CurrencyContextType | undefined>(undefined);

interface CurrencyProviderProps {
    children: ReactNode;
}

export function CurrencyProvider({ children }: CurrencyProviderProps) {
    const [selectedCurrency, setSelectedCurrency] = useState('NGN');

    const handleCurrencyChange = (currency: string) => {
        setSelectedCurrency(currency);
        toast.success(`Currency changed to ${currency}`, {
            duration: 2000,
            description: `Balance now displayed in ${currency}`
        });
    };

    const convertBalance = (balanceNGN: number): number => {
        return balanceNGN * currencyRates[selectedCurrency as keyof typeof currencyRates];
    };

    const formatBalance = (balanceNGN: number): string => {
        const convertedBalance = convertBalance(balanceNGN);
        const symbol = currencySymbols[selectedCurrency as keyof typeof currencySymbols];
        
        return `${symbol}${convertedBalance.toLocaleString(undefined, {
            minimumFractionDigits: selectedCurrency === 'NGN' ? 0 : 2,
            maximumFractionDigits: selectedCurrency === 'NGN' ? 0 : 2
        })}`;
    };

    const value: CurrencyContextType = {
        selectedCurrency,
        currencyRates,
        currencySymbols,
        setSelectedCurrency: handleCurrencyChange,
        convertBalance,
        formatBalance,
    };

    return (
        <CurrencyContext.Provider value={value}>
            {children}
        </CurrencyContext.Provider>
    );
}

export function useCurrency() {
    const context = useContext(CurrencyContext);
    if (context === undefined) {
        throw new Error('useCurrency must be used within a CurrencyProvider');
    }
    return context;
}
