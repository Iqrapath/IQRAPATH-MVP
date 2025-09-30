import React from 'react';
import { Link } from '@inertiajs/react';

export default function BecomeTeacher() {
  return (
    <section className="bg-[#2F8D8C] py-16 md:py-24 relative overflow-hidden">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex flex-col md:flex-row items-center justify-between">
          {/* Left side - Image */}
          <div className="w-full md:w-1/2 mb-10 md:mb-0">
            <img
              src="/assets/images/landing/teacher-laptop.png"
              alt="Teacher using laptop"
              className="max-w-full h-100 mx-auto"
            />
          </div>

          {/* Right side - Content */}
          <div className="w-full md:w-1/2 md:pl-12 text-white">
            <h2 className="text-4xl md:text-5xl font-bold mb-4" style={{
              background: 'linear-gradient(to left, #F3E5C3, #FFFFFF',
              WebkitBackgroundClip: 'text',
              WebkitTextFillColor: 'transparent',
              backgroundClip: 'text'
            }}>
              Become a<br />
              IqraQuest Teacher
            </h2>

            <p className="text-white/90 mb-12 max-w-lg">
              Earn money by sharing your expertise with students. Sign up
              today and start teaching online with IqraQuest!
            </p>

            <div className="space-y-4 mb-12">
              <div className="bg-[#D9D9D9]/40 rounded-sm px-6 py-2 w-[320px] max-w-full ">
                <div className="flex items-center">
                  <div className="w-3 h-3 rounded-full bg-white mr-4 flex-shrink-0"></div>
                  <span className="text-white text-lg font-medium">Discover new students</span>
                </div>
              </div>

              <div className="bg-[#D9D9D9]/40 rounded-sm px-6 py-2 w-[350px] max-w-full ml-[50px]">
                <div className="flex items-center">
                  <div className="w-3 h-3 rounded-full bg-white mr-4 flex-shrink-0"></div>
                  <span className="text-white text-lg font-medium">Expand your Business</span>
                </div>
              </div>

              <div className="bg-[#D9D9D9]/40 rounded-sm px-6 py-2 w-[340px] max-w-full ml-[100px]">
                <div className="flex items-center">
                  <div className="w-3 h-3 rounded-full bg-white mr-4 flex-shrink-0"></div>
                  <span className="text-white text-lg font-medium">Receive payments securely</span>
                </div>
              </div>
            </div>

            <Link
              href="/teacher/register"
              className="inline-block bg-white text-[#2F8D8C] font-medium px-5 py-3 rounded-full hover:bg-gray-100 transition-colors"
            >
              Become a Teacher
            </Link>
          </div>
        </div>
      </div>
    </section>
  );
}