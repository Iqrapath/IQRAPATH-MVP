import { useState } from 'react';
import { Head } from '@inertiajs/react';
import StudentLayout from '@/layouts/student/student-layout';
import {
    TabNavigation,
    WalletBalance,
    PaymentInfo,
    PaymentMethod,
} from './components';

export default function StudentWallet() {
    const [activeTab, setActiveTab] = useState<'wallet' | 'payment-info' | 'payment-method'>('wallet');
    const [refreshTrigger, setRefreshTrigger] = useState(0);

    const handlePaymentMethodsUpdated = () => {
        // Trigger refresh for PaymentInfo component
        setRefreshTrigger(prev => prev + 1);
    };

    return (
        <StudentLayout pageTitle="Payments & Wallet" showRightSidebar={true}>
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
                {activeTab === 'wallet' && <WalletBalance />}
                {activeTab === 'payment-info' && <PaymentInfo key={refreshTrigger} />}
                {activeTab === 'payment-method' && <PaymentMethod onPaymentMethodsUpdated={handlePaymentMethodsUpdated} />}
            </div>
        </StudentLayout>
    );
}
