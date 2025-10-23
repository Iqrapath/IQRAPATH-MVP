/**
 * 🎨 FIGMA DESIGN REFERENCE
 * 
 * Component: Student Plans Index Page
 * Based on: Image design with teal gradient background and plan selection
 * 
 * 📏 EXACT SPECIFICATIONS FROM IMAGE:
 * - Teal gradient background (darker at top, lighter at bottom)
 * - White content area with rounded top corners
 * - Plan duration selector: All Plans, Monthly, Annual
 * - Three pricing cards with white background
 * - "Start with a Monthly Plan" buttons
 * 
 * 📱 RESPONSIVE: Mobile-first approach
 * 🎯 STATES: Default state with hover effects on buttons
 */

import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import StudentLayout from '@/layouts/student/student-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { SubscriptionPlan, Subscription, User } from '@/types';

interface PlansIndexProps {
    plans: SubscriptionPlan[];
    activePlan?: Subscription;
    walletBalance: {
        usd: number;
        ngn: number;
    };
    user: User;
}

export default function PlansIndex({
    plans,
    activePlan,
    walletBalance,
    user
}: PlansIndexProps) {
    const [billingCycle, setBillingCycle] = useState<'monthly' | 'annual'>('monthly');

    // Filter plans by billing cycle
    const filteredPlans = plans.filter(plan => plan.billing_cycle === billingCycle);

    const formatPrice = (plan: SubscriptionPlan, currency: 'USD' | 'NGN') => {
        const price = currency === 'USD' ? plan.price_dollar : plan.price_naira;
        const symbol = currency === 'USD' ? '$' : '₦';
        return `${symbol}${price.toLocaleString()}`;
    };

    // Use actual plans data from database - show all filtered plans
    const displayPlans = filteredPlans;

    return (
        <StudentLayout pageTitle="Choose plan for your kids">
            <Head title="Choose plan for your kids" />

            {/* Page Background */}
            <div className="">
                {/* Back Button */}
                {/* <div className="px-6 pt-6">
                    <Button variant="ghost" asChild className="text-[#2C7870]">
                        <Link href="/student/memorization-plans">
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Back to Memorization Plans
                        </Link>
                    </Button>
                </div> */}

                {/* Main Content Container with Teal Gradient Background */}
                <div className="bg-gradient-to-b from-[#2CB1A4] to-[#A1D2B9] rounded-3xl relative ">

                    {/* Decorative Image - Top Right Corner */}
                    <div className="absolute top-0 right-0">
                        <img 
                            src="/assets/images/quran-icon.png" 
                            alt="Quran decorative icon" 
                            className="w-full h-full object-contain"
                        />
                    </div>
                    
                    {/* Decorative Image - Center */}
                    <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full h-full">
                        <img 
                            src="/assets/images/Arabic_Calligraphy.png" 
                            alt="Quran center decorative icon" 
                            className="w-full h-full object-contain"
                        />
                    </div>
                    
                    <div className="max-w-6xl mx-auto px-6 py-12 relative z-10">
                        {/* Header Section */}
                        <div className="mb-12">
                            <h1 className="text-3xl md:text-4xl font-bold bg-gradient-to-l from-[#FFFFFF] to-[#F3E5C3] bg-clip-text text-transparent mb-4">
                                Choose plan for your kids
                            </h1>
                            <p className="text-lg text-white/90 mb-8">
                            Tailored Learning for Every Student.Full Quran, Half Quran, or Juz' Amma – Tailored Learning for Every Student.
                            </p>
                        </div>

                        {/* Plan Duration Selector */}
                        <div className="flex justify-center mb-12">
                            <div className="bg-white p-1 rounded-full flex gap-1">
                                <Button
                                    variant={billingCycle === 'monthly' ? 'default' : 'ghost'}
                                    size="lg"
                                    onClick={() => setBillingCycle('monthly')}
                                    className={`px-8 py-3 rounded-full ${
                                        billingCycle === 'monthly'
                                            ? 'bg-[#2C7870] text-white'
                                            : 'text-gray-600 hover:bg-gray-100'
                                    }`}
                                >
                                    Monthly Plan
                                </Button>
                                <Button
                                    variant={billingCycle === 'annual' ? 'default' : 'ghost'}
                                    size="lg"
                                    onClick={() => setBillingCycle('annual')}
                                    className={`px-8 py-3 rounded-full relative ${
                                        billingCycle === 'annual'
                                            ? 'bg-[#2C7870] text-white'
                                            : 'text-gray-600 hover:bg-gray-100'
                                    }`}
                                >
                                    Yearly Plan
                                    <Badge className="absolute -top-2 -right-2 bg-[#10B981] text-white text-xs px-2 py-1">
                                        Bonus
                                    </Badge>
                                </Button>
                            </div>
                        </div>

                        {/* Pricing Cards */}
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            {displayPlans.map((plan, index) => (
                                <div key={plan.id} className="bg-gradient-to-tl from-[#FFFFFF]/40 to-[#FFFFFF]/80 border-0 shadow-lg rounded-3xl p-4">
                                    {/* Pricing */}
                                    <div className="mb-4">
                                        <div className="text-2xl font-bold text-[#333333] mb-1">
                                            {plan.price_dollar > 0 ? formatPrice(plan, 'USD') : formatPrice(plan, 'NGN')} <span className="text-xs text-[#666666]"> /per {plan.billing_cycle}</span>
                                        </div>
                                    </div>

                                    {/* Plan Name */}
                                    <h3 className=" text-[#333333] mb-4">
                                        {plan.name}
                                    </h3>

                                    {/* Select Plan Button */}
                                    <Button
                                        className="w-full bg-[#2C7870] hover:bg-[#236158] text-white px-8 py-4 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-200 cursor-pointer"
                                        disabled={!!activePlan}
                                        onClick={() => {
                                            if (!activePlan) {
                                                window.location.href = `/student/plans/${plan.id}`;
                                            }
                                        }}
                                    >
                                        {billingCycle === 'monthly' 
                                            ? 'Start with a Monthly Plan'
                                            : 'Start with an Annual Plan'}
                                    </Button>
                                </div>
                            ))}
                        </div>

                        {/* Empty State */}
                        {displayPlans.length === 0 && (
                            <div className="flex items-center justify-center py-12">
                                <div className="bg-white/80 backdrop-blur rounded-3xl shadow-lg px-8 py-10 text-center max-w-xl w-full">
                                    <h3 className="text-xl font-semibold text-[#1F2937] mb-2">
                                        No {billingCycle} plans available
                                    </h3>
                                    <p className="text-[#4B5563] mb-6">
                                        Please check back later or choose a different billing cycle.
                                    </p>
                                    <div className="flex items-center justify-center gap-2">
                                        <Button
                                            onClick={() => setBillingCycle('monthly')}
                                            className={`rounded-full px-6 ${billingCycle==='monthly' ? 'bg-[#2C7870] hover:bg-[#236158] text-white' : 'bg-white text-[#2C7870] border border-[#2C7870]'}`}
                                        >
                                            Monthly Plans
                                        </Button>
                                        <Button
                                            onClick={() => setBillingCycle('annual')}
                                            className={`rounded-full px-6 ${billingCycle==='annual' ? 'bg-[#2C7870] hover:bg-[#236158] text-white' : 'bg-white text-[#2C7870] border border-[#2C7870]'}`}
                                        >
                                            Annual Plans
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Active Subscription Notice */}
                {activePlan && (
                    <div className="bg-gray-50 px-6 py-8">
                        <div className="max-w-6xl mx-auto">
                            <Card className="bg-white border-0 shadow-lg max-w-md mx-auto">
                                <CardContent className="p-4">
                                    <div className="flex items-center justify-center space-x-2 text-[#2C7870]">
                                        <span className="font-medium">
                                            You have an active {activePlan.plan?.name} subscription
                                        </span>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                )}
            </div>
        </StudentLayout>
    );
}