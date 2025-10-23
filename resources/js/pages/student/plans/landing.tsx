/**
 * 🎨 FIGMA DESIGN REFERENCE
 * 
 * Component: Student Plans Landing Page
 * Figma URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAQUEST?node-id=1422-80415
 * 
 * 📏 EXACT SPECIFICATIONS FROM IMAGE:
 * - Teal gradient background (darker at top, lighter at bottom)
 * - White content area with rounded top corners
 * - Key benefits as numbered white pills with dark text
 * - Three memorization plan cards with white background
 * - "View Memorization Plans" (beige button) and "Match Me" (outlined button)
 * 
 * 📱 RESPONSIVE: Mobile-first approach
 * 🎯 STATES: Default state with hover effects on buttons
 */

import React from 'react';
import { Head, Link } from '@inertiajs/react';
import StudentLayout from '@/layouts/student/student-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { User, Subscription } from '@/types';

interface PlansLandingProps {
    user: User;
    activePlan?: Subscription;
}

export default function PlansLanding({ user, activePlan }: PlansLandingProps) {
    return (
        <StudentLayout pageTitle="Quran Memorization Plans">
            <Head title="Quran Memorization Plans" />
            
            {/* Page Background */}
            <div className="min-h-screen bg-gray-50">
                {/* Main Content Container with Teal Gradient Background */}
                <div className="bg-gradient-to-b from-[#2CB1A4] via-[#68C2AF] to-[#A1D2B9] rounded-3xl min-h-[80vh] relative rounded-b-3xl">
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
                    
                    <div className="max-w-4xl mx-auto px-6 py-12 relative z-10">
                        {/* Header Section */}
                        <div className="mb-12">
                            <h1 className="text-4xl md:text-5xl font-bold mb-4 leading-tight bg-gradient-to-l from-[#FFFFFF] to-[#F3E5C3] bg-clip-text text-transparent">
                                Enroll in Our Quran Memorization Plans Today!
                            </h1>
                            <p className="text-xl text-white/90 mb-8">
                                Full Quran, Half Quran, or Juz' Amma - Tailored Learning for Every Student.
                            </p>
                            
                            {/* Separator Line */}
                            <div className="w-full h-px bg-[#F3E5C3] mb-8"></div>
                        </div>

                        {/* Key Benefits Section */}
                        <div className="mb-12">
                            <h2 className="text-2xl bg-gradient-to-l from-[#FFFFFF] to-[#F3E5C3] bg-clip-text text-transparent mb-6">
                                Key Benefits
                            </h2>
                            <div className="space-y-4">
                                <div className="bg-[#FFF8E7] rounded-2xl px-6 py-3 w-fit shadow-lg">
                                    <span className="text-[#338078] font-semibold text-lg">
                                        <span className="font-bold text-gray-400 pr-4">01</span> Learn at your child's pace
                                    </span>
                                </div>
                                <div className="bg-[#FFF8E7] rounded-2xl px-6 py-3 w-fit shadow-lg">
                                    <span className="text-[#338078] font-semibold text-lg">
                                        <span className="font-bold text-gray-400 pr-4">02</span> Certified Quran teachers
                                    </span>
                                </div>
                                <div className="bg-[#FFF8E7] rounded-2xl px-6 py-3 w-fit shadow-lg">
                                    <span className="text-[#338078] font-semibold text-lg">
                                        <span className="font-bold text-gray-400 pr-4">03</span> Earn a certificate upon completion
                                    </span>
                                </div>
                                <div className="bg-[#FFF8E7] rounded-2xl px-6 py-3 w-fit shadow-lg">
                                    <span className="text-[#338078] font-semibold text-lg">
                                        <span className="font-bold text-gray-400 pr-4">04</span> Progress tracking & parent updates
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Memorization Plans Cards */}
                        <div className="space-y-6 mb-12">
                            {/* Full Quran Memorization */}
                            <Card className="bg-white shadow-lg rounded-3xl border border-[#338078]">
                                <CardContent>
                                    <h3 className="text-2xl font-bold bg-gradient-to-l from-[#0A1A18] to-[#338078] bg-clip-text text-transparent mb-3">
                                        Full Qur'an Memorization
                                    </h3>
                                    <p className="text-gray-600 leading-relaxed">
                                        Complete the journey - Memorize the entire qur'an with expert guidance. 
                                        Include weekly reviews, progress tracking, and a certificate (Al-Ijaaza) of completion.
                                    </p>
                                </CardContent>
                            </Card>

                            {/* Half Quran Memorization */}
                            <Card className="bg-white shadow-lg rounded-3xl border border-[#338076">
                                <CardContent>
                                    <h3 className="text-2xl font-bold bg-gradient-to-l from-[#0A1A18] to-[#338078] bg-clip-text text-transparent mb-3">
                                        Half Qur'an Memorization
                                    </h3>
                                    <p className="text-gray-600 leading-relaxed">
                                        Memorization Half of the Qur'an - A balanced and achievable goal for 
                                        dedicated students. Structured plan with mid-point certification.
                                    </p>
                                </CardContent>
                            </Card>

                            {/* Juz Amma Memorization */}
                            <Card className="bg-white shadow-lg rounded-3xl border border-[#338076">
                                <CardContent>
                                    <h3 className="text-2xl font-bold bg-gradient-to-l from-[#0A1A18] to-[#338078] bg-clip-text text-transparent mb-3">
                                        Juz' Amma Memorization
                                    </h3>
                                    <p className="text-gray-600 leading-relaxed">
                                        Begin your memorization Journey with Juz' Amma - Perfect for beginners 
                                        and younger learners. Build a strong foundation in recitation.
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>

                {/* Action Buttons Section - Outside the teal background */}
                <div className="bg-gray-50 px-6 py-8">
                    <div className="max-w-4xl mx-auto flex flex-col sm:flex-row gap-4 items-start justify-start">
                        <Button 
                            asChild
                            size="lg"
                            className="bg-[#F3E5C3] hover:bg-[#F3E5C3] text-[#338078] font-semibold px-8 py-4 rounded-full text-lg shadow-lg hover:shadow-xl transition-all duration-200 cursor-pointer"
                            disabled={!!activePlan}
                        >
                            <Link href="/student/plans">
                                View Memorization Plans
                            </Link>
                        </Button>
                        
                        <div className="flex items-center space-x-3">
                            <span className="text-gray-600 text-sm">Not sure?</span>
                            <Button 
                                variant="outline"
                                size="lg"
                                className="border-2 border-[#2C7870] text-[#2C7870] hover:bg-[#2C7870] hover:text-white px-6 py-3 rounded-full transition-all duration-200 cursor-pointer"
                            >
                                Match Me
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Active Subscription Notice */}
                {activePlan && (
                    <div className="bg-gray-50 px-6 pb-8">
                        <div className="max-w-4xl mx-auto">
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