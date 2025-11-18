import { useState } from 'react';
import { Head } from '@inertiajs/react';
import GuardianLayout from '@/layouts/guardian/guardian-layout';
import {
    TabNavigation,
    WalletBalance,
    PaymentInfo,
    PaymentMethod,
    PaymentHistory,
} from './components';

interface GuardianWalletProps {
    walletBalance: number;
    totalSpentOnChildren: number;
    totalRefunded: number;
    familySummary: any;
    upcomingPayments: any[];
    recentTransactions: any[];
    walletSettings: any;
}

export default function GuardianWallet(props: GuardianWalletProps) {
    const [activeTab, setActiveTab] = useState<'wallet' | 'payment-info' | 'payment-method' | 'payment-history'>('wallet');
    const [refreshTrigger, setRefreshTrigger] = useState(0);

    const handlePaymentMethodsUpdated = () => {
        // Trigger refresh for PaymentInfo component
        setRefreshTrigger(prev => prev + 1);
    };

    return (
        <GuardianLayout pageTitle="Payments & Wallet" showRightSidebar={true}>
            <Head title="Payments & Wallet" />

            <div className="space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Payments & Wallet</h1>
                </div>

                {/* Tab Navigation */}
                <TabNavigation
                    activeTab={activeTab}
                    onTabChange={setActiveTab}
                />

                {/* Tab Content */}
                {activeTab === 'wallet' && <WalletBalance onViewHistory={() => setActiveTab('payment-history')} />}
                {activeTab === 'payment-info' && <PaymentInfo key={refreshTrigger} />}
                {activeTab === 'payment-method' && <PaymentMethod onPaymentMethodsUpdated={handlePaymentMethodsUpdated} />}
                {activeTab === 'payment-history' && <PaymentHistory />}
            </div>
        </GuardianLayout>
    );
}

