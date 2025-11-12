import { Head, Link } from '@inertiajs/react';
import StudentLayout from '@/layouts/student/student-layout';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { XCircle } from 'lucide-react';

interface PaymentFailedProps {
    subscription: any;
    plan: any;
    retryUrl: string;
    error?: string;
}

export default function PaymentFailed({ subscription, plan, retryUrl, error }: PaymentFailedProps) {
    return (
        <StudentLayout pageTitle="Payment Failed">
            <Head title="Payment Failed" />

            <div className="max-w-2xl mx-auto py-12 px-4">
                <Card className="text-center">
                    <CardContent className="pt-12 pb-8">
                        {/* Error Icon */}
                        <div className="flex justify-center mb-6">
                            <div className="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center">
                                <XCircle className="w-12 h-12 text-red-600" />
                            </div>
                        </div>

                        {/* Error Message */}
                        <h1 className="text-3xl font-bold text-gray-900 mb-4">
                            Payment Failed
                        </h1>
                        <p className="text-lg text-gray-600 mb-8">
                            {error || 'We couldn\'t process your payment. Please try again.'}
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
                                    <span className="font-semibold text-red-600">Payment Failed</span>
                                </div>
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="flex flex-col sm:flex-row gap-4 justify-center">
                            <Button asChild className="bg-[#2C7870] hover:bg-[#236158]">
                                <Link href={retryUrl}>
                                    Try Again
                                </Link>
                            </Button>
                            <Button asChild variant="outline">
                                <Link href="/student/plans">
                                    Choose Different Plan
                                </Link>
                            </Button>
                        </div>

                        {/* Support */}
                        <div className="mt-8 text-sm text-gray-600">
                            <p>Need help? <a href="/support" className="text-[#2C7870] hover:underline">Contact Support</a></p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </StudentLayout>
    );
}
