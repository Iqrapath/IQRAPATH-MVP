import React from 'react';
import Navigation from '@/components/blog/navigation-bar';
import Hero from '@/components/blog/hero';
import Posts from '@/components/blog/posts';
import Footer from '@/components/blog/footer';
import { Head } from '@inertiajs/react';

const Blog: React.FC = () => {
  return (
    <>
    <Head title="Blog" />
    <div className="bg-[#FDFDFC] min-h-screen flex flex-col">
      <Navigation />
      <main className="flex-grow">
        <Hero />
        <Posts />
      </main>
      <Footer />
    </div>
    </>
  );
};

export default Blog;