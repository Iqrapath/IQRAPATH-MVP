/**
 * 🎨 FIGMA REFERENCE
 * Pricing & Payment page for booking process
 * 
 * EXACT SPECS FROM FIGMA:
 * - Session rate display with pricing
 * - Currency selection (USD/NGN)
 * - Payment methods with checkboxes
 * - Go Back and Proceed To Payment buttons
 */

import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import StudentLayout from '@/layouts/student/student-layout';
import { Head } from '@inertiajs/react';
import InsufficientFundsModal from '@/components/student/InsufficientFundsModal';
import BookingSummaryModal from '@/components/student/BookingSummaryModal';
import BookingSuccessModal from '@/components/student/BookingSuccessModal';

interface PricingPaymentPageProps {
    teacher_id: number;
    date: string;
    availability_ids: number[];
    subjects: string[];
    note_to_teacher: string;
    teacher?: {
        id: number;
        name: string;
        hourly_rate_usd?: number;
        hourly_rate_ngn?: number;
    };
    wallet_balance_usd?: number;
    wallet_balance_ngn?: number;
    user?: {
        id: number;
        name: string;
        email: string;
        country: string;
    };
}

export default function PricingPaymentPage({ 
    teacher_id,
    date,
    availability_ids,
    subjects,
    note_to_teacher,
    teacher,
    wallet_balance_usd = 0,
    wallet_balance_ngn = 0,
    user
}: PricingPaymentPageProps) {
    const [selectedCurrency, setSelectedCurrency] = useState<'USD' | 'NGN'>('NGN');
    const [selectedPaymentMethods, setSelectedPaymentMethods] = useState<string[]>(['wallet']);
    const [showInsufficientFundsModal, setShowInsufficientFundsModal] = useState(false);
    const [showBookingSummaryModal, setShowBookingSummaryModal] = useState(false);
    const [showBookingSuccessModal, setShowBookingSuccessModal] = useState(false);
    const [isProcessingPayment, setIsProcessingPayment] = useState(false);
    const [currentWalletBalanceUSD, setCurrentWalletBalanceUSD] = useState(wallet_balance_usd);
    const [currentWalletBalanceNGN, setCurrentWalletBalanceNGN] = useState(wallet_balance_ngn);

    // Show loading state while redirecting if missing required data
    if (!teacher_id || !date || !availability_ids || availability_ids.length === 0 || !subjects || subjects.length === 0) {
        return (
            <StudentLayout pageTitle="Pricing & Payment">
                <Head title="Pricing & Payment" />
                <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                    <div className="text-center">
                        <p className="text-gray-600 mb-4">Redirecting to browse teachers...</p>
                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#2C7870] mx-auto"></div>
                    </div>
                </div>
            </StudentLayout>
        );
    }

    // Check if teacher has pricing set
    if (!teacher?.hourly_rate_usd || !teacher?.hourly_rate_ngn) {
        return (
            <StudentLayout pageTitle="Pricing & Payment">
                <Head title="Pricing & Payment" />
                <div className="min-h-screen bg-[#F8F9FA]">
                    <div className="max-w-2xl mx-auto px-6 py-8">
                        <div className="bg-white rounded-2xl p-8 border border-[#E8E8E8]">
                            <div className="text-center">
                                <h2 className="text-xl font-semibold text-[#212121] mb-4">
                                    Pricing Not Available
                                </h2>
                                <p className="text-[#4F4F4F] mb-6">
                                    This teacher has not set their pricing yet. Please contact support or choose a different teacher.
                                </p>
                                <Button
                                    onClick={() => router.visit('/student/browse-teachers')}
                                    className="px-8 py-3 bg-[#2C7870] hover:bg-[#236158] text-white rounded-lg"
                                >
                                    Browse Teachers
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </StudentLayout>
        );
    }

    // Calculate pricing
    const usdRate = teacher.hourly_rate_usd;
    const ngnRate = teacher.hourly_rate_ngn;
    const numberOfSlots = availability_ids.length;
    const totalUSD = usdRate * numberOfSlots;
    const totalNGN = ngnRate * numberOfSlots;

    const paymentOptions = [
        { id: 'wallet', label: 'My Wallet', enabled: true },
        { id: 'paypal', label: 'PayPal', enabled: true },
        { id: 'card', label: 'Credit/Debit Card', enabled: true },
        { id: 'transfer', label: 'Bank Transfer', enabled: true }
    ];

    const handlePaymentMethodToggle = (methodId: string) => {
        setSelectedPaymentMethods(prev => {
            if (prev.includes(methodId)) {
                return prev.filter(id => id !== methodId);
            } else {
                return [...prev, methodId];
            }
        });
    };

    const handleGoBack = () => {
        router.visit('/student/booking/session-details', {
            method: 'post',
            data: {
                teacher_id,
                date,
                availability_ids
            }
        });
    };

    const handleProceedToPayment = () => {
        if (selectedPaymentMethods.length === 0) {
            alert('Please select at least one payment method');
            return;
        }

        // If wallet payment is selected, check balance
        if (selectedPaymentMethods.includes('wallet')) {
            const requiredAmount = selectedCurrency === 'USD' ? totalUSD : totalNGN;
            const currentBalance = selectedCurrency === 'USD' ? currentWalletBalanceUSD : currentWalletBalanceNGN;
            
            if (currentBalance < requiredAmount) {
                // Show insufficient funds modal
                setShowInsufficientFundsModal(true);
                return;
            }
        }

        // Show booking summary modal if balance is sufficient
        setShowBookingSummaryModal(true);
    };

    const handleFundAccount = () => {
        // The InsufficientFundsModal will handle showing the FundAccountModal
        // No need to redirect to a separate page since we use modals
    };

    const handleCloseModal = () => {
        setShowInsufficientFundsModal(false);
    };

    const handleBalanceUpdated = (newBalance: number) => {
        if (selectedCurrency === 'USD') {
            setCurrentWalletBalanceUSD(newBalance);
            // Also update NGN balance (convert from USD)
            setCurrentWalletBalanceNGN(newBalance * 1500);
        } else {
            setCurrentWalletBalanceNGN(newBalance);
            // Also update USD balance (convert from NGN)
            setCurrentWalletBalanceUSD(newBalance / 1500);
        }
    };

    const handleProceedToPaymentAfterFunding = () => {
        // Close the insufficient funds modal and proceed with payment
        setShowInsufficientFundsModal(false);
        // Call the original payment handler which will now have sufficient balance
        handleProceedToPayment();
    };

    const handleBookingSummaryClose = () => {
        setShowBookingSummaryModal(false);
    };

    const handleBookingSuccessClose = () => {
        setShowBookingSuccessModal(false);
        // Redirect to student dashboard or bookings page
        router.visit('/student/dashboard');
    };

    const handleConfirmPayment = async () => {
        setIsProcessingPayment(true);
        
        try {
            // Use axios for API call instead of router.visit to handle JSON response
            const response = await window.axios.post('/student/booking/payment', {
                teacher_id,
                date,
                availability_ids,
                subjects,
                note_to_teacher,
                currency: selectedCurrency,
                payment_methods: selectedPaymentMethods,
                amount: selectedCurrency === 'USD' ? totalUSD : totalNGN
            });

            if (response.data.success) {
                // Close booking summary modal and show success modal
                setShowBookingSummaryModal(false);
                setShowBookingSuccessModal(true);
                
                // Update wallet balance after successful payment
                const paidAmount = selectedCurrency === 'USD' ? totalUSD : totalNGN;
                if (selectedCurrency === 'USD') {
                    setCurrentWalletBalanceUSD(prev => prev - paidAmount);
                    setCurrentWalletBalanceNGN(prev => prev - (paidAmount * 1500)); // Convert to NGN
                } else {
                    setCurrentWalletBalanceNGN(prev => prev - paidAmount);
                    setCurrentWalletBalanceUSD(prev => prev - (paidAmount / 1500)); // Convert to USD
                }
            } else {
                throw new Error(response.data.message || 'Payment failed');
            }
        } catch (error: any) {
            console.error('Payment error:', error);
            if (error.response?.data?.errors) {
                const errorMessages = Object.values(error.response.data.errors).flat().join('\n');
                alert('Payment failed:\n' + errorMessages);
            } else {
                alert(error.response?.data?.message || 'Payment failed. Please try again.');
            }
        } finally {
            setIsProcessingPayment(false);
        }
    };

    return (
        <StudentLayout pageTitle="Pricing & Payment">
            <Head title="Pricing & Payment" />
            <div className="\">
                <div className="">
                    {/* Header */}
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold text-[#212121] mb-2">
                            Pricing & Payment
                        </h1>
                    </div>

                    <div className="">
                        {/* Session Rate */}
                        <div className="mb-8">
                            <div className="bg-[#E8F5F4] rounded-lg p-6">
                                <h2 className="text-lg font-semibold text-[#212121] mb-3">
                                    Session Rate:
                                </h2>
                                <div className="text-xl font-bold text-[#212121]">
                                    ${usdRate}/₦{ngnRate.toLocaleString()} per session
                                </div>
                                {numberOfSlots > 1 && (
                                    <div className="text-sm text-[#4F4F4F] mt-2">
                                        {numberOfSlots} sessions × ${usdRate} = ${totalUSD} / ₦{totalNGN.toLocaleString()}
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Currency Selection */}
                        <div className="mb-8">
                            <h2 className="text-xl font-semibold text-[#212121] mb-6">
                                Choose your currency
                            </h2>
                            
                            <div className="flex gap-8">
                                <label className="flex items-center gap-3 cursor-pointer">
                                    <input
                                        type="radio"
                                        name="currency"
                                        value="USD"
                                        checked={selectedCurrency === 'USD'}
                                        onChange={(e) => setSelectedCurrency(e.target.value as 'USD')}
                                        className="w-5 h-5 text-[#2C7870] focus:ring-[#2C7870] focus:ring-2"
                                    />
                                    <span className="text-lg font-normal text-[#212121]">
                                        USD
                                    </span>
                                </label>

                                <label className="flex items-center gap-3 cursor-pointer">
                                    <input
                                        type="radio"
                                        name="currency"
                                        value="NGN"
                                        checked={selectedCurrency === 'NGN'}
                                        onChange={(e) => setSelectedCurrency(e.target.value as 'NGN')}
                                        className="w-5 h-5 text-[#2C7870] focus:ring-[#2C7870] focus:ring-2"
                                    />
                                    <span className="text-lg font-normal text-[#212121]">
                                        NGN
                                    </span>
                                </label>
                            </div>
                        </div>

                        {/* Payment Methods */}
                        <div className="mb-8">
                            <h2 className="text-xl font-semibold text-[#212121] mb-6">
                                Payment Methods:
                            </h2>
                            
                            <div className="space-y-6">
                                {paymentOptions.map((option) => (
                                    <label 
                                        key={option.id}
                                        className="flex items-center gap-3 cursor-pointer"
                                    >
                                        <input
                                            type="checkbox"
                                            checked={selectedPaymentMethods.includes(option.id)}
                                            onChange={() => handlePaymentMethodToggle(option.id)}
                                            disabled={!option.enabled}
                                            className="w-5 h-5 rounded border-2 border-[#BDBDBD] text-[#2C7870] focus:ring-[#2C7870] focus:ring-1"
                                        />
                                        <span className={`text-lg font-normal ${
                                            option.enabled ? 'text-[#212121]' : 'text-[#BDBDBD]'
                                        }`}>
                                            {option.label}
                                        </span>
                                        {option.id === 'wallet' && selectedPaymentMethods.includes('wallet') && (
                                            <span className="text-sm text-[#2C7870] font-medium">
                                                (Recommended)
                                            </span>
                                        )}
                                    </label>
                                ))}
                            </div>
                        </div>

                        {/* Total Display */}
                        <div className="mb-8 p-4 bg-[#F8F9FA] rounded-lg border border-[#E8E8E8]">
                            <div className="flex justify-between items-center">
                                <span className="text-lg font-semibold text-[#212121]">
                                    Total Amount:
                                </span>
                                <span className="text-xl font-bold text-[#2C7870]">
                                    {selectedCurrency === 'USD' ? `$${totalUSD}` : `₦${totalNGN.toLocaleString()}`}
                                </span>
                            </div>
                            <div className="text-sm text-[#4F4F4F] mt-1">
                                {numberOfSlots} session{numberOfSlots > 1 ? 's' : ''} with {teacher?.name}
                            </div>
                        </div>

                        {/* Action Buttons */}
                        <div className="flex gap-4 justify-end">
                            <Button
                                variant="outline"
                                onClick={handleGoBack}
                                className="px-8 py-3 text-[#4F4F4F] border-[#E8E8E8] hover:bg-[#F8F9FA] rounded-lg"
                            >
                                Go Back
                            </Button>
                            <Button
                                onClick={handleProceedToPayment}
                                disabled={selectedPaymentMethods.length === 0}
                                className="px-8 py-3 bg-[#2C7870] hover:bg-[#236158] text-white disabled:bg-[#BDBDBD] disabled:cursor-not-allowed rounded-lg"
                            >
                                Proceed To Payment
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Insufficient Funds Modal */}
            <InsufficientFundsModal
                isOpen={showInsufficientFundsModal}
                onClose={handleCloseModal}
                onFundAccount={handleFundAccount}
                onBalanceUpdated={handleBalanceUpdated}
                onProceedToPayment={handleProceedToPaymentAfterFunding}
                currentBalance={selectedCurrency === 'USD' ? currentWalletBalanceUSD : currentWalletBalanceNGN}
                requiredAmount={selectedCurrency === 'USD' ? totalUSD : totalNGN}
                currency={selectedCurrency}
                user={user}
            />

            {/* Booking Summary Modal */}
            <BookingSummaryModal
                isOpen={showBookingSummaryModal}
                onClose={handleBookingSummaryClose}
                onConfirmPayment={handleConfirmPayment}
                teacher={{
                    id: teacher?.id || teacher_id,
                    name: teacher?.name || 'Unknown Teacher'
                }}
                subject={{
                    id: 1,
                    name: subjects.join(' / ')
                }}
                date={new Date(date).toLocaleDateString('en-US', { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                })}
                time="5:00 PM - 6:00 PM"
                totalFee={selectedCurrency === 'USD' ? totalUSD : totalNGN}
                currency={selectedCurrency === 'USD' ? '$' : '₦'}
                notes={note_to_teacher}
                isProcessing={isProcessingPayment}
            />

            {/* Booking Success Modal */}
            <BookingSuccessModal
                isOpen={showBookingSuccessModal}
                onClose={handleBookingSuccessClose}
                teacher={{
                    id: teacher?.id || teacher_id,
                    name: teacher?.name || 'Unknown Teacher'
                }}
                sessionDate={new Date(date).toLocaleDateString('en-US', { 
                    weekday: 'long', 
                    month: 'long', 
                    day: 'numeric' 
                })}
                sessionTime="5:00 PM - 6:00 PM"
            />
        </StudentLayout>
    );
}
