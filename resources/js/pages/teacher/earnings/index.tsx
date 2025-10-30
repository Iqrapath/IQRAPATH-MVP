import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import TeacherLayout from '@/layouts/teacher/teacher-layout';
import TabNavigation from './components/TabNavigation';
import Earnings from './components/Earnings';
import PaymentInfo from './components/PaymentInfo';
import PaymentMethod from './components/PaymentMethod';

export default function TeacherEarnings() {
    const [activeTab, setActiveTab] = useState<'earnings' | 'payment-info' | 'payment-method'>('earnings');
    const [refreshTrigger, setRefreshTrigger] = useState(0);

    const handlePaymentMethodsUpdated = () => {
        // Trigger refresh for PaymentInfo component
        setRefreshTrigger(prev => prev + 1);
    };

    return (
        <TeacherLayout pageTitle="Earnings & Wallet" showRightSidebar={true}>
            <Head title="Earnings & Wallet" />
            
            <div className="space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Earnings & Wallet</h1>
                </div>

                {/* Tab Navigation */}
                <TabNavigation 
                    activeTab={activeTab} 
                    onTabChange={setActiveTab} 
                />

                {/* Tab Content */}
                {activeTab === 'earnings' && <Earnings />}
                {activeTab === 'payment-info' && <PaymentInfo key={refreshTrigger} />}
                {activeTab === 'payment-method' && <PaymentMethod onPaymentMethodsUpdated={handlePaymentMethodsUpdated} />}
            </div>
        </TeacherLayout>
    );
}
