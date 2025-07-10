import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import Navigation from '@/components/landing-page/navigation-bar';
import Hero from '@/components/landing-page/hero';
import HowItWorks from '@/components/landing-page/how-it-works';
import Features from '@/components/landing-page/features ';
import Teachers from '@/components/landing-page/teachers';
import MemorizeQuran from '@/components/landing-page/memorize-quran';
import Enroll from '@/components/landing-page/enroll';
import Testimonials from '@/components/landing-page/testimonials';
import BecomeTeacher from '@/components/landing-page/become-a-teacher';
import FAQ from '@/components/landing-page/faq';
import DownloadApp from '@/components/landing-page/download-app';
import CallToAction from '@/components/landing-page/call-to-action';
import Footer from '@/components/landing-page/footer';

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="Welcome" />
            <Navigation auth={auth} />
            <Hero />
            <Features />
            <HowItWorks />
            <Teachers />
            <MemorizeQuran />
            <Enroll />
            <Testimonials />
            <BecomeTeacher />
            <FAQ />
            <DownloadApp />
            <CallToAction />
            <Footer />
        </>
    );
}