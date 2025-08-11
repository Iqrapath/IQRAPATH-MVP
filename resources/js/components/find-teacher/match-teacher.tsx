import React from 'react';

const MatchTeacher: React.FC = () => {
  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
      <div className="grid md:grid-cols-2 gap-12 items-center">
        {/* Left Column - Form */}
        <div className="space-y-8">
          <div className="space-y-4">
            <h2 className="text-[32px] font-medium text-gray-900 leading-tight">
              Not Sure<br />
              Who to Choose?
            </h2>
            <p className="text-gray-600">
              Let us match you with a verified Quran teacher based on your preferences. Fill the short form below and we'll take care of the rest.
            </p>
          </div>

          <form className="space-y-6">
            {/* Name Input */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Your Name</label>
              <input
                type="text"
                className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[#2EBCAE] focus:ring-1 focus:ring-[#2EBCAE] transition-colors"
                placeholder="Enter your full name"
              />
            </div>

            {/* Email Input */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Your Email</label>
              <input
                type="email"
                className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[#2EBCAE] focus:ring-1 focus:ring-[#2EBCAE] transition-colors"
                placeholder="Enter your email address"
              />
            </div>

            {/* Two Column Layout */}
            <div className="grid grid-cols-2 gap-4">
              {/* Student Age */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Student Age</label>
                <input
                  type="number"
                  className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[#2EBCAE] focus:ring-1 focus:ring-[#2EBCAE] transition-colors"
                  placeholder="Age"
                />
              </div>

              {/* Preferred Subjects */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Preferred Subjects</label>
                <select className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[#2EBCAE] focus:ring-1 focus:ring-[#2EBCAE] transition-colors text-gray-600">
                  <option value="">Select Subject</option>
                  <option value="tajweed">Tajweed</option>
                  <option value="hifz">Hifz</option>
                  <option value="qaida">Qaida</option>
                  <option value="tafsir">Tafsir</option>
                </select>
              </div>
            </div>

            {/* Best Time to Learn */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Best Time to Learn</label>
              <select className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[#2EBCAE] focus:ring-1 focus:ring-[#2EBCAE] transition-colors text-gray-600">
                <option value="">Select preferred time</option>
                <option value="morning">Morning (6 AM - 12 PM)</option>
                <option value="afternoon">Afternoon (12 PM - 5 PM)</option>
                <option value="evening">Evening (5 PM - 10 PM)</option>
              </select>
            </div>

            {/* Submit Button */}
            <button
              type="submit"
              className="w-auto px-8 py-3 bg-[#2EBCAE] text-white rounded-full font-medium hover:bg-[#2EBCAE]/90 transition-colors"
            >
              Match Me With a Teacher
            </button>
          </form>
        </div>

        {/* Right Column - Images */}
        <div className="relative h-[600px]">
          {/* Main Teacher Image */}
          <div className="absolute right-0 top-0 w-[90%] h-[500px] rounded-2xl overflow-hidden shadow-2xl">
            <img
              src="/assets/images/teacher/match-teacher1.jpg"
              alt="Quran Teacher"
              className="w-full h-full object-cover"
            />
          </div>
          {/* Secondary Image */}
          <div className="absolute left-0 bottom-0 w-[60%] h-[280px] rounded-2xl overflow-hidden shadow-xl z-10">
            <img
              src="/assets/images/teacher/match-teacher2.jpg"
              alt="Online Quran Learning"
              className="w-full h-full object-cover"
            />
          </div>
        </div>
      </div>
    </div>
  );
};

export default MatchTeacher;
