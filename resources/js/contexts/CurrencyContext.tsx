import React, { createContext, useContext, useState, ReactNode } from 'react';
import { toast } from 'sonner';

// Currency conversion rates (example rates - in production, these would come from an API)
const currencyRates = {
    NGN: 1, // Base currency
    USD: 0.0007, // 1 NGN = 0.0007 USD (approximate rate: ~1,400 NGN = 1 USD)
};

const currencySymbols = {
    NGN: '₦',
    USD: '$',
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
