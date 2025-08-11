import React from 'react';
import Navigation from '@/components/how-it-works/navigation-bar';
import Hero from '@/components/how-it-works/hero';
import Steps from '@/components/how-it-works/steps';
import Download from '@/components/how-it-works/download';
// import FAQ from '@/components/how-it-works/faq';
// import Testimonials from '@/components/how-it-works/testimonials';
// import BecomeTeacher from '@/components/how-it-works/become-teacher';
import CallToAction from '@/components/how-it-works/call-to-action';
import Footer from '@/components/how-it-works/footer';
import { Head } from '@inertiajs/react';

const HowItWorks: React.FC = () => {
  return (
    <>
    <Head title="How It Works" />
    <div className="bg-[#FDFDFC] min-h-screen flex flex-col">
      <Navigation />
      <main className="flex-grow">
        <Hero />
        <Steps />
        <Download />
        {/* <FAQ /> */}
        {/* <Testimonials /> */}
        {/* <BecomeTeacher /> */}
        <CallToAction />
      </main>
      <Footer />
    </div>
    </>
  );
};

export default HowItWorks;