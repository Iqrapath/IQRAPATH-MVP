/**
 * 🎨 FIGMA REFERENCE
 * Reschedule Pricing Payment page
 * 
 * EXACT SPECS FROM FIGMA:
 * - Pricing breakdown with original vs new dates
 * - Payment method selection (wallet/card)
 * - Terms and conditions checkbox
 * - Submit reschedule button
 * - Clean layout with proper spacing
 */

import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import GuardianLayout from '@/layouts/guardian/guardian-layout';
import { Head } from '@inertiajs/react';

interface PricingData {
    teacher_name: string;
    subject: string;
    original_date: string;
    original_time: string;
    new_dates: string[];
    hourly_rate_ngn: number;
    hourly_rate_usd: number;
    duration_hours: number;
    total_amount_ngn: number;
    total_amount_usd: number;
    sessions_count: number;
}

interface ReschedulePricingPaymentPageProps {
    pricing: PricingData;
    reschedule_data: {
        booking_id: number;
        teacher_id: number;
        dates: string[];
        availability_ids: number[];
        subjects: string[];
        note_to_teacher: string;
    };
}

export default function ReschedulePricingPaymentPage({ 
    pricing, 
    reschedule_data 
}: ReschedulePricingPaymentPageProps) {
    const [paymentMethod, setPaymentMethod] = useState<'wallet' | 'card'>('wallet');
    const [agreeTerms, setAgreeTerms] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = async () => {
        if (!agreeTerms) {
            alert('Please agree to the terms and conditions');
            return;
        }

        setIsSubmitting(true);

        try {
            await router.post('/guardian/reschedule/pricing-payment', {
                payment_method: paymentMethod,
                agree_terms: agreeTerms,
            });
        } catch (error) {
            console.error('Reschedule submission error:', error);
            setIsSubmitting(false);
        }
    };

    const handleGoBack = () => {
        router.visit(`/guardian/reschedule/session-details?booking_id=${reschedule_data.booking_id}&teacher_id=${reschedule_data.teacher_id}&dates=${reschedule_data.dates.join(',')}&availability_ids=${reschedule_data.availability_ids.join(',')}`);
    };

    return (
        <GuardianLayout pageTitle="Reschedule Payment">
            <Head title="Reschedule Payment" />
            <div className="min-h-screen bg-gray-50">
                <div className="max-w-4xl mx-auto px-6 py-8">
                    {/* Header */}
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold text-gray-900 mb-2">
                            Reschedule Payment
                        </h1>
                        <p className="text-gray-600">
                            Complete your reschedule request for {pricing.teacher_name}
                        </p>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        {/* Left Column - Pricing Details */}
                        <div className="space-y-6">
                            {/* Original Booking */}
                            <div className="bg-white rounded-lg border border-gray-200 p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Original Booking</h3>
                                <div className="space-y-3">
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Teacher:</span>
                                        <span className="font-medium">{pricing.teacher_name}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Subject:</span>
                                        <span className="font-medium">{pricing.subject}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Date:</span>
                                        <span className="font-medium">{pricing.original_date}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Time:</span>
                                        <span className="font-medium">{pricing.original_time}</span>
                                    </div>
                                </div>
                            </div>

                            {/* New Booking */}
                            <div className="bg-white rounded-lg border border-gray-200 p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">New Booking</h3>
                                <div className="space-y-3">
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Teacher:</span>
                                        <span className="font-medium">{pricing.teacher_name}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Subject:</span>
                                        <span className="font-medium">{pricing.subject}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">New Dates:</span>
                                        <div className="text-right">
                                            {pricing.new_dates.map((date, index) => (
                                                <div key={index} className="font-medium">{date}</div>
                                            ))}
                                        </div>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Sessions:</span>
                                        <span className="font-medium">{pricing.sessions_count}</span>
                                    </div>
                                </div>
                            </div>

                            {/* Pricing Breakdown */}
                            <div className="bg-white rounded-lg border border-gray-200 p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Pricing Breakdown</h3>
                                <div className="space-y-3">
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Hourly Rate (NGN):</span>
                                        <span className="font-medium">₦{pricing.hourly_rate_ngn.toLocaleString()}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Hourly Rate (USD):</span>
                                        <span className="font-medium">${pricing.hourly_rate_usd}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Duration per session:</span>
                                        <span className="font-medium">{pricing.duration_hours} hour{pricing.duration_hours > 1 ? 's' : ''}</span>
                                    </div>
                                    <hr className="border-gray-200" />
                                    <div className="flex justify-between text-lg font-semibold">
                                        <span>Total Amount (NGN):</span>
                                        <span className="text-[#2C7870]">₦{pricing.total_amount_ngn.toLocaleString()}</span>
                                    </div>
                                    <div className="flex justify-between text-lg font-semibold">
                                        <span>Total Amount (USD):</span>
                                        <span className="text-[#2C7870]">${pricing.total_amount_usd}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Right Column - Payment Form */}
                        <div className="space-y-6">
                            {/* Payment Method */}
                            <div className="bg-white rounded-lg border border-gray-200 p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Payment Method</h3>
                                <div className="space-y-3">
                                    <label className="flex items-center space-x-3 cursor-pointer">
                                        <input
                                            type="radio"
                                            name="payment_method"
                                            value="wallet"
                                            checked={paymentMethod === 'wallet'}
                                            onChange={(e) => setPaymentMethod(e.target.value as 'wallet' | 'card')}
                                            className="w-4 h-4 text-[#2C7870] focus:ring-[#2C7870]"
                                        />
                                        <span className="text-gray-700">Pay from Wallet</span>
                                    </label>
                                    <label className="flex items-center space-x-3 cursor-pointer">
                                        <input
                                            type="radio"
                                            name="payment_method"
                                            value="card"
                                            checked={paymentMethod === 'card'}
                                            onChange={(e) => setPaymentMethod(e.target.value as 'wallet' | 'card')}
                                            className="w-4 h-4 text-[#2C7870] focus:ring-[#2C7870]"
                                        />
                                        <span className="text-gray-700">Pay with Card</span>
                                    </label>
                                </div>
                            </div>

                            {/* Terms and Conditions */}
                            <div className="bg-white rounded-lg border border-gray-200 p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Terms and Conditions</h3>
                                <div className="space-y-3">
                                    <label className="flex items-start space-x-3 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={agreeTerms}
                                            onChange={(e) => setAgreeTerms(e.target.checked)}
                                            className="w-4 h-4 text-[#2C7870] focus:ring-[#2C7870] mt-1"
                                        />
                                        <span className="text-sm text-gray-700">
                                            I agree to the reschedule terms and conditions. I understand that:
                                            <ul className="mt-2 ml-4 list-disc text-xs text-gray-600">
                                                <li>The teacher will be notified of this reschedule request</li>
                                                <li>The teacher has 24 hours to approve or decline the request</li>
                                                <li>If approved, the new session will replace the original booking</li>
                                                <li>If declined, the original booking will remain unchanged</li>
                                                <li>No additional charges apply for rescheduling</li>
                                            </ul>
                                        </span>
                                    </label>
                                </div>
                            </div>

                            {/* Action Buttons */}
                            <div className="flex gap-4">
                                <Button
                                    variant="outline"
                                    onClick={handleGoBack}
                                    className="flex-1 px-8 py-3 text-[#2c7870] border-[#2c7870] hover:bg-[#2c7870] hover:text-white rounded-lg font-medium transition-colors"
                                >
                                    Go Back
                                </Button>
                                <Button
                                    onClick={handleSubmit}
                                    disabled={!agreeTerms || isSubmitting}
                                    className="flex-1 px-8 py-3 bg-[#2c7870] hover:bg-[#236158] text-white disabled:bg-gray-300 disabled:cursor-not-allowed rounded-lg font-medium transition-colors"
                                >
                                    {isSubmitting ? 'Submitting...' : 'Submit Reschedule Request'}
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </GuardianLayout>
    );
}
