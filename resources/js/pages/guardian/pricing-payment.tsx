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
import GuardianLayout from '@/layouts/guardian/guardian-layout';
import { Head } from '@inertiajs/react';
import InsufficientFundsModal from '@/components/student/InsufficientFundsModal';
import BookingSummaryModal from '@/components/student/BookingSummaryModal';
import BookingSuccessModal from '@/components/student/BookingSuccessModal';
import RecommendedTeachers from '@/pages/student/components/RecommendedTeachers';

interface TimeSlot {
    id: number;
    day_of_week: string;
    start_time: string;
    end_time: string;
    formatted_time: string;
    time_zone: string;
}

import { RecommendedTeacher } from '@/types';

interface PricingPaymentPageProps {
    teacher_id: number;
    dates: string[];
    availability_ids: number[];
    time_slots: TimeSlot[];
    subjects: string[];
    note_to_teacher: string;
    teacher?: {
        id: number;
        name: string;
        hourly_rate_usd?: number;
        hourly_rate_ngn?: number;
        recommended_teachers?: RecommendedTeacher[];
    };
    wallet_balance_usd?: number;
    wallet_balance_ngn?: number;
    user?: {
        id: number;
        name: string;
        email: string;
        country: string;
        additional_roles?: string[];
    };
    children?: Array<{
        id: number;
        name: string;
        email: string;
    }>;
}

