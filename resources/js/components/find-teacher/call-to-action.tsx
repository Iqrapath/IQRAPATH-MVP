import React from 'react';

const CallToAction: React.FC = () => {
  return (
    <section>
      <div className="bg-[#FEF9E5]">
        {/* Content Container */}
        <div className="grid lg:grid-cols-2 items-center gap-8">
          {/* Left Content */}
          <div className="p-12 lg:pl-40">
            <h2 className="mb-4">
              <span className="block text-[42px] text-[#2B6B65] font-semibold leading-tight">Become a</span>
              <span className="block text-[42px] text-[#64748B] font-semibold leading-tight">Iqrapath Teacher</span>
            </h2>
            <p className="text-gray-600 mb-8 max-w-md text-lg">
              Earn money by sharing your expertise with students. Sign up today and start teaching online with IqraPath!
            </p>
            <a href="#" className="inline-flex items-center justify-center px-8 py-3 bg-[#2B6B65] text-white text-base font-medium rounded-full hover:bg-[#235750] transition-colors duration-300">
              Start Teaching Today
            </a>
          </div>

          {/* Right Image */}
          <div className="relative h-full lg:pr-40">
            <img src="/assets/images/cta/cta-teacher.png" alt="Teacher with tablet" className="w-auto h-full object-contain scale-x-[-1]" />
          </div>
        </div>
      </div>
    </section>
  );
};

export default CallToAction;