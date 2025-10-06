import React from 'react';

const Enroll: React.FC = () => {
  return (
    <section 
    id="enroll"
    className="py-20 bg-white relative">
      {/* Background Elements */}
      <div className="absolute inset-0 pointer-events-none">
        {/* Upper Right - Arabic Calligraphy */}
        <div className="absolute top-0 right-[30%] opacity-100">
          <img
            src="/assets/images/about/Arabic_Calligraphy_Asy_Syifa-removebg-preview 1.png"
            alt="Arabic Calligraphy Background"
            className="w-full h-full object-contain object-top rounded-xl"
          />
        </div>
      </div>

      <div className="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div className="max-w-7xl mx-auto">
          {/* Header and Mission Statement */}
          <div className="mb-16 lg:mb-20">
            <div className="max-w-2xl">
              <h2 className="text-4xl sm:text-5xl md:text-6xl font-bold mb-6">
                <span className="text-[#2B6B65]">Our</span>
                <br />
                <span className="text-gray-800">Mission & Values</span>
              </h2>
              <p className="text-lg sm:text-xl text-gray-700 leading-relaxed">
                To make quality Quran education accessible to everyone, everywhere.
              </p>
            </div>
          </div>

          {/* Core Values Section */}
          <div className="relative">
            {/* Core Values Image */}
            <div className="flex justify-center lg:justify-center">
              <img
                src="/assets/images/about/core-values.png"
                alt="Our Mission & Values"
                className="w-full max-w-4xl h-auto object-contain rounded-xl"
              />
            </div>
          </div>
        </div>
      </div>

       {/* Lower Left - Group 169 */}
       <div className="absolute bottom-[-30%] left-[2%] opacity-100">
          <img
            src="/assets/images/about/Group 169 (1).png"
            alt="Abstract Background Pattern"
            className="w-full h-full object-contain object-bottom rounded-xl"
          />
        </div>
    </section>
  );
};

export default Enroll;
