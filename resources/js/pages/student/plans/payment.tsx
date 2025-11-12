import { Head } from '@inertiajs/react';
import StudentLayout from '@/layouts/student/student-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { CheckCircle2, CreditCard, Building2, Wallet } from 'lucide-react';
import { useState } from 'react';
import axios from 'axios';
import { toast } from 'sonner';
import { router } from '@inertiajs/react';

interface PaymentPageProps {
    subscription: any;
    plan: any;
    amount: number;
    currency: 'NGN' | 'USD';
    paymentMethods: any[];
    stripePublicKey?: string;
    paystackPublicKey?: string;
    paypalClientId?: string;
}

export default function SubscriptionPayment({
    subscription,
    plan,
    amount,
    currency,
    paymentMethods,
    stripePublicKey,
    paystackPublicKey,
}: PaymentPageProps) {
    const [selectedMethod, setSelectedMethod] = useState<'card' | 'bank_transfer'>('card');
    const [isProcessing, setIsProcessing] = useState(false);

    const formatCurrency = (value: number) => {
        const symbol = currency === 'USD' ? '$' : '₦';
        return `${symbol}${value.toLocaleString()}`;
    };

    const handlePaystackPayment = () => {
        if (!paystackPublicKey) {
            toast.error('Payment gateway not configured');
            return;
        }

        setIsProcessing(true);

        // @ts-ignore
        const handler = PaystackPop.setup({
            key: paystackPublicKey,
            email: subscription.user?.email || '',
            amount: amount * 100, // Convert to kobo
            currency: 'NGN',
            ref: `sub_${subscription.subscription_uuid}_${Date.now()}`,
            metadata: {
                subscription_id: subscription.id,
                subscription_uuid: subscription.subscription_uuid,
                plan_name: plan.name,
            },
            callback: async function(response: any) {
                try {
                    const result = await axios.post('/student/plans/payment/paystack', {
                        subscription_uuid: subscription.subscription_uuid,
                        reference: response.reference,
                    });

                    if (result.data.success) {
                        toast.success('Payment successful!');
                        window.location.href = result.data.data.redirect_url;
                    }
                } catch (error: any) {
                    toast.error(error.response?.data?.message || 'Payment verification failed');
                    setIsProcessing(false);
                }
            },
            onClose: function() {
                setIsProcessing(false);
                toast.info('Payment cancelled');
            }
        });

        handler.openIframe();
    };

    const handleStripePayment = async () => {
        toast.info('Stripe integration coming soon');
        // TODO: Implement Stripe payment
    };

    const handlePayment = () => {
        if (selectedMethod === 'card') {
            if (currency === 'NGN') {
                handlePaystackPayment();
            } else {
                handleStripePayment();
            }
        } else {
            toast.info('Bank transfer integration coming soon');
        }
    };

    return (
        <StudentLayout pageTitle="Complete Payment">
            <Head title="Complete Payment" />

            <div className="max-w-4xl mx-auto py-8 px-4">
                {/* Header */}
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900">Complete Your Payment</h1>
                    <p className="text-gray-600 mt-2">Choose your payment method to activate your subscription</p>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Payment Methods */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Payment Method Selection */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Select Payment Method</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {/* Card Payment */}
                                <button
                                    onClick={() => setSelectedMethod('card')}
                                    className={`w-full p-4 border-2 rounded-lg flex items-center gap-4 transition-all ${
                                        selectedMethod === 'card'
                                            ? 'border-[#2C7870] bg-[#2C7870]/5'
                                            : 'border-gray-200 hover:border-gray-300'
                                    }`}
                                >
                                    <div className={`w-12 h-12 rounded-full flex items-center justify-center ${
                                        selectedMethod === 'card' ? 'bg-[#2C7870] text-white' : 'bg-gray-100'
                                    }`}>
                                        <CreditCard className="w-6 h-6" />
                                    </div>
                                    <div className="flex-1 text-left">
                                        <p className="font-semibold">Credit/Debit Card</p>
                                        <p className="text-sm text-gray-600">Pay securely with your card</p>
                                    </div>
                                    {selectedMethod === 'card' && (
                                        <CheckCircle2 className="w-6 h-6 text-[#2C7870]" />
                                    )}
                                </button>

                                {/* Bank Transfer */}
                                <button
                                    onClick={() => setSelectedMethod('bank_transfer')}
                                    className={`w-full p-4 border-2 rounded-lg flex items-center gap-4 transition-all ${
                                        selectedMethod === 'bank_transfer'
                                            ? 'border-[#2C7870] bg-[#2C7870]/5'
                                            : 'border-gray-200 hover:border-gray-300'
                                    }`}
                                >
                                    <div className={`w-12 h-12 rounded-full flex items-center justify-center ${
                                        selectedMethod === 'bank_transfer' ? 'bg-[#2C7870] text-white' : 'bg-gray-100'
                                    }`}>
                                        <Building2 className="w-6 h-6" />
                                    </div>
                                    <div className="flex-1 text-left">
                                        <p className="font-semibold">Bank Transfer</p>
                                        <p className="text-sm text-gray-600">Transfer to virtual account</p>
                                    </div>
                                    {selectedMethod === 'bank_transfer' && (
                                        <CheckCircle2 className="w-6 h-6 text-[#2C7870]" />
                                    )}
                                </button>
                            </CardContent>
                        </Card>

                        {/* Payment Button */}
                        <Button
                            onClick={handlePayment}
                            disabled={isProcessing}
                            className="w-full bg-[#2C7870] hover:bg-[#236158] text-white py-6 text-lg"
                        >
                            {isProcessing ? (
                                <>
                                    <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Processing...
                                </>
                            ) : (
                                `Pay ${formatCurrency(amount)}`
                            )}
                        </Button>
                    </div>

                    {/* Order Summary */}
                    <div className="lg:col-span-1">
                        <Card>
                            <CardHeader>
                                <CardTitle>Order Summary</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <p className="text-sm text-gray-600">Plan</p>
                                    <p className="font-semibold">{plan.name}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600">Billing Cycle</p>
                                    <p className="font-semibold capitalize">{plan.billing_cycle}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600">Duration</p>
                                    <p className="font-semibold">{plan.duration_months} month(s)</p>
                                </div>
                                <div className="border-t pt-4">
                                    <div className="flex justify-between items-center">
                                        <p className="text-lg font-semibold">Total</p>
                                        <p className="text-2xl font-bold text-[#2C7870]">{formatCurrency(amount)}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Security Badge */}
                        <div className="mt-4 p-4 bg-gray-50 rounded-lg">
                            <div className="flex items-center gap-2 text-sm text-gray-600">
                                <CheckCircle2 className="w-5 h-5 text-green-600" />
                                <span>Secure payment processing</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Load Paystack Script */}
            {currency === 'NGN' && (
                <script src="https://js.paystack.co/v1/inline.js"></script>
            )}
        </StudentLayout>
    );
}
