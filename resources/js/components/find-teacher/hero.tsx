import React from 'react';

const Hero: React.FC = () => {
  return (
    <section className="bg-gradient-to-r from-[#FEF9E5] via-[white] to-[white] relative">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid lg:grid-cols-2 items-center gap-16 py-20">
          {/* Left Content */}
          <div className="relative mt-32">
            <div className="space-y-1">
              <h1 className="text-[35px] leading-tight">
                <span className="text-[#2B6B65] font-medium">Learn Quran with the </span>
                <span className="text-[#64748B] font-medium">Right Teacher</span>
              </h1>
              <p className="text-[32px] text-[#2B6B65] font-medium">
                Anytime, Anywhere.
              </p>
            </div>

            <div className="flex items-center gap-4 mt-4">
              <a href="#" className="px-7 py-3 bg-[#2B6B65] text-white text-base font-medium rounded-full hover:bg-[#235750] transition-colors">
                Browse Teachers
              </a>
              <a href="#" className="px-7 py-3 border border-[#2B6B65] text-[#2B6B65] text-base font-medium rounded-full hover:bg-[#2B6B65] hover:text-white transition-colors">
                Let Us Match You
              </a>
            </div>
          </div>

          {/* Right Image */}
          <div className="relative">
            <img src="/assets/images/hero/teacher.png" alt="Quran Teacher" className="w-full h-auto max-w-xl mx-auto" />
          </div>
        </div>
      </div>
    </section>
  );
};

export default Hero;
