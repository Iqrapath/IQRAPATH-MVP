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
import { Head, Link, router, usePage } from '@inertiajs/react';
import StudentLayout from '@/layouts/student/student-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { ArrowLeft } from 'lucide-react';
import { SubscriptionPlan, Subscription, User } from '@/types';
import { useCurrency } from '@/contexts/CurrencyContext';
import { toast } from 'sonner';

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

    // Use global currency context
    const { selectedCurrency: globalCurrency, currencySymbols } = useCurrency();
    const selectedCurrency = (globalCurrency === 'NGN' || globalCurrency === 'USD') ? globalCurrency : 'NGN';

    // Handle flash messages
    const { props } = usePage<any>();
    React.useEffect(() => {
        if (props.flash?.success) {
            toast.success(props.flash.success);
        }
        if (props.flash?.error) {
            toast.error(props.flash.error);
        }
    }, [props.flash]);

    // Filter plans by billing cycle
    const filteredPlans = plans.filter(plan => plan.billing_cycle === billingCycle);

    const formatPrice = (plan: SubscriptionPlan) => {
        const price = selectedCurrency === 'USD' ? plan.price_dollar : plan.price_naira;
        const symbol = currencySymbols[selectedCurrency as keyof typeof currencySymbols];
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
                                    className={`px-8 py-3 rounded-full ${billingCycle === 'monthly'
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
                                    className={`px-8 py-3 rounded-full relative ${billingCycle === 'annual'
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
                                            {formatPrice(plan)} <span className="text-xs text-[#666666]"> /per {plan.billing_cycle}</span>
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
                                            className={`rounded-full px-6 ${billingCycle === 'monthly' ? 'bg-[#2C7870] hover:bg-[#236158] text-white' : 'bg-white text-[#2C7870] border border-[#2C7870]'}`}
                                        >
                                            Monthly Plans
                                        </Button>
                                        <Button
                                            onClick={() => setBillingCycle('annual')}
                                            className={`rounded-full px-6 ${billingCycle === 'annual' ? 'bg-[#2C7870] hover:bg-[#236158] text-white' : 'bg-white text-[#2C7870] border border-[#2C7870]'}`}
                                        >
                                            Annual Plans
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Active Subscription Management */}
                {activePlan && (
                    <div className="bg-gradient-to-br from-[#14B8A6]/5 to-[#14B8A6]/10 px-6 py-12">
                        <div className="max-w-6xl mx-auto">
                            <div className="text-center mb-8">
                                <h2 className="text-2xl font-bold text-[#1E293B] mb-2">
                                    Manage Your Subscription
                                </h2>
                                <p className="text-[#64748B]">
                                    View and manage your active subscription details
                                </p>
                            </div>

                            <Card className="bg-white border border-[#14B8A6]/20 shadow-lg overflow-hidden max-w-3xl mx-auto">
                                <CardContent className="p-0">
                                    {/* Subscription Header */}
                                    <div className="bg-gradient-to-r from-[#14B8A6] to-[#0D9488] p-6 text-white">
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <div className="flex items-center gap-2 mb-2">
                                                    <h3 className="text-xl font-bold">
                                                        {activePlan.plan?.name}
                                                    </h3>
                                                    <Badge className="bg-white/20 text-white border-0">
                                                        {activePlan.status}
                                                    </Badge>
                                                </div>
                                                <p className="text-white/90 text-sm">
                                                    {activePlan.plan?.description || 'Your active memorization plan'}
                                                </p>
                                            </div>
                                            <div className="text-right">
                                                <div className="text-3xl font-bold">
                                                    {selectedCurrency === 'USD' 
                                                        ? `$${activePlan.plan?.price_dollar}` 
                                                        : `₦${activePlan.plan?.price_naira?.toLocaleString()}`}
                                                </div>
                                                <div className="text-white/80 text-sm">
                                                    per {activePlan.plan?.billing_cycle}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Subscription Details */}
                                    <div className="p-6 space-y-4">
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            {/* Start Date */}
                                            <div className="flex items-start gap-3">
                                                <div className="w-10 h-10 rounded-full bg-[#14B8A6]/10 flex items-center justify-center flex-shrink-0">
                                                    <svg className="w-5 h-5 text-[#14B8A6]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div className="text-sm text-[#64748B] mb-1">Start Date</div>
                                                    <div className="font-medium text-[#1E293B]">
                                                        {new Date(activePlan.start_date).toLocaleDateString('en-US', { 
                                                            month: 'long', 
                                                            day: 'numeric', 
                                                            year: 'numeric' 
                                                        })}
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Renewal Date */}
                                            {activePlan.end_date && (
                                                <div className="flex items-start gap-3">
                                                    <div className="w-10 h-10 rounded-full bg-[#14B8A6]/10 flex items-center justify-center flex-shrink-0">
                                                        <svg className="w-5 h-5 text-[#14B8A6]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <div className="text-sm text-[#64748B] mb-1">
                                                            {activePlan.auto_renew ? 'Next Renewal' : 'Expires On'}
                                                        </div>
                                                        <div className="font-medium text-[#1E293B]">
                                                            {new Date(activePlan.end_date).toLocaleDateString('en-US', { 
                                                                month: 'long', 
                                                                day: 'numeric', 
                                                                year: 'numeric' 
                                                            })}
                                                        </div>
                                                    </div>
                                                </div>
                                            )}

                                            {/* Auto Renewal Status */}
                                            <div className="flex items-start gap-3">
                                                <div className="w-10 h-10 rounded-full bg-[#14B8A6]/10 flex items-center justify-center flex-shrink-0">
                                                    <svg className="w-5 h-5 text-[#14B8A6]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div className="text-sm text-[#64748B] mb-1">Auto Renewal</div>
                                                    <div className="font-medium text-[#1E293B]">
                                                        {activePlan.auto_renew ? 'Enabled' : 'Disabled'}
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Subscription ID */}
                                            <div className="flex items-start gap-3">
                                                <div className="w-10 h-10 rounded-full bg-[#14B8A6]/10 flex items-center justify-center flex-shrink-0">
                                                    <svg className="w-5 h-5 text-[#14B8A6]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div className="text-sm text-[#64748B] mb-1">Subscription ID</div>
                                                    <div className="font-medium text-[#1E293B] font-mono text-sm">
                                                        #{activePlan.subscription_uuid?.substring(0, 12).toUpperCase()}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Auto-Renewal Toggle & Action Buttons */}
                                        <div className="pt-4 border-t border-gray-200 space-y-4">
                                            {/* Auto-Renewal Toggle */}
                                            <div className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                                <div className="flex-1">
                                                    <div className="font-medium text-[#1E293B] mb-1">
                                                        Auto-Renewal
                                                    </div>
                                                    <div className="text-sm text-[#64748B]">
                                                        {activePlan.auto_renew 
                                                            ? 'Your subscription will automatically renew' 
                                                            : 'Your subscription will expire at the end of the period'}
                                                    </div>
                                                </div>
                                                <Switch
                                                    checked={activePlan.auto_renew}
                                                    onCheckedChange={(checked) => {
                                                        router.patch(
                                                            route('student.subscriptions.auto-renewal', activePlan.subscription_uuid),
                                                            {},
                                                            {
                                                                preserveScroll: true,
                                                                onSuccess: () => {
                                                                    toast.success(`Auto-renewal ${checked ? 'enabled' : 'disabled'} successfully`);
                                                                },
                                                                onError: () => {
                                                                    toast.error('Failed to update auto-renewal setting');
                                                                }
                                                            }
                                                        );
                                                    }}
                                                    className="data-[state=checked]:bg-[#14B8A6]"
                                                />
                                            </div>

                                            {/* Action Buttons */}
                                            <div className="flex flex-wrap gap-3 justify-center">
                                                
                                                <Button
                                                    variant="outline"
                                                    className="border-gray-300 text-gray-700 hover:bg-gray-50"
                                                    onClick={() => {
                                                        router.visit(route('student.book-class'));
                                                    }}
                                                >
                                                    Schedule a Class
                                                </Button>

                                                <Button
                                                    variant="outline"
                                                    className="border-red-300 text-red-600 hover:bg-red-50"
                                                    onClick={() => {
                                                        if (confirm('Are you sure you want to cancel your subscription? This action cannot be undone.')) {
                                                            router.delete(
                                                                route('student.subscriptions.cancel', activePlan.subscription_uuid),
                                                                {
                                                                    onSuccess: () => {
                                                                        toast.success('Subscription cancelled successfully');
                                                                    },
                                                                    onError: () => {
                                                                        toast.error('Failed to cancel subscription');
                                                                    }
                                                                }
                                                            );
                                                        }
                                                    }}
                                                >
                                                    Cancel Subscription
                                                </Button>
                                            </div>
                                        </div>
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