import React from 'react';
import { Link } from '@inertiajs/react';

const AboutUs: React.FC = () => {
  return (
    <section className="bg-[#2B6B65] py-20">
      <div className="container mx-auto px-4 sm:px-6 lg:px-8">
        <div className="max-w-7xl mx-auto grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
          {/* Left Section - Textual Content */}
          <div className="text-white lg:max-w-[560px]">
            {/* Subtitle */}
            <p className="uppercase tracking-[0.35em] text-[11px] mb-2 text-[#F3E5C3]">A BIT</p>

            {/* Main Heading */}
            <h2 className="text-4xl sm:text-5xl md:text-6xl tracking-wide mb-6 leading-[1.1]">
              ABOUT US
            </h2>

            {/* Mission Statement */}
            <p className="text-white/90 text-base sm:text-lg leading-relaxed mb-8">
              We believe that learning the Quran should be accessible, affordable, and high-quality. That's why we created this platform—to connect students with expert teachers and help them achieve their learning goals.
            </p>

            {/* Key Features */}
            <ul className="space-y-4 mb-8 text-[#F3E5C3]">
              <li className="flex items-start gap-3">
                <span className="mt-2 inline-block w-2 h-2 rounded-full bg-[#F3E5C3]"></span>
                <p className="text-[#F3E5C3] text-sm sm:text-base">Flexible scheduling & reliable payment system.</p>
              </li>
              <li className="flex items-start gap-3">
                <span className="mt-2 inline-block w-2 h-2 rounded-full bg-[#F3E5C3]"></span>
                <p className="text-[#F3E5C3] text-sm sm:text-base">Trusted by thousands of students & teachers.</p>
              </li>
              <li className="flex items-start gap-3">
                <span className="mt-2 inline-block w-2 h-2 rounded-full bg-[#F3E5C3]"></span>
                <p className="text-[#F3E5C3] text-sm sm:text-base">Secure platform for learning & teaching.</p>
              </li>
            </ul>

            {/* Call-to-Action Button */}
            <Link
              href="/find-teacher"
              className="inline-flex items-center justify-center px-6 py-3 bg-white text-[#2B6B65] font-medium rounded-full hover:bg-gray-100 transition-colors duration-300 shadow-sm"
            >
              Explore More
            </Link>
          </div>

          {/* Right Section - Visual Elements */}
          <div className="relative h-[540px] sm:h-[580px]">
            {/* Upper Image: Hands holding Quran (img4) */}
            <div className="absolute left-[-10%] top-0 w-[62%] h-[56%]">
              <img
                src="/assets/images/about/img4.png"
                alt="Hands holding Quran"
                // className="w-full h-full object-cover rounded-2xl shadow-[0_12px_30px_rgba(0,0,0,0.25)]"
              />

            </div>
              {/* Top-right Statistics Card (img1) */}
              <img
                src="/assets/images/about/img1.png"
                alt="30,000+ trusted by thousands of students & teachers"
                className="absolute -top-6 left-[40%] sm:-top-8 sm:right-[-12px] w-56 sm:w-64 rounded-xl"
              />

            {/* Lower Image: Person reading Quran by lantern (img3) */}
            <div className="absolute right-0 bottom-0 w-[62%] h-[64%]">
              <img
                src="/assets/images/about/img3.png"
                alt="Person reading Quran by lantern"
                // className="w-full h-full object-cover rounded-2xl shadow-[0_12px_30px_rgba(0,0,0,0.25)]"
              />
            </div>

            {/* Middle-left Ratings Card (img2) */}
            <img
              src="/assets/images/about/img2.png"
              alt="Best ratings"
              className="absolute left-[0%] bottom-[10%] w-48 sm:w-56 rounded-xl"
            />
          </div>
        </div>
      </div>
    </section>
  );
};

export default AboutUs;
