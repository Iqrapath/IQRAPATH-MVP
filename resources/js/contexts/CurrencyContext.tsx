import { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { toast } from 'sonner';
import axios from 'axios';

// Default currency symbols
const currencySymbols = {
    NGN: '₦',
    USD: '$',
};

interface CurrencyRates {
    NGN: number;
    USD: number;
    [key: string]: number;
}

interface CurrencyContextType {
    selectedCurrency: string;
    currencyRates: CurrencyRates;
    currencySymbols: typeof currencySymbols;
    setSelectedCurrency: (currency: string) => void;
    convertBalance: (balanceNGN: number) => number;
    formatBalance: (balanceNGN: number) => string;
    isLoadingRates: boolean;
    lastUpdated: Date | null;
}

const CurrencyContext = createContext<CurrencyContextType | undefined>(undefined);

interface CurrencyProviderProps {
    children: ReactNode;
}

export function CurrencyProvider({ children }: CurrencyProviderProps) {
    const [selectedCurrency, setSelectedCurrency] = useState('NGN');
    const [currencyRates, setCurrencyRates] = useState<CurrencyRates>({
        NGN: 1, // Base currency
        USD: 0.00069, // Static rate: ~1,450 NGN = 1 USD
    });
    const [isLoadingRates, setIsLoadingRates] = useState(false);
    const [lastUpdated, setLastUpdated] = useState<Date | null>(null);

    // Note: Exchange rate fetching disabled to avoid CORS issues
    // Using static fallback rates instead
    // To enable live rates, implement a backend API endpoint that fetches rates server-side

    const handleCurrencyChange = (currency: string) => {
        setSelectedCurrency(currency);
        const rate = currencyRates[currency];
        const rateDisplay = currency === 'USD' ? `1 NGN = $${rate.toFixed(6)}` : '';
        
        toast.success(`Currency changed to ${currency}`, {
            duration: 2000,
            description: rateDisplay || `Balance now displayed in ${currency}`
        });
    };

    const convertBalance = (balanceNGN: number): number => {
        return balanceNGN * currencyRates[selectedCurrency as keyof typeof currencyRates];
    };

    const formatBalance = (balanceNGN: number): string => {
        const convertedBalance = convertBalance(balanceNGN);
        const symbol = currencySymbols[selectedCurrency as keyof typeof currencySymbols];

        // NGN: No decimals, USD: 2 decimals
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
        isLoadingRates,
        lastUpdated,
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
