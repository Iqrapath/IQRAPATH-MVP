import { Head, Link } from '@inertiajs/react';
import StudentLayout from '@/layouts/student/student-layout';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { CheckCircle2 } from 'lucide-react';

interface PaymentSuccessProps {
    subscription: any;
    plan: any;
    transaction?: any;
}

export default function PaymentSuccess({ subscription, plan, transaction }: PaymentSuccessProps) {
    return (
        <StudentLayout pageTitle="Payment Successful">
            <Head title="Payment Successful" />

            <div className="max-w-2xl mx-auto py-12 px-4">
                <Card className="text-center">
                    <CardContent className="pt-12 pb-8">
                        {/* Success Icon */}
                        <div className="flex justify-center mb-6">
                            <div className="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center">
                                <CheckCircle2 className="w-12 h-12 text-green-600" />
                            </div>
                        </div>

                        {/* Success Message */}
                        <h1 className="text-3xl font-bold text-gray-900 mb-4">
                            Payment Successful!
                        </h1>
                        <p className="text-lg text-gray-600 mb-8">
                            Your subscription has been activated successfully
                        </p>

                        {/* Subscription Details */}
                        <div className="bg-gray-50 rounded-lg p-6 mb-8 text-left">
                            <h2 className="font-semibold text-lg mb-4">Subscription Details</h2>
                            <div className="space-y-3">
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Plan:</span>
                                    <span className="font-semibold">{plan.name}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Billing Cycle:</span>
                                    <span className="font-semibold capitalize">{plan.billing_cycle}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Status:</span>
                                    <span className="font-semibold text-green-600">Active</span>
                                </div>
                                {transaction && (
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Transaction ID:</span>
                                        <span className="font-mono text-sm">{transaction.transaction_uuid}</span>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="flex flex-col sm:flex-row gap-4 justify-center">
                            <Button asChild className="bg-[#2C7870] hover:bg-[#236158]">
                                <Link href="/student/dashboard">
                                    Go to Dashboard
                                </Link>
                            </Button>
                            <Button asChild variant="outline">
                                <Link href="/student/plans">
                                    View Plans
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </StudentLayout>
    );
}
