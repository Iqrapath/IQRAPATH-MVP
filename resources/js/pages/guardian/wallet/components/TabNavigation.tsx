import { Button } from '@/components/ui/button';

interface TabNavigationProps {
    activeTab: 'wallet' | 'payment-info' | 'payment-method' | 'payment-history';
    onTabChange: (tab: 'wallet' | 'payment-info' | 'payment-method' | 'payment-history') => void;
}

export default function TabNavigation({ activeTab, onTabChange }: TabNavigationProps) {
    return (
        <div className="flex space-x-1 bg-white p-1 rounded-lg border border-gray-200 w-fit">
            <Button
                variant={activeTab === 'wallet' ? 'default' : 'ghost'}
                onClick={() => onTabChange('wallet')}
                className={`px-6 py-2 rounded-md transition-all duration-200 ${activeTab === 'wallet'
                    ? 'bg-[#338078] text-white shadow-sm'
                    : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
                    }`}
            >
                Wallet
            </Button>
            <Button
                variant={activeTab === 'payment-info' ? 'default' : 'ghost'}
                onClick={() => onTabChange('payment-info')}
                className={`px-6 py-2 rounded-md transition-all duration-200 ${activeTab === 'payment-info'
                    ? 'bg-[#338078] text-white shadow-sm'
                    : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
                    }`}
            >
                Payment Info
            </Button>
            <Button
                variant={activeTab === 'payment-method' ? 'default' : 'ghost'}
                onClick={() => onTabChange('payment-method')}
                className={`px-6 py-2 rounded-md transition-all duration-200 ${activeTab === 'payment-method'
                    ? 'bg-[#338078] text-white shadow-sm'
                    : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
                    }`}
            >
                Payment Method
            </Button>
            <Button
                variant={activeTab === 'payment-history' ? 'default' : 'ghost'}
                onClick={() => onTabChange('payment-history')}
                className={`px-6 py-2 rounded-md transition-all duration-200 ${activeTab === 'payment-history'
                    ? 'bg-[#338078] text-white shadow-sm'
                    : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
                    }`}
            >
                Payment History
            </Button>
        </div>
    );
}

