import React from 'react';
import { Link } from '@inertiajs/react';

export default function EnrollSection() {
  return (
    <section className="bg-[#F1FBEC] py-16 md:py-6 relative overflow-hidden">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-1">
        <div className="flex flex-col md:flex-row items-center justify-between">
          {/* Left side - Image */}
          <div className="w-full md:w-1/2 mb-10 md:mb-0">
            <img
              src="/assets/images/landing/quran-boy.png"
              alt="Boy reading Quran"
              className="max-w-full h-100 mx-auto"
            />
          </div>

          {/* Right side - Content */}
          <div className="w-full md:w-1/2 md:pl-2">

            <h2 className="text-4xl md:text-4xl font-bold text-[#2F8D8C] mb-6">
              Enroll in Our Quran Memorization Plans Today!
            </h2>

            <p className="text-gray-700 mb-10 text-lg">
              Full Quran, Half Quran, or Juz' Amma – Tailored Learning <br /> for Every Student.
            </p>

            <div className="flex flex-wrap items-center gap-6">
              <Link
                href="/memorization-plans"
                className="inline-block bg-[#2F8D8C] text-white font-medium px-8 py-4 rounded-full hover:bg-[#267373] transition-colors shadow-md"
              >
                View Memorization Plans
              </Link>

              <div className="flex items-center">
                <span className="text-gray-600 mr-4">Not sure?</span>
                <Link
                  href="/match-me"
                  className="inline-block border-2 border-[#2F8D8C] text-[#2F8D8C] font-medium px-8 py-3.5 rounded-full hover:bg-[#2F8D8C] hover:text-white transition-colors"
                >
                  Match Me
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}