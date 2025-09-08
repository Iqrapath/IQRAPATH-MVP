/**
 * 🎨 FIGMA REFERENCE
 * Reschedule Pricing & Payment page for reschedule process
 * 
 * EXACT SPECS FROM FIGMA:
 * - Session rate display with pricing
 * - Currency selection (USD/NGN)
 * - Payment methods with checkboxes
 * - Go Back and Proceed To Payment buttons
 * - Show current booking details
 * - Show reschedule reason 
 */

import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { toast } from 'sonner';
import StudentLayout from '@/layouts/student/student-layout';
import { Head } from '@inertiajs/react';
import InsufficientFundsModal from '@/components/student/InsufficientFundsModal';
import BookingSummaryModal from '@/components/student/BookingSummaryModal';
import RescheduleSummaryModal from '@/components/student/RescheduleSummaryModal';
import BookingSuccessModal from '@/components/student/BookingSuccessModal';
import RescheduleSuccessModal from '@/components/student/RescheduleSuccessModal';
import RecommendedTeachers from '../components/RecommendedTeachers';
import { BookingData } from '@/types';

interface TimeSlot {
    id: number;
    day_of_week: string;
    start_time: string;
    end_time: string;
    formatted_time: string;
    time_zone: string;
}

import { RecommendedTeacher } from '@/types';

interface ReschedulePricingPaymentPageProps {
    booking_id: number;
    teacher_id: number;
    dates: string[];
    availability_ids: number[];
    time_slots: TimeSlot[];
    subjects: string[];
    note_to_teacher: string;
    reschedule_reason: string;
    booking: BookingData;
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
    };
}

