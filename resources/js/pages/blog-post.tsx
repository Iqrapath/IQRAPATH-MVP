import React from 'react';
import Navigation from '@/components/blog/navigation-bar';
import PostDetail from '@/components/blog/post-detail';
import Footer from '@/components/blog/footer';
import { Head } from '@inertiajs/react';

const BlogPost: React.FC = () => {
  return (
    <>
      <Head title="Blog Post - IqraQuest" />
      <div className="bg-[#FDFDFC] min-h-screen flex flex-col">
        <Navigation />
        <main className="flex-grow">
          <PostDetail />
        </main>
        <Footer />
      </div>
    </>
  );
};

export default BlogPost;
