/**
 * 🎨 FIGMA DESIGN REFERENCE
 * 
 * Component: CreditCardForm
 * Design: Credit card payment form with card number, expiry, CVV fields
 * 
 * 📏 EXACT SPECIFICATIONS:
 * - Background: White card with rounded corners
 * - Padding: 40px all sides
 * - Border Radius: 24px
 * - Card Number Field: Full width with payment icons (Mastercard, Visa, Apple Pay)
 * - Expiry/CVV: Two-column grid layout
 * - Checkbox: Custom styled with Mastercard icon
 * - Buttons: Teal filled "Make Payment", White outlined "Cancel"
 * - Typography: Inter font family
 * - Colors: #2C7870 (primary teal), #64748B (text gray)
 * 
 * 📱 RESPONSIVE: Desktop-first design
 * 🎯 STATES: Default, hover, focus, disabled, loading
 */

import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { VisaCardIcon } from '@/components/icons/visa-card-icon';
import { MasterCardIcon } from '@/components/icons/master-card-icon';
import { AmericanExpressIconProps } from '@/components/icons/american-express-icon';
import { DiscoverIconProps } from '@/components/icons/discover-icon';
import { CreditCardIcon } from '@/components/icons/credit-card-icon';

interface CreditCardFormProps {
    elements: any;
    cardNumberElement: { current: any };
    cardExpiryElement: { current: any };
    cardCvcElement: { current: any };
    rememberCard: boolean;
    isLoading: boolean;
    isFormValid: boolean;
    onRememberCardChange: (checked: boolean) => void;
    onSubmit: () => void;
    onCancel: () => void;
    setValidationError: (error: string | null) => void;
}

// Card brand logo component
const CardBrandIcon = ({ brand }: { brand: string }) => {
    switch (brand) {
        case 'visa':
            return (
                VisaCardIcon({
                    className: 'w-8 h-6',
                })
            );
        case 'mastercard':
            return (
                MasterCardIcon({
                    className: 'w-8 h-6',
                })
            );
        case 'amex':
            return (
                AmericanExpressIconProps({
                    className: 'w-8 h-6',
                })
            );
        case 'discover':
            return (
                DiscoverIconProps({
                    className: 'w-8 h-6',
                })
            );
        default:
            return (
                CreditCardIcon({
                    className: 'w-8 h-6',
                })
            );
    }
};

