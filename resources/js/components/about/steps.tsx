import React from 'react';
import { Link } from '@inertiajs/react';

const Steps: React.FC = () => {
  return (
    <section className="py-60 bg-white overflow-hidden">
      <div className="container mx-auto px-4 sm:px-6 lg:px-8">
        <div className="max-w-6xl mx-auto">
          {/* Section Heading */}
          <h2 className="text-3xl md:text-4xl font-bold text-gray-300 mb-16 relative">
            For Student
            <div className="absolute w-20 h-1 bg-[#2B6B65] bottom-0 left-0 mt-2"></div>
          </h2>

          {/* Steps Layout */}
          <div className="relative min-h-[1000px]">
            {/* Step 1 Card - Top Left */}
            <div className="absolute left-0 top-0 w-64 sm:w-72 md:w-80 z-10">
              <div className="relative">
                {/* Phone Background */}
                <img src="/assets/images/how-it-works/Rectangle.png" alt="Step 1" className="w-full" />

                {/* Phone Content */}
                <div className="absolute inset-0 pt-12 px-6 flex flex-col">
                  {/* Teacher Cards */}
                  <div>
                    <img src="/assets/images/how-it-works/Frame2.png" alt="Booking Interface" className="w-auto h-auto object-contain" />
                  </div>
                </div>
              </div>
            </div>

            {/* Step 2 Card - Middle */}
            <div className="absolute left-1/2 -translate-x-1/2 top-[35%] w-64 sm:w-72 md:w-80 z-10">
              <div className="relative">
                {/* Phone Background */}
                <img src="/assets/images/how-it-works/Rectangle.png" alt="Step 2" className="w-full" />

                {/* Phone Content */}
                <div className="absolute inset-0 pt-12 px-6 flex flex-col">
                  {/* Booking Interface Mockup */}
                  <div>
                    <img src="/assets/images/how-it-works/Frame2.png" alt="Booking Interface" className="w-auto h-auto object-contain" />
                  </div>
                </div>
              </div>
            </div>

            {/* Step 3 Card - Bottom Right */}
            <div className="absolute right-0 top-[70%] w-64 sm:w-72 md:w-80 z-10">
              <div className="relative">
                {/* Phone Background */}
                <img src="/assets/images/how-it-works/Rectangle.png" alt="Step 3" className="w-full" />

                {/* Phone Content */}
                <div className="absolute inset-0 pt-12 px-6 flex flex-col">
                  {/* Learning Interface Mockup */}
                  <div>
                    <img src="/assets/images/how-it-works/Frame2.png" alt="Learning Interface" className="w-auto h-auto object-contain" />
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Call To Action Button */}
          <div className="mt-32 text-center relative z-10">
            <Link href="/find-teacher" className="inline-block bg-[#2B6B65] text-white py-3 px-8 rounded-full font-medium hover:bg-[#235750] transition-colors shadow-md">
              Find a Teacher Now
            </Link>
          </div>
        </div>
        <div className="max-w-6xl mx-auto">
          {/* Section Heading */}
          <h2 className="text-3xl md:text-4xl font-bold text-gray-300 mb-16 relative">
            For Student
            <div className="absolute w-20 h-1 bg-[#2B6B65] bottom-0 left-0 mt-2"></div>
          </h2>

          {/* Steps Layout */}
          <div className="relative min-h-[1000px]">
            {/* Step 1 Card - Top Left */}
            <div className="absolute left-0 top-0 w-64 sm:w-72 md:w-80 z-10">
              <div className="relative">
                {/* Phone Background */}
                <img src="/assets/images/how-it-works/Rectangle.png" alt="Step 1" className="w-full" />

                {/* Phone Content */}
                <div className="absolute inset-0 pt-12 px-6 flex flex-col">
                  {/* Teacher Cards */}
                  <div>
                    <img src="/assets/images/how-it-works/Frame2.png" alt="Booking Interface" className="w-auto h-auto object-contain" />
                  </div>
                </div>
              </div>
            </div>

            {/* Step 2 Card - Middle */}
            <div className="absolute left-1/2 -translate-x-1/2 top-[35%] w-64 sm:w-72 md:w-80 z-10">
              <div className="relative">
                {/* Phone Background */}
                <img src="/assets/images/how-it-works/Rectangle.png" alt="Step 2" className="w-full" />

                {/* Phone Content */}
                <div className="absolute inset-0 pt-12 px-6 flex flex-col">
                  {/* Booking Interface Mockup */}
                  <div>
                    <img src="/assets/images/how-it-works/Frame2.png" alt="Booking Interface" className="w-auto h-auto object-contain" />
                  </div>
                </div>
              </div>
            </div>

            {/* Step 3 Card - Bottom Right */}
            <div className="absolute right-0 top-[70%] w-64 sm:w-72 md:w-80 z-10">
              <div className="relative">
                {/* Phone Background */}
                <img src="/assets/images/how-it-works/Rectangle.png" alt="Step 3" className="w-full" />

                {/* Phone Content */}
                <div className="absolute inset-0 pt-12 px-6 flex flex-col">
                  {/* Learning Interface Mockup */}
                  <div>
                    <img src="/assets/images/how-it-works/Frame2.png" alt="Learning Interface" className="w-auto h-auto object-contain" />
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Call To Action Button */}
          <div className="mt-32 text-center relative z-10">
            <Link href="/find-teacher" className="inline-block bg-[#2B6B65] text-white py-3 px-8 rounded-full font-medium hover:bg-[#235750] transition-colors shadow-md">
              Find a Teacher Now
            </Link>
          </div>
        </div>
      </div>
    </section>
  );
};

export default Steps;
