// import React from 'react';

// interface TabNavigationProps {
//     activeTab: 'wallet' | 'payment-info' | 'payment-method';
//     onTabChange: (tab: 'wallet' | 'payment-info' | 'payment-method') => void;
// }

// export default function TabNavigation({ activeTab, onTabChange }: TabNavigationProps) {
//     const tabs = [
//         { id: 'wallet' as const, label: 'Wallet' },
//         { id: 'payment-info' as const, label: 'Payment Info' },
//         { id: 'payment-method' as const, label: 'Payment Method' },
//     ];

//     return (
//         <div className="flex gap-2 border-b border-gray-200">
//             {tabs.map((tab) => (
//                 <button
//                     key={tab.id}
//                     onClick={() => onTabChange(tab.id)}
//                     className={`
//                         px-6 py-3 text-sm font-medium rounded-t-lg transition-colors
//                         ${activeTab === tab.id
//                             ? 'bg-[#2C7870] text-white'
//                             : 'bg-transparent text-gray-600 hover:text-gray-900 hover:bg-gray-50'
//                         }
//                     `}
//                 >
//                     {tab.label}
//                 </button>
//             ))}
//         </div>
//     );
// }

import { Button } from '@/components/ui/button';

interface TabNavigationProps {
    activeTab: 'wallet' | 'payment-info' | 'payment-method';
    onTabChange: (tab: 'wallet' | 'payment-info' | 'payment-method') => void;
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
        </div>
    );
}

