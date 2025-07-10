import React from 'react';

export default function HowItWorks() {
  return (
    <section className="bg-white py-16 md:py-1 overflow-hidden relative">
      {/* Background pattern */}
      <div className="absolute inset-0 w-full h-full pointer-events-none opacity-20">
        <img
          src="/assets/images/landing/bg-element.png"
          alt=""
          className="w-full h-full object-cover"
          style={{ objectPosition: 'left top' }}
        />
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-10 relative">
        {/* Top section */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-2 relative z-20 mt-10 md:mt-20">
          {/* Left side - Title */}
          <div className="flex flex-col justify-center">
            <h2 className="text-4xl md:text-5xl font-bold text-[#2F8D8C] mb-6">
              How<br />
              it Works
            </h2>
          </div>

          {/* Right side - Description */}
          <div className="flex flex-col justify-center">
            <p className="text-gray-600 text-lg">
              Finding the perfect Quran tutor has never been easier. Our platform is designed to
              match students with certified and experienced teachers, ensuring a personalized and
              effective learning experience.
            </p>
          </div>
        </div>
      </div>

      {/* Bottom section - Steps */}
      <div className="mt-10 md:mt-10 relative z-20 w-full" style={{
        background: 'linear-gradient(to right, #FFFBF9, #EFFDFB, #E4FFFC)'
      }}>
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-20 py-8 md:py-30">
          <div className="grid grid-cols-1 md:grid-cols-4 md:gap-0 relative">
            {/* Step 1 - UP */}
            <div className="flex flex-col items-start mt-0 md:mt-0 md:items-center md:px-2">
              <div className="relative">
                <div className="w-28 h-28 rounded-full bg-[#F5F5F5] flex items-center justify-center absolute -top-2 -left-2"></div>
                <div className="w-24 h-24 rounded-full bg-gradient-to-br from-[#F3E5C3] to-[#317B74] flex items-center justify-center text-[#2F4F4C] text-3xl font-bold mb-4 shadow-md relative z-10">
                  <span className="text-white">01</span>
                </div>
              </div>
              <h3 className="text-xl font-bold text-[#2F4F4C] mb-1">
                Sign Up
              </h3>
              <div className="flex items-center">
                <svg className="w-5 h-5 text-[#2F8D8C] inline mr-1 mb-6" viewBox="0 0 20 20" fill="currentColor">
                  <path fillRule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clipRule="evenodd" />
                </svg>
                <p className="text-gray-500 text-left inline">
                  Create your free account<br />in minutes.
                </p>
              </div>
            </div>

            {/* Connecting line 1 */}
            <div className="hidden md:block absolute left-[13%] top-[50px] w-[25%]">
              <img src="/assets/images/landing/line1.png" alt="" className="w-full h-[90px] object-contain" />
            </div>

            {/* Step 2 - DOWN */}
            <div className="flex flex-col items-start mt-6 md:mt-16 md:items-center md:px-2">
              <div className="relative">
                <div className="w-28 h-28 rounded-full bg-[#F5F5F5] flex items-center justify-center absolute -top-2 -left-2"></div>
                <div className="w-24 h-24 rounded-full bg-gradient-to-br from-[#F3E5C3] to-[#317B74] flex items-center justify-center text-[#2F4F4C] text-3xl font-bold mb-4 shadow-md relative z-10">
                  <span className="text-white">02</span>
                </div>
              </div>
              <h3 className="text-xl font-bold text-[#2F4F4C] mb-1">
                Find a Teacher
              </h3>
              <div className="flex items-center">
                <svg className="w-5 h-5 text-[#2F8D8C] inline mr-1 mb-6" viewBox="0 0 20 20" fill="currentColor">
                  <path fillRule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clipRule="evenodd" />
                </svg>
                <p className="text-gray-500 text-left inline">
                  Browse our certified tutors<br />& choose the best fit.
                </p>
              </div>
            </div>

            {/* Connecting line 2 */}
            <div className="hidden md:block absolute left-[37%] top-[50px] w-[25%]">
              <img src="/assets/images/landing/line2.png" alt="" className="w-full h-[90px] object-contain" />
            </div>

            {/* Step 3 - UP */}
            <div className="flex flex-col items-start mt-6 md:mt-0 md:items-center md:px-2">
              <div className="relative">
                <div className="w-28 h-28 rounded-full bg-[#F5F5F5] flex items-center justify-center absolute -top-2 -left-2"></div>
                <div className="w-24 h-24 rounded-full bg-gradient-to-br from-[#F3E5C3] to-[#317B74] flex items-center justify-center text-[#2F4F4C] text-3xl font-bold mb-4 shadow-md relative z-10">
                  <span className="text-white">03</span>
                </div>
              </div>
              <h3 className="text-xl font-bold text-[#2F4F4C] mb-1">
                Book a Class
              </h3>
              <div className="flex items-center">
                <svg className="w-5 h-5 text-[#2F8D8C] inline mr-1 mb-6" viewBox="0 0 20 20" fill="currentColor">
                  <path fillRule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clipRule="evenodd" />
                </svg>
                <p className="text-gray-500 text-left inline">
                  Select a time that<br />suits you.
                </p>
              </div>
            </div>

            {/* Connecting line 3 */}
            <div className="hidden md:block absolute left-[62%] top-[50px] w-[25%]">
              <img src="/assets/images/landing/line3.png" alt="" className="w-full h-[90px] object-contain" />
            </div>

            {/* Step 4 - DOWN */}
            <div className="flex flex-col items-start mt-6 md:mt-16 md:items-center md:px-2">
              <div className="relative">
                <div className="w-28 h-28 rounded-full bg-[#F5F5F5] flex items-center justify-center absolute -top-2 -left-2"></div>
                <div className="w-24 h-24 rounded-full bg-gradient-to-br from-[#F3E5C3] to-[#317B74] flex items-center justify-center text-[#2F4F4C] text-3xl font-bold mb-4 shadow-md relative z-10">
                  <span className="text-white">04</span>
                </div>
              </div>
              <h3 className="text-xl font-bold text-[#2F4F4C] mb-1">
                Start Learning
              </h3>

              <div className="flex items-center">
                <svg className="w-5 h-5 text-[#2F8D8C] inline mr-1 mb-6" viewBox="0 0 20 20" fill="currentColor">
                  <path fillRule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clipRule="evenodd" />
                </svg>
                <p className="text-gray-500 text-left">
                Enjoy interactive Quran<br />lessons online.
              </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}