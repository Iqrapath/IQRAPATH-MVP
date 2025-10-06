import React from 'react';

export default function MemorizeQuran() {
  return (
    <section className="py-16 md:py-24 bg-white relative overflow-hidden">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-10">
        <div className="text-start max-w-1xl mx-auto">
          <h2 className="text-2xl sm:text-3xl md:text-4xl lg:text-4xl font-bold text-[#2F8D8C] mb-4">
            Want your kids to be an Hafiz in 6months
          </h2>
          <p className="text-sm sm:text-base text-gray-600 mb-8 sm:mb-12 md:mb-16">
            Full Quran, Half Quran, or Juz' Amma – Tailored Learning for Every Student.
          </p>
        </div>

        <div className="flex flex-wrap sm:flex-nowrap justify-start gap-2 sm:gap-1 md:gap-2 pb-6 overflow-x-hidden sm:overflow-x-visible">
          <div className="transform rotate-8 mx-0.5 sm:mx-1 md:mx-2 flex-shrink-0">
            <div className="flex items-center bg-[#FFF8E7] px-2 sm:px-3 md:px-4 py-2 sm:py-2.5 rounded-full shadow-sm border border-[#F0EBE1]">
              <span className="bg-[#F8F3E7] text-[#2F8D8C] font-bold rounded-full w-5 h-5 sm:w-6 sm:h-6 flex items-center justify-center mr-1 sm:mr-2 text-xs">
                01
              </span>
              <span className="text-gray-700 text-xs sm:text-sm whitespace-nowrap">Learn at your child's pace</span>
            </div>
          </div>

          <div className="transform rotate-8 mx-0.5 sm:mx-1 md:mx-2 flex-shrink-0">
            <div className="flex items-center bg-[#FFF8E7] px-2 sm:px-3 md:px-4 py-2 sm:py-2.5 rounded-full shadow-sm border border-[#F0EBE1]">
              <span className="bg-[#F8F3E7] text-[#2F8D8C] font-bold rounded-full w-5 h-5 sm:w-6 sm:h-6 flex items-center justify-center mr-1 sm:mr-2 text-xs">
                03
              </span>
              <span className="text-gray-700 text-xs sm:text-sm whitespace-nowrap">Earn a certificate upon completion</span>
            </div>
          </div>

          <div className="transform rotate-8 mx-0.5 sm:mx-1 md:mx-2 flex-shrink-0">
            <div className="flex items-center bg-[#FFF8E7] px-2 sm:px-3 md:px-4 py-2 sm:py-2.5 rounded-full shadow-sm border border-[#F0EBE1]">
              <span className="bg-[#F8F3E7] text-[#2F8D8C] font-bold rounded-full w-5 h-5 sm:w-6 sm:h-6 flex items-center justify-center mr-1 sm:mr-2 text-xs">
                02
              </span>
              <span className="text-gray-700 text-xs sm:text-sm whitespace-nowrap">Certified Quran teachers</span>
            </div>
          </div>

          <div className="transform rotate-8 mx-0.5 sm:mx-1 md:mx-2 flex-shrink-0">
            <div className="flex items-center bg-[#FFF8E7] px-2 sm:px-3 md:px-4 py-2 sm:py-2.5 rounded-full shadow-sm border border-[#F0EBE1]">
              <span className="bg-[#F8F3E7] text-[#2F8D8C] font-bold rounded-full w-5 h-5 sm:w-6 sm:h-6 flex items-center justify-center mr-1 sm:mr-2 text-xs">
                04
              </span>
              <span className="text-gray-700 text-xs sm:text-sm whitespace-nowrap">Progress tracking & parent updates</span>
            </div>
          </div>
        </div>
      </div>

      {/* Arrow that overlaps to the next section */}
      <div className="absolute bottom-0 left-1/2 transform -translate-x-1/2 translate-y-1/2 z-10">
        <img
          src="/assets/images/landing/Arrow.png"
          alt="Arrow pointing down"
          className="w-16 sm:w-20 md:w-25 h-auto animate-bounce"
        />
      </div>
    </section>
  );
}