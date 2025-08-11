import React from 'react';

const Hero: React.FC = () => {
  return (
    <section className="bg-gradient-to-r from-[#F5F9F9] to-[#EAF5F3] py-24 sm:py-24">
      <div className="container mx-auto px-4 sm:px-6 lg:px-8 ">
        <div className="max-w-4xl mx-auto text-center">
          {/* Title */}
          <h1 className="text-4xl sm:text-5xl md:text-6xl font-bold text-[#2B6B65] mb-6">
            About Us
          </h1>

          {/* Subtitle */}
          <p className="text-lg sm:text-xl text-[#2B6B65] leading-relaxed">
            Connecting students with expert Quran teachers worldwide.
          </p>
        </div>
      </div>
    </section>
  );
};

export default Hero;
