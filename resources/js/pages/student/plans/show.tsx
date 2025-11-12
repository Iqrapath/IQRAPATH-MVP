/**
 * 🎨 FIGMA DESIGN REFERENCE
 * 
 * Component: Student Plan Enrollment Page
 * Figma URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAQUEST?node-id=1472-76198
 * 
 * 📏 EXACT SPECIFICATIONS FROM IMAGE:
 * - Light teal header with rounded top corners
 * - Plan details with key-value pairs
 * - What's Included section with green checkmarks
 * - Currency selector (USD/NGN radio buttons)
 * - Student Details form section
 * - Payment Method selection with radio buttons
 * - Bank account display with change option
 * - Go Back and Enroll Now buttons at bottom
 * 
 * 📱 RESPONSIVE: Mobile-first approach
 * 🎯 STATES: Form validation, payment method selection
 */

import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import StudentLayout from '@/layouts/student/student-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import { CheckCircle2, ChevronRight } from 'lucide-react';
import { SubscriptionPlan, Subscription, User } from '@/types';
import axios from 'axios';
import { toast } from 'sonner';
import SubscriptionPaymentModal from '@/components/student/SubscriptionPaymentModal';
import { useCurrency } from '@/contexts/CurrencyContext';

interface PaymentMethod {
    id: number;
    type: string;
    name: string;
    details: any;
    is_default: boolean;
    is_active: boolean;
    // Bank transfer fields
    bank_name?: string;
    bank_code?: string;
    account_name?: string;
    account_number?: string | null;
    last_four?: string;
    // Card fields
    card_brand?: string;
    exp_month?: string;
    exp_year?: string;
}

interface PlanShowProps {
    plan: SubscriptionPlan;
    activePlan?: Subscription;
    walletBalance: {
        usd: number;
        ngn: number;
    };
    defaultPaymentMethod?: PaymentMethod | null;
    user: User & {
        studentProfile?: {
            grade_level?: string;
            age_group?: string;
            preferred_learning_times?: string[] | string | null;
            learning_goals?: string;
            additional_notes?: string;
        };
    };
}