export default function ReschedulePricingPaymentPage({ 
    booking_id,
    teacher_id,
    dates,
    availability_ids,
    time_slots,
    subjects,
    note_to_teacher,
    reschedule_reason,
    booking,
    teacher,
    wallet_balance_usd = 0,
    wallet_balance_ngn = 0,
    user
}: ReschedulePricingPaymentPageProps) {
    const [selectedCurrency, setSelectedCurrency] = useState<'USD' | 'NGN'>('NGN');
    const [selectedPaymentMethods, setSelectedPaymentMethods] = useState<string[]>(['wallet']);
    const [showInsufficientFundsModal, setShowInsufficientFundsModal] = useState(false);
    const [showBookingSummaryModal, setShowBookingSummaryModal] = useState(false);
    const [showRescheduleSummaryModal, setShowRescheduleSummaryModal] = useState(false);
    const [showBookingSuccessModal, setShowBookingSuccessModal] = useState(false);
    const [showRescheduleSuccessModal, setShowRescheduleSuccessModal] = useState(false);
    const [isProcessingPayment, setIsProcessingPayment] = useState(false);
    const [currentWalletBalanceUSD, setCurrentWalletBalanceUSD] = useState(wallet_balance_usd);
    const [currentWalletBalanceNGN, setCurrentWalletBalanceNGN] = useState(wallet_balance_ngn);

    // Show loading state while redirecting if missing required data
    if (!booking_id || !teacher_id || !dates || dates.length === 0 || !availability_ids || availability_ids.length === 0 || !subjects || subjects.length === 0) {
        return (
            <StudentLayout pageTitle="Reschedule Pricing & Payment">
                <Head title="Reschedule Pricing & Payment" />
                <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                    <div className="text-center">
                        <p className="text-gray-600 mb-4">Redirecting to reschedule...</p>
                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#2C7870] mx-auto"></div>
                    </div>
                </div>
            </StudentLayout>
        );
    }

    // Check if teacher data is available
    if (!teacher) {
        return (
            <StudentLayout pageTitle="Reschedule Pricing & Payment">
                <Head title="Reschedule Pricing & Payment" />
                <div className="min-h-screen bg-[#F8F9FA]">
                    <div className="max-w-2xl mx-auto px-6 py-8">
                        <div className="bg-white rounded-2xl p-8 border border-[#E8E8E8]">
                            <div className="text-center">
                                <h2 className="text-xl font-semibold text-[#212121] mb-4">
                                    Teacher Not Found
                                </h2>
                                <p className="text-[#4F4F4F] mb-6">
                                    The teacher information could not be loaded. This might happen if the page was refreshed. Please start the reschedule process again.
                                </p>
                                <Button
                                    onClick={() => router.visit('/student/my-bookings')}
                                    className="px-8 py-3 bg-[#2C7870] hover:bg-[#236158] text-white rounded-lg"
                                >
                                    My Bookings
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </StudentLayout>
        );
    }

    // Check if teacher has pricing set (with fallback values from backend)
    if (!teacher?.hourly_rate_usd || !teacher?.hourly_rate_ngn) {
        return (
            <StudentLayout pageTitle="Reschedule Pricing & Payment">
                <Head title="Reschedule Pricing & Payment" />
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
                                    onClick={() => router.visit('/student/my-bookings')}
                                    className="px-8 py-3 bg-[#2C7870] hover:bg-[#236158] text-white rounded-lg"
                                >
                                    My Bookings
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
        router.visit('/student/reschedule/session-details', {
            method: 'post',
            data: {
                booking_id,
                teacher_id,
                dates,
                availability_ids,
                subjects,
                note_to_teacher,
                reschedule_reason
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

        // Show reschedule summary modal if balance is sufficient
        setShowRescheduleSummaryModal(true);
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
        router.visit('/student/my-bookings');
    };

    const handleConfirmReschedule = () => {
        setShowRescheduleSummaryModal(false);
        handleConfirmPayment();
    };

    const handleConfirmPayment = async () => {
        setIsProcessingPayment(true);
        
        try {
            // Calculate new booking details from selected dates and availability
            const newBookingDate = dates[0]; // Use first selected date
            const firstAvailabilityId = availability_ids[0]; // Use first selected availability
            const firstTimeSlot = time_slots.find(slot => slot.id === firstAvailabilityId);
            
            if (!firstTimeSlot) {
                throw new Error('Selected time slot not found');
            }
            
            // Calculate duration in minutes
            const startTime = new Date(`2000-01-01T${firstTimeSlot.start_time}`);
            const endTime = new Date(`2000-01-01T${firstTimeSlot.end_time}`);
            const durationMinutes = Math.round((endTime.getTime() - startTime.getTime()) / (1000 * 60));
            
            // Format times to H:i format (remove seconds if present)
            const formatTimeToH_i = (timeString: string) => {
                // If time includes seconds (e.g., "14:30:00"), remove them
                return timeString.includes(':') && timeString.split(':').length === 3 
                    ? timeString.substring(0, 5) // Take only HH:MM part
                    : timeString; // Already in HH:MM format
            };
            
            // Use axios for API call instead of router.visit to handle JSON response
            const response = await window.axios.post('/student/reschedule/submit', {
                booking_id,
                teacher_id,
                new_booking_date: newBookingDate,
                new_start_time: formatTimeToH_i(firstTimeSlot.start_time),
                new_end_time: formatTimeToH_i(firstTimeSlot.end_time),
                new_duration_minutes: durationMinutes,
                subjects,
                note_to_teacher,
                reschedule_reason,
                currency: selectedCurrency,
                payment_methods: selectedPaymentMethods,
                amount: selectedCurrency === 'USD' ? totalUSD : totalNGN
            });

            if (response.data.success) {
                // Close reschedule summary modal and show success modal
                setShowRescheduleSummaryModal(false);
                setShowRescheduleSuccessModal(true);
                
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
                throw new Error(response.data.message || 'Reschedule request failed');
            }
        } catch (error: any) {
            console.error('Reschedule error:', error);
            if (error.response?.data?.errors) {
                const errorMessages = Object.values(error.response.data.errors).flat().join('\n');
                toast.error('Reschedule request failed:\n' + errorMessages);
            } else {
                toast.error(error.response?.data?.message || 'Reschedule request failed. Please try again.');
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
        <StudentLayout pageTitle="Reschedule Pricing & Payment">
            <Head title="Reschedule Pricing & Payment" />
            <div className="">
                <div className="">
                    {/* Header */}
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold text-[#212121] mb-2">
                            Reschedule Pricing & Payment
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

                    {/* Current Booking Details */}
                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                        <h3 className="text-lg font-semibold text-blue-900 mb-4">Current Booking</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div className="flex items-center gap-2">
                                <span className="text-gray-600">Date:</span>
                                <span className="font-medium text-gray-900">
                                    {booking.booking_date ? new Date(booking.booking_date).toLocaleDateString('en-US', {
                                        weekday: 'long',
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric'
                                    }) : 'Unknown Date'}
                                </span>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="text-gray-600">Time:</span>
                                <span className="font-medium text-gray-900">
                                    {booking.start_time && booking.end_time ? 
                                        `${new Date(`2000-01-01T${booking.start_time}`).toLocaleTimeString('en-US', {
                                            hour: 'numeric',
                                            minute: '2-digit',
                                            hour12: true
                                        })} - ${new Date(`2000-01-01T${booking.end_time}`).toLocaleTimeString('en-US', {
                                            hour: 'numeric',
                                            minute: '2-digit',
                                            hour12: true
                                        })}` : 'Unknown Time'}
                                </span>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="text-gray-600">Subject:</span>
                                <span className="font-medium text-gray-900">
                                    {booking.title}
                                </span>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="text-gray-600">Teacher:</span>
                                <span className="font-medium text-gray-900">
                                    Ustadh {typeof booking.teacher === 'object' ? booking.teacher.name : booking.teacher}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Reschedule Reason */}
                    <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-8">
                        <h3 className="text-lg font-semibold text-yellow-900 mb-4">Reschedule Reason</h3>
                        <p className="text-sm text-gray-700">{reschedule_reason}</p>
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
                                Submit Reschedule Request
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

            {/* Reschedule Summary Modal */}
            <RescheduleSummaryModal
                isOpen={showRescheduleSummaryModal}
                onClose={() => setShowRescheduleSummaryModal(false)}
                onConfirmReschedule={handleConfirmReschedule}
                booking={booking}
                newDate={dates.length === 1 
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
                newTime={formatTimeSlots()}
                newDuration={60} // Default duration, should be calculated from time slots
                totalFee={selectedCurrency === 'USD' ? totalUSD : totalNGN}
                currency={selectedCurrency === 'USD' ? '$' : '₦'}
                rescheduleReason={reschedule_reason}
                isProcessing={isProcessingPayment}
            />

            {/* Reschedule Success Modal */}
            <RescheduleSuccessModal
                isOpen={showRescheduleSuccessModal}
                onClose={handleBookingSuccessClose}
                teacher={{
                    id: teacher?.id || teacher_id,
                    name: teacher?.name || 'Unknown Teacher'
                }}
                currentDate={booking.booking_date ? new Date(booking.booking_date).toLocaleDateString('en-US', { 
                    weekday: 'long', 
                    month: 'long', 
                    day: 'numeric' 
                }) : 'Unknown Date'}
                currentTime={booking.start_time && booking.end_time ? 
                    `${new Date(`2000-01-01T${booking.start_time}`).toLocaleTimeString('en-US', {
                        hour: 'numeric',
                        minute: '2-digit',
                        hour12: true
                    })} - ${new Date(`2000-01-01T${booking.end_time}`).toLocaleTimeString('en-US', {
                        hour: 'numeric',
                        minute: '2-digit',
                        hour12: true
                    })}` : 'Unknown Time'}
                newDate={dates.length === 1 
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
                newTime={formatTimeSlots()}
            />

            {/* Recommended Teachers Section */}
            {teacher?.recommended_teachers && teacher.recommended_teachers.length > 0 && (
                <div className="mt-12">
                    <RecommendedTeachers teachers={teacher.recommended_teachers} />
                </div>
            )}
        </StudentLayout>
    );
}