export default function PricingPaymentPage({ 
    teacher_id,
    dates,
    availability_ids,
    time_slots,
    subjects,
    note_to_teacher,
    teacher,
    wallet_balance_usd = 0,
    wallet_balance_ngn = 0,
    user,
    children = []
}: PricingPaymentPageProps) {
    const [selectedCurrency, setSelectedCurrency] = useState<'USD' | 'NGN'>('NGN');
    const [selectedPaymentMethods, setSelectedPaymentMethods] = useState<string[]>(['wallet']);
    const [bookingFor, setBookingFor] = useState<'self' | 'child'>('child');
    const [selectedChildId, setSelectedChildId] = useState<number | null>(null);
    const [showInsufficientFundsModal, setShowInsufficientFundsModal] = useState(false);
    const [showBookingSummaryModal, setShowBookingSummaryModal] = useState(false);
    const [showBookingSuccessModal, setShowBookingSuccessModal] = useState(false);
    const [isProcessingPayment, setIsProcessingPayment] = useState(false);
    const [currentWalletBalanceUSD, setCurrentWalletBalanceUSD] = useState(wallet_balance_usd);
    const [currentWalletBalanceNGN, setCurrentWalletBalanceNGN] = useState(wallet_balance_ngn);

    // Show loading state while redirecting if missing required data
    if (!teacher_id || !dates || dates.length === 0 || !availability_ids || availability_ids.length === 0 || !subjects || subjects.length === 0) {
        return (
            <GuardianLayout pageTitle="Pricing & Payment">
                <Head title="Pricing & Payment" />
                <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                    <div className="text-center">
                        <p className="text-gray-600 mb-4">Redirecting to browse teachers...</p>
                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#2C7870] mx-auto"></div>
                    </div>
                </div>
            </GuardianLayout>
        );
    }

    // Check if teacher data is available
    if (!teacher) {
        return (
            <GuardianLayout pageTitle="Pricing & Payment">
                <Head title="Pricing & Payment" />
                <div className="min-h-screen bg-[#F8F9FA]">
                    <div className="max-w-2xl mx-auto px-6 py-8">
                        <div className="bg-white rounded-2xl p-8 border border-[#E8E8E8]">
                            <div className="text-center">
                                <h2 className="text-xl font-semibold text-[#212121] mb-4">
                                    Teacher Not Found
                                </h2>
                                <p className="text-[#4F4F4F] mb-6">
                                    The teacher information could not be loaded. This might happen if the page was refreshed. Please start the booking process again.
                                </p>
                                <Button
                                    onClick={() => router.visit('/guardian/browse-teachers')}
                                    className="px-8 py-3 bg-[#2C7870] hover:bg-[#236158] text-white rounded-lg"
                                >
                                    Browse Teachers
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </GuardianLayout>
        );
    }

    // Check if teacher has pricing set
    if (!teacher?.hourly_rate_usd || !teacher?.hourly_rate_ngn) {
        return (
            <GuardianLayout pageTitle="Pricing & Payment">
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
                                    onClick={() => router.visit('/guardian/browse-teachers')}
                                    className="px-8 py-3 bg-[#2C7870] hover:bg-[#236158] text-white rounded-lg"
                                >
                                    Browse Teachers
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </GuardianLayout>
        );
    }

    // Calculate pricing
    const usdRate = teacher.hourly_rate_usd;
    const ngnRate = teacher.hourly_rate_ngn;
    const numberOfDays = dates.length;
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
        router.visit('/guardian/booking/session-details', {
            method: 'post',
            data: {
                teacher_id,
                dates,
                availability_ids,
                subjects,
                note_to_teacher
            }
        });
    };

    const handleProceedToPayment = () => {
        // Validate booking selection
        if (bookingFor === 'child' && !selectedChildId) {
            alert('Please select a child for this booking');
            return;
        }
        
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
        // Redirect to guardian dashboard or bookings page
        router.visit('/guardian/dashboard');
    };

    const handleConfirmPayment = async () => {
        setIsProcessingPayment(true);
        
        try {
            // Use axios for API call instead of router.visit to handle JSON response
            const response = await window.axios.post('/guardian/booking/payment', {
                teacher_id,
                dates,
                availability_ids,
                subjects,
                note_to_teacher,
                currency: selectedCurrency,
                payment_methods: selectedPaymentMethods,
                amount: selectedCurrency === 'USD' ? totalUSD : totalNGN,
                booking_for: bookingFor,
                child_id: bookingFor === 'child' ? selectedChildId : null
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

    const formatTimeSlots = (): string => {
        if (time_slots.length === 0) {
            return "Time not selected";
        }
        
        if (time_slots.length === 1) {
            return time_slots[0].formatted_time;
        }
        
        // For multiple time slots, group by day and show times
        const groupedByDay: { [key: string]: string[] } = {};
        time_slots.forEach(slot => {
            if (!groupedByDay[slot.day_of_week]) {
                groupedByDay[slot.day_of_week] = [];
            }
            groupedByDay[slot.day_of_week].push(slot.formatted_time);
        });
        
        const dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        const sortedDays = Object.keys(groupedByDay).sort((a, b) => {
            return dayOrder.indexOf(a) - dayOrder.indexOf(b);
        });
        
        return sortedDays.map(day => 
            `${day}: ${groupedByDay[day].join(', ')}`
        ).join(' | ');
    };

    return (
        <GuardianLayout pageTitle="Pricing & Payment">
            <Head title="Pricing & Payment" />
            <div className="min-h-screen bg-[#F8F9FA]">
                <div className="max-w-2xl mx-auto px-6 py-8">
                    {/* Header */}
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold text-[#212121] mb-2">
                            Pricing & Payment
                        </h1>
                        <div className="text-sm text-[#4F4F4F] mb-2">
                            Selected {dates.length} day{dates.length > 1 ? 's' : ''} • {availability_ids.length} time slot{availability_ids.length > 1 ? 's' : ''}
                        </div>
                        <div className="text-sm text-[#828282]">
                            {dates.map((date, index) => (
                                <div key={index} className="mb-1">
                                    <span className="font-medium">
                                        {new Date(date).toLocaleDateString('en-US', { 
                                            weekday: 'short', 
                                            month: 'short', 
                                            day: 'numeric' 
                                        })}
                                    </span>
                                </div>
                            ))}
                            <div className="text-sm text-[#4F4F4F] mt-2">
                                <span className="font-medium">Time:</span> {formatTimeSlots()}
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-2xl p-8 border border-[#E8E8E8]">
                        {/* Booking Selection */}
                        <div className="mb-8">
                            <h2 className="text-xl font-semibold text-[#212121] mb-6">
                                Who is this booking for?
                            </h2>
                            
                            <div className="space-y-4">
                                {/* Option 1: For myself */}
                                {user?.additional_roles?.includes('student') && (
                                    <label className="flex items-center gap-3 cursor-pointer p-4 border border-[#E8E8E8] rounded-lg hover:bg-[#F8F8F8]">
                                        <input
                                            type="radio"
                                            name="booking_for"
                                            value="self"
                                            checked={bookingFor === 'self'}
                                            onChange={(e) => {
                                                setBookingFor('self');
                                                setSelectedChildId(null);
                                            }}
                                            className="w-4 h-4 text-[#14B8A6]"
                                        />
                                        <div>
                                            <div className="font-medium text-[#212121]">
                                                For myself
                                            </div>
                                            <div className="text-sm text-[#4F4F4F]">
                                                Book this class for your own learning
                                            </div>
                                        </div>
                                    </label>
                                )}
                                
                                {/* Option 2: For a child */}
                                {children.length > 0 && (
                                    <label className="flex items-center gap-3 cursor-pointer p-4 border border-[#E8E8E8] rounded-lg hover:bg-[#F8F8F8]">
                                        <input
                                            type="radio"
                                            name="booking_for"
                                            value="child"
                                            checked={bookingFor === 'child'}
                                            onChange={(e) => setBookingFor('child')}
                                            className="w-4 h-4 text-[#14B8A6]"
                                        />
                                        <div className="flex-1">
                                            <div className="font-medium text-[#212121]">
                                                For one of my children
                                            </div>
                                            <div className="text-sm text-[#4F4F4F]">
                                                Book this class for one of your children
                                            </div>
                                            
                                            {bookingFor === 'child' && (
                                                <div className="mt-3">
                                                    <select
                                                        value={selectedChildId || ''}
                                                        onChange={(e) => setSelectedChildId(Number(e.target.value))}
                                                        className="w-full p-3 border border-[#E8E8E8] rounded-lg focus:ring-2 focus:ring-[#14B8A6] focus:border-transparent"
                                                    >
                                                        <option value="">Select a child</option>
                                                        {children.map((child) => (
                                                            <option key={child.id} value={child.id}>
                                                                {child.name}
                                                            </option>
                                                        ))}
                                                    </select>
                                                </div>
                                            )}
                                        </div>
                                    </label>
                                )}
                                
                                {/* Show message if no options available */}
                                {!user?.additional_roles?.includes('student') && children.length === 0 && (
                                    <div className="p-4 bg-[#FFF3CD] border border-[#FFEAA7] rounded-lg">
                                        <div className="text-[#856404]">
                                            <strong>No booking options available.</strong><br />
                                            You need to either have children added to your account or request student access to book classes for yourself.
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Session Rate */}
                        <div className="mb-8">
                            <div className="bg-[#E8F5F4] rounded-lg p-6">
                                <h2 className="text-lg font-semibold text-[#212121] mb-3">
                                    Session Rate:
                                </h2>
                                <div className="text-xl font-bold text-[#212121]">
                                    ${usdRate}/₦{ngnRate.toLocaleString()} per session
                                </div>
                                <div className="text-sm text-[#4F4F4F] mt-2">
                                    {numberOfDays} day{numberOfDays > 1 ? 's' : ''} • {numberOfSlots} session{numberOfSlots > 1 ? 's' : ''} × ${usdRate} = ${totalUSD} / ₦{totalNGN.toLocaleString()}
                                </div>
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
                                {numberOfDays} day{numberOfDays > 1 ? 's' : ''} • {numberOfSlots} session{numberOfSlots > 1 ? 's' : ''} with {teacher?.name}
                            </div>
                        </div>

                        {/* Action Buttons */}
                        <div className="flex gap-4 justify-end">
                            <Button
                                variant="outline"
                                onClick={handleGoBack}
                                className="px-8 py-3 text-[#2C7870] border-[#2C7870] hover:bg-[#F8F9FA] rounded-full"
                            >
                                Go Back
                            </Button>
                            <Button
                                onClick={handleProceedToPayment}
                                disabled={selectedPaymentMethods.length === 0}
                                className="px-8 py-3 bg-[#2C7870] hover:bg-[#236158] text-white disabled:bg-[#BDBDBD] disabled:cursor-not-allowed rounded-full"
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
                date={dates.length === 1 
                    ? new Date(dates[0]).toLocaleDateString('en-US', { 
                        weekday: 'long', 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    })
                    : `${dates.length} days: ${dates.map(d => new Date(d).toLocaleDateString('en-US', { 
                        month: 'short', 
                        day: 'numeric' 
                    })).join(', ')}`
                }
                time={formatTimeSlots()}
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
                sessionDate={dates.length === 1 
                    ? new Date(dates[0]).toLocaleDateString('en-US', { 
                        weekday: 'long', 
                        month: 'long', 
                        day: 'numeric' 
                    })
                    : `${dates.length} days: ${dates.map(d => new Date(d).toLocaleDateString('en-US', { 
                        month: 'short', 
                        day: 'numeric' 
                    })).join(', ')}`
                }
                sessionTime={formatTimeSlots()}
            />

            {/* Recommended Teachers Section */}
            {teacher?.recommended_teachers && teacher.recommended_teachers.length > 0 && (
                <div className="mt-12">
                    <RecommendedTeachers teachers={teacher.recommended_teachers} />
                </div>
            )}
        </GuardianLayout>
    );
}