export default function CreditCardForm({
    elements,
    cardNumberElement,
    cardExpiryElement,
    cardCvcElement,
    rememberCard,
    isLoading,
    isFormValid,
    onRememberCardChange,
    onSubmit,
    onCancel,
    setValidationError
}: CreditCardFormProps) {
    // Refs for mounting Stripe elements
    const cardNumberRef = useRef<HTMLDivElement | null>(null);
    const cardExpiryRef = useRef<HTMLDivElement | null>(null);
    const cardCvcRef = useRef<HTMLDivElement | null>(null);

    // State for card brand
    const [cardBrand, setCardBrand] = useState<string>('unknown');

    // Stripe element styling
    const elementStyle = {
        base: {
            fontSize: '16px',
            color: '#1E293B',
            fontFamily: '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
            fontWeight: '400',
            lineHeight: '24px',
            '::placeholder': {
                color: '#CBD5E1',
            },
        },
        invalid: {
            color: '#EF4444',
            iconColor: '#EF4444',
        },
    };

    // Create separate Stripe elements
    useEffect(() => {
        if (!elements) return;

        // Cleanup function
        const cleanup = () => {
            if (cardNumberElement.current) {
                cardNumberElement.current.destroy();
                cardNumberElement.current = null;
            }
            if (cardExpiryElement.current) {
                cardExpiryElement.current.destroy();
                cardExpiryElement.current = null;
            }
            if (cardCvcElement.current) {
                cardCvcElement.current.destroy();
                cardCvcElement.current = null;
            }
        };

        cleanup();

        // Use requestAnimationFrame for proper DOM timing
        requestAnimationFrame(() => {
            if (!cardNumberRef.current || !cardExpiryRef.current || !cardCvcRef.current || !elements) return;

            try {
                // Create Card Number Element
                const cardNumberEl = elements.create('cardNumber', {
                    style: elementStyle,
                    showIcon: true,
                });
                cardNumberEl.mount(cardNumberRef.current);
                cardNumberElement.current = cardNumberEl;

                // Create Card Expiry Element
                const cardExpiryEl = elements.create('cardExpiry', {
                    style: elementStyle,
                });
                cardExpiryEl.mount(cardExpiryRef.current);
                cardExpiryElement.current = cardExpiryEl;

                // Create Card CVC Element
                const cardCvcEl = elements.create('cardCvc', {
                    style: elementStyle,
                });
                cardCvcEl.mount(cardCvcRef.current);
                cardCvcElement.current = cardCvcEl;

                // Add event listeners
                const handleChange = (event: any) => {
                    if (event.error) {
                        setValidationError(event.error.message);
                    } else {
                        setValidationError(null);
                    }
                };

                // Listen for card brand changes
                const handleCardNumberChange = (event: any) => {
                    handleChange(event);
                    if (event.brand) {
                        setCardBrand(event.brand);
                    }
                };

                cardNumberEl.on('change', handleCardNumberChange);
                cardExpiryEl.on('change', handleChange);
                cardCvcEl.on('change', handleChange);
            } catch (error) {
                console.error('Failed to create Stripe elements:', error);
            }
        });

        return cleanup;
    }, [elements]);

    return (
        <>
            {/* Header - Outside the card */}
            <h3 className="text-[#1E293B] text-2xl font-normal mb-4">
                Enter your card details to pay
            </h3>

            {/* White Card Container */}
            <div className="bg-white rounded-[32px] p-10 shadow-[0_4px_24px_rgba(0,0,0,0.08)]">
                {/* Card Number Field */}
                <div className="mb-4">
                    <label className="block text-[#64748B] text-sm font-semibold mb-3 uppercase tracking-wider">
                        CARD NUMBER
                    </label>
                    <div
                        ref={cardNumberRef}
                        className="w-full px-3 py-3 border border-[#E2E8F0] rounded-xl bg-[#F8FAFC] focus-within:ring-2 focus-within:ring-[#2C7870]/20 focus-within:border-[#2C7870] transition-all"
                    />
                </div>

                {/* Expiry and CVV Grid */}
                <div className="grid grid-cols-2 gap-6 mb-8">
                    {/* Valid Till - Actual Stripe Expiry Element */}
                    <div>
                        <label className="block text-[#64748B] text-sm font-semibold mb-3 uppercase tracking-wider">
                            VALIG TILL
                        </label>
                        <div className="relative">
                            <div className="absolute left-5 top-1/2 -translate-y-1/2 z-10 pointer-events-none">
                                <CardBrandIcon brand={cardBrand} />
                            </div>
                            <div
                                ref={cardExpiryRef}
                                className="w-full pl-16 pr-5 py-2 border border-[#E2E8F0] rounded-xl bg-[#F8FAFC] focus-within:ring-2 focus-within:ring-[#2C7870]/20 focus-within:border-[#2C7870] transition-all"
                            />
                        </div>
                    </div>

                    {/* CVV - Actual Stripe CVC Element */}
                    <div>
                        <label className="block text-[#64748B] text-sm font-semibold mb-3 uppercase tracking-wider">
                            CVV
                        </label>
                        <div className="relative">
                            <div className="absolute left-5 top-1/2 -translate-y-1/2 z-10 pointer-events-none">
                                <CardBrandIcon brand={cardBrand} />
                            </div>
                            <div
                                ref={cardCvcRef}
                                className="w-full pl-16 pr-5 py-2 border border-[#E2E8F0] rounded-xl bg-[#F8FAFC] focus-within:ring-2 focus-within:ring-[#2C7870]/20 focus-within:border-[#2C7870] transition-all"
                            />
                        </div>
                    </div>
                </div>

                {/* Remember Card Checkbox */}
                <div className="mb-6">
                    <label className="flex items-center gap-3 cursor-pointer group">
                        <div className="relative flex items-center justify-center">
                            <input
                                type="checkbox"
                                checked={rememberCard}
                                onChange={(e) => onRememberCardChange(e.target.checked)}
                                className="w-6 h-6 text-[#2C7870] border-2 border-[#CBD5E1] rounded-md focus:ring-2 focus:ring-[#2C7870] focus:ring-offset-2 cursor-pointer transition-all appearance-none checked:bg-[#2C7870] checked:border-[#2C7870]"
                            />
                            {rememberCard && (
                                <svg className="w-4 h-4 text-white absolute pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                                </svg>
                            )}
                        </div>
                        <span className="text-[#94A3B8] text-base font-normal select-none">
                            Remember this card next time
                        </span>
                    </label>
                </div>

                {/* Divider */}
                <div className="border-t border-[#E2E8F0] mb-8"></div>

                {/* Action Buttons */}
                <div className="flex items-center gap-4">
                    <Button
                        onClick={onSubmit}
                        disabled={isLoading || !isFormValid}
                        className="bg-[#2C7870] hover:bg-[#236158] disabled:bg-[#CBD5E1] disabled:cursor-not-allowed text-white py-4 px-10 rounded-full font-semibold transition-all text-base shadow-sm hover:shadow-md disabled:shadow-none"
                        aria-label="Proceed to payment confirmation"
                    >
                        {isLoading ? 'Processing...' : 'Make Payment'}
                    </Button>
                    <Button
                        onClick={onCancel}
                        disabled={isLoading}
                        className="bg-white border-2 border-[#2C7870] text-[#2C7870] hover:bg-[#F0F9FF] py-4 px-10 rounded-full font-semibold transition-all text-base disabled:opacity-50 disabled:cursor-not-allowed"
                        aria-label="Cancel payment"
                    >
                        Cancel
                    </Button>
                </div>
            </div>
        </>
    );
}