export default function PlanShow({
    plan,
    activePlan,
    walletBalance,
    defaultPaymentMethod,
    user
}: PlanShowProps) {
    // Use global currency context
    const { selectedCurrency: globalCurrency } = useCurrency();
    const selectedCurrency = (globalCurrency === 'NGN' || globalCurrency === 'USD') ? globalCurrency : 'NGN';
    
    const [isEnrolling, setIsEnrolling] = useState(false);
    const [showPaymentModal, setShowPaymentModal] = useState(false);
    const [pendingSubscription, setPendingSubscription] = useState<any>(null);

    // Set payment method based on user's default or fallback to wallet
    const [paymentMethod, setPaymentMethod] = useState<string>(
        defaultPaymentMethod?.type || 'wallet'
    );
    const [studentName, setStudentName] = useState(user.name || '');

    // Get preferred learning times and format it
    let preferredTimeDisplay = '';
    const preferredTimesData = user.studentProfile?.preferred_learning_times;

    if (preferredTimesData) {
        try {
            // Parse if it's a JSON string
            const timesArray = typeof preferredTimesData === 'string'
                ? JSON.parse(preferredTimesData)
                : preferredTimesData;

            if (Array.isArray(timesArray) && timesArray.length > 0) {
                preferredTimeDisplay = timesArray.join(', ');
            }
        } catch (e) {
            // If parsing fails, use the string as is
            preferredTimeDisplay = String(preferredTimesData);
        }
    }

    const [preferredTime, setPreferredTime] = useState(preferredTimeDisplay);
    const [ageGroup, setAgeGroup] = useState(user.studentProfile?.age_group || user.studentProfile?.grade_level || '');
    const [notes, setNotes] = useState(user.studentProfile?.learning_goals || '');

    const formatPrice = (currency: 'USD' | 'NGN') => {
        const price = currency === 'USD' ? plan.price_dollar : plan.price_naira;
        const symbol = currency === 'USD' ? '$' : '₦';
        return `${symbol}${price.toLocaleString()}`;
    };

    const handleEnrollNow = async () => {
        if (activePlan || isEnrolling) {
            return;
        }

        setIsEnrolling(true);

        // Prepare enrollment data
        const enrollmentData = {
            plan_id: plan.id,
            currency: selectedCurrency,
            payment_method: paymentMethod,
            auto_renew: false,
            student_details: {
                name: studentName,
                preferred_time: preferredTime,
                age_group: ageGroup,
                notes: notes,
            }
        };

        try {
            // Use axios for API call since backend returns JSON
            const response = await axios.post('/student/plans/enroll', enrollmentData);

            if (response.data.success) {
                const subscription = response.data.data.subscription;
                
                // Check payment method
                if (paymentMethod === 'wallet') {
                    // Wallet payment successful, redirect to dashboard
                    toast.success('Subscription activated successfully!');
                    router.visit('/student/dashboard');
                } else {
                    // Non-wallet payment - show payment modal
                    setPendingSubscription(subscription);
                    setShowPaymentModal(true);
                    setIsEnrolling(false);
                }
            }
        } catch (error: any) {
            console.error('Enrollment failed:', error);

            if (error.response?.data) {
                const errorData = error.response.data;
                toast.error(errorData.message || 'Enrollment failed. Please try again.');

                // Handle specific error codes
                if (errorData.code === 'INSUFFICIENT_FUNDS' && errorData.data) {
                    toast.error(`Insufficient balance. You need ₦${Number(errorData.data.shortfall).toLocaleString()} more.`);
                }
            } else {
                toast.error('An error occurred. Please try again.');
            }

            setIsEnrolling(false);
        }
    };

    return (
        <StudentLayout pageTitle="Plan Enrollment">
            <Head title={`Enroll in ${plan.name}`} />

            <div className="max-w-2xl mx-auto py-8 px-4 space-y-8">
                {/* Header Card */}
                <div className="bg-[#E8F5F4] rounded-t-3xl p-6">
                    <h1 className="text-2xl font-bold text-[#2C7870] mb-2">
                        Plan Enrollment
                    </h1>
                    <p className="text-xl text-[#2C7870]">
                        {formatPrice(selectedCurrency)}/{plan.billing_cycle}
                    </p>
                </div>

                {/* Plan Details Section */}
                <div className="space-y-4">
                    <div className="flex items-center">
                        <span className="text-[#4F4F4F] font-medium">Plan Selected:</span>
                        <span className="text-[#212121] ml-4">{plan.name}</span>
                    </div>
                    <div className="flex items-center">
                        <span className="text-[#4F4F4F] font-medium">Price:</span>
                        <span className="text-[#212121] ml-4">{formatPrice(selectedCurrency)}/{plan.billing_cycle}</span>
                    </div>
                    <div className="flex items-center">
                        <span className="text-[#4F4F4F] font-medium">Billing:</span>
                        <span className="text-[#212121] ml-4 capitalize">{plan.billing_cycle}</span>
                    </div>
                </div>

                {/* What's Included Section */}
                <div>
                    <h2 className="text-lg font-semibold text-[#212121] mb-4">
                        What's Included
                    </h2>
                    <div className="space-y-3">
                        {plan.features && plan.features.length > 0 ? (
                            // Display features from database
                            plan.features.map((feature, index) => (
                                <div key={index} className="flex items-start space-x-3">
                                    <CheckCircle2 className="h-5 w-5 text-[#10B981] mt-0.5 flex-shrink-0" />
                                    <span className="text-[#4F4F4F]">{feature}</span>
                                </div>
                            ))
                        ) : (
                            // Fallback to default features if none in database
                            <>
                                <div className="flex items-start space-x-3">
                                    <CheckCircle2 className="h-5 w-5 text-[#10B981] mt-0.5 flex-shrink-0" />
                                    <span className="text-[#4F4F4F]">Daily live sessions with certified teacher</span>
                                </div>
                                <div className="flex items-start space-x-3">
                                    <CheckCircle2 className="h-5 w-5 text-[#10B981] mt-0.5 flex-shrink-0" />
                                    <span className="text-[#4F4F4F]">Certificate of completion</span>
                                </div>
                                <div className="flex items-start space-x-3">
                                    <CheckCircle2 className="h-5 w-5 text-[#10B981] mt-0.5 flex-shrink-0" />
                                    <span className="text-[#4F4F4F]">Weekly tests & progress tracking</span>
                                </div>
                                <div className="flex items-start space-x-3">
                                    <CheckCircle2 className="h-5 w-5 text-[#10B981] mt-0.5 flex-shrink-0" />
                                    <span className="text-[#4F4F4F]">24/7 support</span>
                                </div>
                                <div className="flex items-start space-x-3">
                                    <CheckCircle2 className="h-5 w-5 text-[#10B981] mt-0.5 flex-shrink-0" />
                                    <span className="text-[#4F4F4F]">Flexible schedule</span>
                                </div>
                            </>
                        )}
                    </div>
                </div>

                {/* Currency Info */}
                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p className="text-sm text-blue-900">
                        <strong>Currency:</strong> {selectedCurrency} - You can change your currency from the header or sidebar.
                    </p>
                </div>

                {/* Student Details */}
                <div>
                    <h2 className="text-lg font-semibold text-[#212121] mb-4">
                        Student Details
                    </h2>
                    <div className="space-y-4">
                        <div className="flex items-center">
                            <span className="text-[#4F4F4F] font-medium">Name:</span>
                            <span className="text-[#212121] ml-4">{studentName}</span>
                        </div>
                        <div className="flex items-center">
                            <span className="text-[#4F4F4F] font-medium">Preferred Time:</span>
                            <span className="text-[#212121] ml-4">{preferredTime}</span>
                        </div>
                        <div className="flex items-center">
                            <span className="text-[#4F4F4F] font-medium">Age Group:</span>
                            <span className="text-[#212121] ml-4">{ageGroup}</span>
                        </div>
                        <div>
                            <span className="text-[#4F4F4F] font-medium block mb-2">Notes:</span>
                            <div className="bg-[#F8F8F8] rounded-xl p-4">
                                <span className="text-[#4F4F4F] text-sm ">
                                    {notes || 'Student has completed Juz\' Amma already.'}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Payment Method */}
                <div>
                    <h2 className="text-lg font-semibold text-[#212121] mb-4">
                        Choose Payment Method:
                    </h2>
                    <RadioGroup value={paymentMethod} onValueChange={setPaymentMethod}>
                        <div className="space-y-4">
                            <div className="flex items-center space-x-3">
                                <RadioGroupItem value="wallet" id="wallet" />
                                <Label htmlFor="wallet" className="text-[#4F4F4F] cursor-pointer">My Wallet</Label>
                            </div>
                            <div className="flex items-center space-x-3">
                                <RadioGroupItem value="bank_transfer" id="bank" />
                                <Label htmlFor="bank" className="text-[#4F4F4F] cursor-pointer">Bank Transfer</Label>
                            </div>
                            <div className="flex items-center space-x-3">
                                <RadioGroupItem value="card" id="card" />
                                <Label htmlFor="card" className="text-[#4F4F4F] cursor-pointer">Credit/Debit Card</Label>
                            </div>
                            <div className="flex items-center space-x-3">
                                <RadioGroupItem value="paypal" id="paypal" />
                                <Label htmlFor="paypal" className="text-[#4F4F4F] cursor-pointer">PayPal</Label>
                            </div>
                        </div>
                    </RadioGroup>

                    {/* Payment Method Display - Only show if user has saved payment method */}
                    {defaultPaymentMethod && (
                        <div className="mt-4 bg-[#FFFFFF] rounded-[23px] p-4">
                            <div className="flex items-start justify-between">
                                <div className="flex items-start space-x-3">
                                    <CheckCircle2 className="h-5 w-5 text-[#10B981] mt-0.5 flex-shrink-0" />
                                    <div className="space-y-1">
                                        <p className="text-sm font-semibold text-[#212121]">
                                            1. {defaultPaymentMethod.type === 'bank_transfer' ? 'Bank Transfer' : defaultPaymentMethod.name}
                                        </p>
                                        {defaultPaymentMethod.type === 'bank_transfer' && (
                                            <>
                                                <p className="text-sm font-medium text-[#212121]">
                                                    {defaultPaymentMethod.bank_name || 'Bank Name'}
                                                </p>
                                                <p className="text-sm text-[#6B7280]">
                                                    {defaultPaymentMethod.account_name || 'Account Name'} | {defaultPaymentMethod.account_number || (defaultPaymentMethod.last_four ? '****' + defaultPaymentMethod.last_four : 'Account Number')}
                                                </p>
                                            </>
                                        )}
                                        {defaultPaymentMethod.type === 'card' && (
                                            <>
                                                <p className="text-sm font-medium text-[#212121]">
                                                    {defaultPaymentMethod.card_brand || 'Card'} •••• {defaultPaymentMethod.last_four || '****'}
                                                </p>
                                                <p className="text-sm text-[#6B7280]">
                                                    Expires {defaultPaymentMethod.exp_month}/{defaultPaymentMethod.exp_year}
                                                </p>
                                            </>
                                        )}
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    className="text-[#2C7870] text-sm font-medium flex items-center gap-1 hover:text-[#236158] transition-colors"
                                    onClick={() => {
                                        // TODO: Open payment method selector modal
                                        console.log('Change payment method clicked');
                                    }}
                                >
                                    Change
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M6 12L10 8L6 4" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    )}
                </div>

                {/* Action Buttons */}
                <div className="flex gap-4 justify-end">
                    <Button
                        variant="outline"
                        size="lg"
                        asChild
                        className="px-8 border-gray-300 text-gray-700 hover:bg-gray-50 rounded-full"
                    >
                        <Link href="/student/plans">
                            Go Back
                        </Link>
                    </Button>
                    <Button
                        size="lg"
                        onClick={handleEnrollNow}
                        disabled={!!activePlan || isEnrolling}
                        className="px-8 bg-[#2C7870] hover:bg-[#236158] text-white rounded-full disabled:opacity-50 cursor-pointer disabled:cursor-not-allowed"
                    >
                        {isEnrolling ? (
                            <>
                                <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Processing...
                            </>
                        ) : activePlan ? 'Already Enrolled' : 'Enrol Now'}
                    </Button>
                </div>

                {/* Active Subscription Warning */}
                {activePlan && (
                    <Card className="border-amber-200 bg-amber-50">
                        <CardContent className="p-4">
                            <p className="text-sm text-amber-800 text-center">
                                You already have an active subscription. Please wait for it to expire before enrolling in a new plan.
                            </p>
                        </CardContent>
                    </Card>
                )}
            </div>

            {/* Subscription Payment Modal */}
            {showPaymentModal && pendingSubscription && (
                <SubscriptionPaymentModal
                    isOpen={showPaymentModal}
                    onClose={() => {
                        setShowPaymentModal(false);
                        setPendingSubscription(null);
                    }}
                    subscription={pendingSubscription}
                    plan={plan}
                    amount={selectedCurrency === 'USD' ? plan.price_dollar : plan.price_naira}
                    currency={selectedCurrency}
                    paymentMethod={paymentMethod}
                    user={user}
                    onSuccess={() => {
                        setShowPaymentModal(false);
                        toast.success('Payment successful! Subscription activated.');
                        router.visit('/student/dashboard');
                    }}
                />
            )}
        </StudentLayout>
    );
}