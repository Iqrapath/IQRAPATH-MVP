import React from 'react';
import { Link } from '@inertiajs/react';

export default function CallToAction() {
  return (
    <section className="py-16 md:py-10 bg-[#FFF8E7] relative overflow-hidden">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex flex-col md:flex-row items-center justify-between">
          {/* Left side - Content */}
          <div className="w-full md:w-1/2 mb-10 md:mb-0 pr-0 md:pr-8">
            <h2 className="text-4xl md:text-4xl font-bold text-gray-600 mb-6">
              Start your Quran<br />
              Learning Journey <span className="text-[#2F8D8C]">Today</span>
            </h2>

            <p className="text-gray-600 mb-8 max-w-xl">
              Earn money by sharing your expertise with students. Sign up
              today and start teaching online with IqraPath!
            </p>

            <Link
              href="/register"
              className="inline-block bg-[#2F8D8C] text-white font-medium px-4 py-2 rounded-full hover:bg-[#267373] transition-colors"
            >
              Start Teaching Today
            </Link>
          </div>

          {/* Right side - Image */}
          <div className="w-full md:w-1/2">
            <img
              src="/assets/images/landing/teacher-with-tablet.png"
              alt="Teacher with tablet"
              className="max-w-full h-80 w-auto"
            />
          </div>
        </div>
      </div>
    </section>
  );
}