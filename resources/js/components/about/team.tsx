import React from 'react';

const Team: React.FC = () => {
  return (
    <section className="py-20 mb-40 relative">
      {/* Background Element */}
      <div className="absolute inset-0 pointer-events-none">
        
        {/* Background Element */}
        <div className="absolute -bottom-40 left-0 w-full h-auto opacity-100">
          <img
            src="/assets/images/about/Rectangle 203.png"
            alt="Background Decoration"
            className="w-full h-full object-cover rounded-xl"
          />
        </div>

        <div className="absolute inset-0 pointer-events-none">
          {/* Upper Right - Arabic Calligraphy */}
          <div className="absolute top-50 right-[80%] opacity-100">
            <img
              src="/assets/images/about/Arabic_Calligraphy_Asy_Syifa-removebg-preview 1.png"
              alt="Arabic Calligraphy Background"
              className="w-full h-auto object-contain object-top rounded-xl opacity-100"
            />
          </div>
        </div>
      </div>

      <div className="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div className="max-w-6xl mx-auto">
          {/* Header Section */}
          <div className="text-center mb-16">
            <h2 className="text-3xl sm:text-4xl md:text-5xl font-bold text-[#2B6B65] mb-6">
              Meet Our Team
            </h2>
            <p className="text-lg sm:text-xl text-gray-700 max-w-3xl mx-auto leading-relaxed">
              At the heart of our platform is a passionate team dedicated to making Quran learning accessible, flexible, and effective for students worldwide.
            </p>
          </div>

          {/* Team Member Cards Grid */}
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-6">
            {/* Team Member 1 - Ahmed Yusuf */}
            <div className="bg-white rounded-xl shadow-lg p-2 hover:shadow-xl transition-shadow">
              <div className="text-center">
                <div className="w-full h-auto mx-auto mb-4 rounded-xl overflow-hidden">
                  <img
                    src="/assets/images/about/card-header.png"
                    alt="Ahmed Yusuf"
                    className="w-full h-full object-cover rounded-xl"
                  />
                </div>
                <h3 className="text-xl font-bold text-[#2B6B65] mb-2">Ahmed Yusuf</h3>
                <p className="text-gray-600 mb-4">Founder & CEO</p>
                <p className="text-gray-700 text-sm leading-relaxed">
                  With a vision to make quality Quran education accessible worldwide, Ahmed founded this platform to connect students with the best teachers.
                </p>
              </div>
            </div>

            {/* Team Member 2 - Fatima Ibrahim */}
            <div className="bg-white rounded-xl shadow-lg p-2 hover:shadow-xl transition-shadow">
              <div className="text-center">
                <div className="w-full h-auto mx-auto mb-4 rounded-xl overflow-hidden">
                  <img
                    src="/assets/images/about/card-header (1).png"
                    alt="Fatima Ibrahim"
                    className="w-full h-full object-cover rounded-xl"
                  />
                </div>
                <h3 className="text-xl font-bold text-[#2B6B65] mb-2">Fatima Ibrahim</h3>
                <p className="text-gray-600 mb-4">Head of Education</p>
                <p className="text-gray-700 text-sm leading-relaxed">
                  A highly experienced Quran teacher and curriculum developer, Fatima ensures that the platform offers the highest educational standards.
                </p>
              </div>
            </div>

            {/* Team Member 3 - Omar Khalid */}
            <div className="bg-white rounded-xl shadow-lg p-2 hover:shadow-xl transition-shadow">
              <div className="text-center">
                <div className="w-full h-auto mx-auto mb-4 rounded-xl overflow-hidden">
                  <img
                    src="/assets/images/about/card-header (2).png"
                    alt="Omar Khalid"
                    className="w-full h-full object-cover rounded-xl"
                  />
                </div>
                <h3 className="text-xl font-bold text-[#2B6B65] mb-2">Omar Khalid</h3>
                <p className="text-gray-600 mb-4">Chief Operating Officer (COO)</p>
                <p className="text-gray-700 text-sm leading-relaxed">
                  With extensive experience in operations and technology, Omar oversees the platform's day-to-day operations and strategic growth.
                </p>
              </div>
            </div>

            {/* Team Member 4 - Aisha Suleiman */}
            <div className="bg-white rounded-xl shadow-lg p-2 hover:shadow-xl transition-shadow">
              <div className="text-center">
                <div className="w-full h-auto mx-auto mb-4 rounded-xl overflow-hidden">
                  <img
                    src="/assets/images/about/card-header (3).png"
                    alt="Aisha Suleiman"
                    className="w-full h-full object-cover rounded-xl"
                  />
                </div>
                <h3 className="text-xl font-bold text-[#2B6B65] mb-2">Aisha Suleiman</h3>
                <p className="text-gray-600 mb-4">Head of Student Success</p>
                <p className="text-gray-700 text-sm leading-relaxed">
                  Dedicated to helping students achieve their learning goals, Aisha leads the student support team, ensuring personalized guidance and smooth onboarding.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default Team;