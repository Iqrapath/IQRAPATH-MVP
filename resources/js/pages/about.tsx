import React from 'react';
import Navigation from '@/components/about/navigation-bar';
import Hero from '@/components/about/hero';
import AboutUs from '@/components/about/about-us';
// import Steps from '@/components/about/steps';
import Enroll from '@/components/about/enroll';
import Team from '@/components/about/team';
import Download from '@/components/about/download';
// import FAQ from '@/components/about/faq';
// import Testimonials from '@/components/about/testimonials';
// import BecomeTeacher from '@/components/about/become-teacher';
import CallToAction from '@/components/about/call-to-action';
import Footer from '@/components/about/footer';
import { Head } from '@inertiajs/react';

const About: React.FC = () => {
  return (
    <>
    <Head title="About Us" />
    <div className="bg-[#FDFDFC] min-h-screen flex flex-col">
      <Navigation />
      <main className="flex-grow">
        <Hero />
        <AboutUs />
        {/* <Steps /> */}
        <Enroll />
        {/* <Team /> */}
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

export default About;