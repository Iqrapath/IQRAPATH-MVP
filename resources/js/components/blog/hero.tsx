import React from 'react';

const Hero: React.FC = () => {
  return (
    <section className="bg-white py-30 border-b border-gray-200">
      <div className="container mx-auto px-4 sm:px-6 lg:px-8 max-w-5xl text-center">
        <h1 className="text-3xl md:text-4xl font-bold mb-3 text-center">
          <span className="text-[#2B6B65]">Quran Learning Insights,</span>
          <span className="text-gray-800"> Tips & Stories</span>
        </h1>

        <p className="text-gray-600 text-base md:text-lg max-w-2xl mx-auto">
          Explore articles from teachers, students, and parents to enrich your Quran learning journey.
        </p>
      </div>
    </section>
  );
};

export default Hero;
