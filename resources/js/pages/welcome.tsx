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

interface WelcomeProps extends SharedData {
    teachers: Array<{
        id: number;
        name: string;
        specialization: string;
        image: string;
        initials: string;
        rating: number;
        reviews: number;
        yearsExp: number;
    }>;
    subscriptionPlans: Array<{
        id: number;
        name: string;
        description?: string;
        price_naira: number;
        price_dollar: number;
        billing_cycle: 'monthly' | 'quarterly' | 'biannually' | 'annually';
        duration_months: number;
        features?: string[];
        tags?: string[];
        image_path?: string;
        is_active: boolean;
    }>;
    faqs: Array<{
        id: number;
        title: string;
        content: string;
        status: string;
        order_index: number;
    }>;
}

export default function Welcome() {
    const { auth, teachers, subscriptionPlans, faqs } = usePage<WelcomeProps>().props;

    return (
        <>
            <Head title="Welcome" />
            <Navigation auth={auth} />
            <Hero />
            <Features />
            <HowItWorks />
            <Teachers teachers={teachers} />
            <MemorizeQuran />
            <Enroll subscriptionPlans={subscriptionPlans} />
            <Testimonials />
            <BecomeTeacher />
            <FAQ faqs={faqs} />
            <DownloadApp />
            <CallToAction />
            <Footer />
        </>
    );
}