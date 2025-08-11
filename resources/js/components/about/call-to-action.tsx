import React from 'react';
import { Link } from '@inertiajs/react';

const CallToAction: React.FC = () => {
  return (
    <section>
      <div className="bg-[#FEF9E5]">
        {/* Content Container */}
        <div className="grid lg:grid-cols-2 items-center gap-8">
          {/* Left Content */}
          <div className="p-18 lg:pl-40">
            <h2 className="mb-4">
              <span className="block text-[42px] text-[#2B6B65] font-semibold leading-tight">
                Start Your Quran Journey Today!
              </span>
            </h2>
            <Link
              href="/find-teacher"
              className="inline-flex items-center justify-center px-8 py-3 bg-[#2B6B65] text-white text-base font-medium rounded-full hover:bg-[#235750] transition-colors duration-300"
            >
              Find a Teacher
            </Link>
          </div>

          {/* Right Image */}
          <div className="relative h-full lg:pr-40">
            <img
              src="/assets/images/cta/cta-teacher.png"
              alt="Teacher with tablet"
              className="w-auto h-full object-contain -scale-x-100"
            />
          </div>
        </div>
      </div>
    </section>
  );
};

export default CallToAction;
