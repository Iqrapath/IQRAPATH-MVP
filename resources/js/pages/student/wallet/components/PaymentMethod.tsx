import React, { useState, useEffect } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Plus, Trash2, Check } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import { toast } from 'sonner';
import axios from 'axios';
import AddPaymentMethodModal from './AddPaymentMethodModal';

interface PaymentMethod {
    id: number;
    type: 'bank_transfer' | 'mobile_money';
    name: string;
    details: {
        bank_name?: string;
        account_holder?: string;
        account_number?: string;
        provider?: string;
        phone_number?: string;
    } | null;
    is_default: boolean;
    is_active: boolean;
    is_verified: boolean;
    created_at: string;
}

interface PaymentMethodProps {
    onPaymentMethodsUpdated?: () => void;
}

export default function PaymentMethod({ onPaymentMethodsUpdated }: PaymentMethodProps) {
    const pageProps = usePage().props as any;
    const [paymentMethods, setPaymentMethods] = useState<PaymentMethod[]>([]);
    const [loading, setLoading] = useState(false);
    const [showAddModal, setShowAddModal] = useState(false);
    const [deletingId, setDeletingId] = useState<number | null>(null);

    // Fetch payment methods
    const fetchPaymentMethods = async () => {
        setLoading(true);
        try {
            const response = await axios.get('/student/wallet/payment-methods');
            if (response.data.payment_methods) {
                setPaymentMethods(response.data.payment_methods);
            }
        } catch (error) {
            console.error('Error fetching payment methods:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (pageProps.paymentMethods) {
            setPaymentMethods(pageProps.paymentMethods);
        }
        fetchPaymentMethods();
    }, [pageProps.paymentMethods]);

    const handleAddPaymentMethod = () => {
        setShowAddModal(true);
    };

    const handlePaymentMethodAdded = () => {
        fetchPaymentMethods();
        if (onPaymentMethodsUpdated) {
            onPaymentMethodsUpdated();
        }
    };

    const handleSetDefault = async (methodId: number) => {
        try {
            await axios.put(`/student/wallet/payment-methods/${methodId}`, {
                is_default: true,
            });

            toast.success('Default payment method updated');
            fetchPaymentMethods();
            if (onPaymentMethodsUpdated) {
                onPaymentMethodsUpdated();
            }
        } catch (error: any) {
            console.error('Error setting default:', error);
            toast.error('Failed to update default payment method');
        }
    };

    const handleDelete = async (methodId: number) => {
        if (!confirm('Are you sure you want to delete this payment method?')) {
            return;
        }

        setDeletingId(methodId);
        try {
            await axios.delete(`/student/wallet/payment-methods/${methodId}`);

            toast.success('Payment method deleted');
            fetchPaymentMethods();
            if (onPaymentMethodsUpdated) {
                onPaymentMethodsUpdated();
            }
        } catch (error: any) {
            console.error('Error deleting payment method:', error);
            toast.error('Failed to delete payment method');
        } finally {
            setDeletingId(null);
        }
    };

    const getPaymentMethodIcon = (type: string) => {
        switch (type) {
            case 'bank_transfer':
                return '🏦';
            case 'mobile_money':
                return '📱';
            default:
                return '💳';
        }
    };

    const getPaymentMethodDisplay = (method: PaymentMethod) => {
        if (method.type === 'bank_transfer' && method.details) {
            return (
                <div>
                    <div className="font-medium">{method.details.bank_name}</div>
                    <div className="text-sm text-gray-600">
                        {method.details.account_holder} - {method.details.account_number}
                    </div>
                </div>
            );
        }
        if (method.type === 'mobile_money' && method.details) {
            return (
                <div>
                    <div className="font-medium">{method.details.provider}</div>
                    <div className="text-sm text-gray-600">{method.details.phone_number}</div>
                </div>
            );
        }
        return <div className="font-medium">{method.name}</div>;
    };

    return (
        <div className="space-y-6">
            <Card className="bg-white border border-gray-200">
                <CardContent className="p-6">
                    <div className="flex items-center justify-between mb-6">
                        <div>
                            <h2 className="text-xl font-bold text-gray-900">Manage Payment Methods</h2>
                            <p className="text-sm text-gray-600 mt-1">
                                Add and manage your payment methods for wallet top-ups
                            </p>
                        </div>
                        <Button
                            onClick={handleAddPaymentMethod}
                            className="bg-[#2C7870] hover:bg-[#235f59] text-white"
                        >
                            <Plus className="w-4 h-4 mr-2" />
                            Add Payment Method
                        </Button>
                    </div>

                    {loading ? (
                        <div className="text-center py-8 text-gray-500">
                            Loading payment methods...
                        </div>
                    ) : paymentMethods.length === 0 ? (
                        <div className="text-center py-12">
                            <div className="text-6xl mb-4">💳</div>
                            <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                No Payment Methods Yet
                            </h3>
                            <p className="text-gray-600 mb-6">
                                Add a payment method to easily fund your wallet
                            </p>
                            <Button
                                onClick={handleAddPaymentMethod}
                                className="bg-[#2C7870] hover:bg-[#235f59] text-white"
                            >
                                <Plus className="w-4 h-4 mr-2" />
                                Add Your First Payment Method
                            </Button>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {paymentMethods.map((method) => (
                                <div
                                    key={method.id}
                                    className="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200"
                                >
                                    <div className="flex items-center gap-4 flex-1">
                                        <div className="text-3xl">
                                            {getPaymentMethodIcon(method.type)}
                                        </div>
                                        <div className="flex-1">
                                            <div className="flex items-center gap-2 mb-1">
                                                <span className="font-semibold text-gray-900">{method.name}</span>
                                                {method.is_default && (
                                                    <span className="inline-flex items-center gap-1 text-xs bg-[#2C7870] text-white px-2 py-0.5 rounded">
                                                        <Check className="w-3 h-3" />
                                                        Default
                                                    </span>
                                                )}
                                                {method.is_verified ? (
                                                    <span className="inline-flex items-center gap-1 text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded">
                                                        <Check className="w-3 h-3" />
                                                        Verified
                                                    </span>
                                                ) : (
                                                    <span className="text-xs bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded">
                                                        Pending
                                                    </span>
                                                )}
                                            </div>
                                            {getPaymentMethodDisplay(method)}
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {!method.is_default && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleSetDefault(method.id)}
                                            >
                                                Set as Default
                                            </Button>
                                        )}
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => handleDelete(method.id)}
                                            disabled={deletingId === method.id}
                                            className="text-red-600 hover:text-red-700 hover:bg-red-50"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    <div className="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <p className="text-sm text-blue-900">
                            <strong>Supported Payment Methods:</strong>
                        </p>
                        <ul className="text-sm text-blue-800 mt-2 space-y-1">
                            <li>• Bank Transfer (Nigerian banks)</li>
                            <li>• Mobile Money (MTN, Airtel, Glo, 9mobile)</li>
                        </ul>
                    </div>
                </CardContent>
            </Card>

            {/* Add Payment Method Modal */}
            {showAddModal && (
                <AddPaymentMethodModal
                    isOpen={showAddModal}
                    onClose={() => setShowAddModal(false)}
                    onSuccess={handlePaymentMethodAdded}
                />
            )}
        </div>
    );
}
