import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import { type SharedData } from '@/types';
import Navigation from '@/components/find-teacher/navigation-bar';
import Hero from '@/components/find-teacher/hero';
import Teachers from '@/components/find-teacher/teachers';
import HowItWorks from '@/components/find-teacher/how-it-works';
import MatchTeacher from '@/components/find-teacher/match-teacher';
// import FAQ from '@/components/find-teacher/faq';
// import Download from '@/components/find-teacher/download';
import CallToAction from '@/components/find-teacher/call-to-action';
import Footer from '@/components/find-teacher/footer';

interface FindTeacherProps {
  teachers?: {
    data: any[];
    total: number;
    current_page: number;
    last_page: number;
  };
  subjects?: string[];
  languages?: string[];
  subjectTemplates?: Array<{
    id: number;
    name: string;
  }>;
  filters?: {
    search: string;
    subject: string;
    rating: string;
    budget: string;
    timePreference: string;
    language: string;
  };
}

const FindTeacher: React.FC<FindTeacherProps> = ({ teachers, subjects, languages, subjectTemplates, filters }) => {
  const { auth } = usePage<SharedData>().props;

  return (
    <>
      <Head title="Find a Teacher" />
      <div className="bg-[#FDFDFC] min-h-screen flex flex-col">
        <Navigation auth={auth} />
        <main className="flex-grow">
          <Hero />
          <Teachers teachers={teachers} subjects={subjects} languages={languages} filters={filters} />
          <HowItWorks />
          <MatchTeacher subjects={subjectTemplates} />
          {/* <FAQ /> */}
          {/* <Download /> */}
          <CallToAction />
        </main>
        <Footer />
      </div>
    </>
  );
};

export default FindTeacher;