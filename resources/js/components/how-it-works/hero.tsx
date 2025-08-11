import React from 'react';

const Hero: React.FC = () => {
  return (
    <div className="w-full">
      {/* Top white section */}
      <div className="bg-gradient-to-r from-[#F5F9F9] to-[#EAF5F3] pt-24 pb-8">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="max-w-5xl mx-auto pl-4 sm:pl-12 md:pl-16 lg:pl-10">
            {/* Title */}
            <h1 className="text-2xl md:text-5xl lg:text-6xl font-bold text-[#2B6B65] leading-tight">
              How
              <br />
              It Works ?
            </h1>
          </div>
        </div>
      </div>

      {/* Mint green section */}
      <div className="bg-gradient-to-r  to-[#338078]  py-6">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="max-w-5xl mx-auto pl-4 sm:pl-12 md:pl-16 lg:pl-10">
            <h2 className="text-2xl md:text-3xl font-medium text-[#2B6B65]">
              Find the best Quran teachers or start teaching today!
            </h2>
          </div>
        </div>
      </div>

      {/* Dark teal section */}
      <div className="bg-[#2B6B65] py-8">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="max-w-5xl mx-auto pl-4 sm:pl-12 md:pl-16 lg:pl-10">
            <p className="text-white text-base md:text-lg leading-relaxed">
              Our platform is designed to match students with certified and experienced teachers, ensuring a
              personalized and effective learning experience. Whether you're a beginner, memorizing the Quran, or
              improving your Tajweed, our step-by-step process makes it simple to start your journey.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Hero;
