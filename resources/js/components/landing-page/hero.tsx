import { Link } from '@inertiajs/react';

export default function Hero() {
  return (
    <div className="relative  overflow-hidden">
      <div
        className="absolute inset-0 z-0 opacity-100"
        style={{
          backgroundImage: "url('/assets/images/landing/hero-bg.jpg')",
          backgroundSize: 'cover',
          backgroundPosition: 'center'
        }}
      />

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 sm:pt-12 md:pt-16 pb-28 md:pb-40 relative z-10">
        <div className="flex flex-col items-center">
          {/* Text content */}
          <div className="text-center mb-8 sm:mb-12 md:mb-16 max-w-4xl px-4">
            <div className="mb-4 sm:mb-6">
              <h1 className="text-2xl sm:text-3xl md:text-4xl font-bold">
                <span className="text-gray-900">Connect with </span>
                <span className="inline-block text-[#2F8D8C] bg-gradient-to-r from-[#FFF6E0] via-[#C9DFD1] to-[#8AC5BF] px-3 sm:px-4 py-1 sm:py-2 rounded-full transform -rotate-2">Expert Quran Teachers</span>
              </h1>
            </div>

            <h2 className="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mt-4 sm:mt-6 mb-4 sm:mb-8">
              Anytime, Anywhere!
            </h2>

            <p className="text-base sm:text-lg text-gray-600 mb-6 sm:mb-10 max-w-lg mx-auto leading-relaxed px-4">
              Find expert Quran tutors for kids and adults. Learn at your own pace, anytime, anywhere.
            </p>

            <div className="flex flex-wrap gap-4 sm:gap-6 justify-center">
              <Link
                href={route('register.student-guardian')}
                className="bg-[#2F8D8C] hover:bg-[#267373] text-white rounded-full px-6 sm:px-8 py-2.5 sm:py-3 text-sm sm:text-base font-medium transition-colors duration-200"
              >
                Find a Teacher
              </Link>

              <Link
                href={route('register.teacher')}
                className="border-2 border-[#2F8D8C] hover:bg-[#2F8D8C] hover:text-white text-[#2F8D8C] rounded-full px-6 sm:px-8 py-2.5 sm:py-3 text-sm sm:text-base font-medium transition-colors duration-200"
              >
                Become a Teacher
              </Link>
            </div>
          </div>

          {/* Teacher image */}
          <div className="w-full flex justify-center">
            <img
              src="/assets/images/landing/hero-teacher.png"
              alt="Quran Teacher"
              className="w-full max-w-xs sm:max-w-sm md:max-w-md lg:max-w-2xl object-contain"
            />
          </div>
        </div>
      </div>
    </div>
  );
}