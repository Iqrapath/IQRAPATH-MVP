import React, { useState, useEffect } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Check, Plus, ChevronRight } from 'lucide-react';
import { usePage } from '@inertiajs/react';

interface PaymentMethod {
    id: number;
    type: 'bank_transfer' | 'mobile_money' | 'card' | 'paypal';
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
    verification_status: 'pending' | 'verified' | 'failed';
    created_at: string;
    updated_at: string;
}

export default function PaymentInfo() {
    const pageProps = usePage().props as any;
    const [paymentMethods, setPaymentMethods] = useState<PaymentMethod[]>([]);
    const [loading, setLoading] = useState(false);

    // Fetch payment methods from API
    const fetchPaymentMethods = async () => {
        setLoading(true);
        try {
            const response = await fetch('/student/wallet/payment-methods', {
                headers: {
                    'Accept': 'application/json',
                }
            });
            const data = await response.json();
            if (data.payment_methods && Array.isArray(data.payment_methods)) {
                setPaymentMethods(data.payment_methods);
            }
        } catch (error) {
            console.error('Error fetching payment methods:', error);
        } finally {
            setLoading(false);
        }
    };

    // Initial load from props, then fetch from API
    useEffect(() => {
        if (pageProps.paymentMethods) {
            setPaymentMethods(pageProps.paymentMethods);
        }
        // Also fetch fresh data
        fetchPaymentMethods();
    }, [pageProps.paymentMethods]);

    // Check if user has any payment methods
    const hasPaymentMethods = paymentMethods.length > 0;

    const getPaymentMethodIcon = (type: string) => {
        switch (type) {
            case 'bank_transfer':
                return '🏦';
            case 'mobile_money':
                return '📱';
            case 'card':
                return '💳';
            case 'paypal':
                return '💰';
            default:
                return '💳';
        }
    };

    const getPaymentMethodDisplay = (method: PaymentMethod) => {
        if (method.type === 'bank_transfer' && method.details) {
            return `${method.details.bank_name} - ${method.details.account_number?.slice(-4)}`;
        }
        if (method.type === 'mobile_money' && method.details) {
            return `${method.details.provider} - ${method.details.phone_number}`;
        }
        return method.name;
    };

    return (
        <div className="space-y-6">
            <Card className="bg-white border border-gray-200">
                <CardContent className="p-6">
                    <div className="flex items-center justify-between mb-6">
                        <h2 className="text-xl font-bold text-gray-900">Payment Methods</h2>
                        <Button
                            onClick={() => window.location.href = '/student/wallet?tab=payment-method'}
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
                    ) : !hasPaymentMethods ? (
                        <div className="text-center py-12">
                            <div className="text-6xl mb-4">💳</div>
                            <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                No Payment Methods Yet
                            </h3>
                            <p className="text-gray-600 mb-6">
                                Add a payment method to easily fund your wallet and pay for classes
                            </p>
                            <Button
                                onClick={() => window.location.href = '/student/wallet?tab=payment-method'}
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
                                    className="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200 hover:border-[#2C7870] transition-colors"
                                >
                                    <div className="flex items-center gap-4">
                                        <div className="text-3xl">
                                            {getPaymentMethodIcon(method.type)}
                                        </div>
                                        <div>
                                            <div className="font-semibold text-gray-900">
                                                {method.name}
                                            </div>
                                            <div className="text-sm text-gray-600">
                                                {getPaymentMethodDisplay(method)}
                                            </div>
                                            <div className="flex items-center gap-2 mt-1">
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
                                                        Pending Verification
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => window.location.href = '/student/wallet?tab=payment-method'}
                                    >
                                        <ChevronRight className="w-5 h-5 text-gray-400" />
                                    </Button>
                                </div>
                            ))}
                        </div>
                    )}

                    {hasPaymentMethods && (
                        <div className="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <p className="text-sm text-blue-900">
                                <strong>Note:</strong> Your payment methods are securely stored and can be used for quick wallet top-ups and automatic payments.
                            </p>
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
