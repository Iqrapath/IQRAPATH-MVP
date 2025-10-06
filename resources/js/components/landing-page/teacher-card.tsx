import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import TeacherProfileModal from '@/components/common/TeacherProfileModal';
import { Button } from '@/components/ui/button';

export interface TeacherProps {
  id: number;
  name: string;
  specialization: string;
  image: string;
  initials: string;
  rating: number;
  reviews: number;
  yearsExp: number;
}

const TeacherCard: React.FC<TeacherProps> = ({ id, name, specialization, image, initials, rating, reviews, yearsExp }) => {
  const { auth } = usePage().props as { auth?: { user?: any } };
  const isAuthenticated = !!auth?.user;
  
  // Determine the correct href based on authentication status
  const getBookNowHref = () => {
    if (isAuthenticated) {
      return `/student/book-class?teacher_id=${id}`;
    }
    return `/login?redirect=/student/book-class?teacher_id=${id}`;
  };

  return (
    <div className="relative">
      <div className="relative overflow-hidden transform transition-transform hover:scale-105">
        {/* White rectangle background */}
        <div className="relative">
          <img src="/assets/images/landing/TeacherRectangle.png" alt="" className="w-full h-auto" />

          <div className="absolute inset-0 p-3 sm:p-4 md:p-6 pb-0 flex flex-col items-center">
            <div className="w-20 h-20 sm:w-24 sm:h-24 md:w-28 md:h-28 lg:w-32 lg:h-32 overflow-hidden mb-1 rounded-full bg-gray-200 flex items-center justify-center">
              {image ? (
                <img src={image} alt={name} className="w-full h-full object-cover rounded-full" />
              ) : (
                <span className="text-lg sm:text-xl md:text-2xl font-bold text-gray-600">{initials}</span>
              )}
            </div>
            <h3 className="text-sm sm:text-base md:text-lg font-bold text-[#2F4F4C] mb-0 text-center leading-tight">{name}</h3>
            <p className="text-xs sm:text-sm text-gray-500 mb-2 sm:mb-4 text-center leading-tight">{specialization}</p>
          </div>
        </div>

        {/* Yellow rectangle background - positioned on top of the bottom of white rectangle */}
        <div className="absolute top-50 -bottom-12 sm:-bottom-10 md:bottom-10 left-1/2 transform -translate-x-1/2 w-[85%] z-10">
          <img src="/assets/images/landing/TeacherRectangle2.png" alt="" className="w-full h-auto" />

          <div className="absolute inset-0 p-4 sm:p-3 md:p-4">
            {/* Ratings and years exp */}
            <div className="flex justify-between items-center mb-2 sm:mb-3">
              <div className="flex items-center bg-white rounded-full px-1 py-1">
                <svg className="w-5 h-5 sm:w-5 sm:h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                <span className="text-[10px] sm:text-xs font-bold text-gray-500 ml-1">{rating.toFixed(1)}</span>
                <span className="text-[8px] sm:text-[10px] text-gray-500 ml-1 hidden sm:inline">({reviews} Reviews)</span>
              </div>
              <div className="text-[8px] sm:text-[10px] text-gray-500">{yearsExp}+ Years</div>
            </div>

            {/* Action buttons */}
            <div className="flex flex-col sm:flex-row justify-between gap-1 sm:gap-2">
              <TeacherProfileModal
                teacher={{
                  id,
                  name,
                  avatar: image,
                  subjects: [specialization],
                  rating,
                  reviews_count: reviews,
                  hourly_rate_ngn: 0,
                  location: 'Nigeria',
                  availability: 'Available',
                  bio: '',
                  experience_years: yearsExp.toString(),
                  verified: true
                }}
                trigger={
                  <Button 
                    variant="outline"
                    className="text-[#2F8D8C] text-[10px] sm:text-xs font-medium px-2 sm:px-3 py-1 sm:py-1.5 rounded-full border border-[#2F8D8C] hover:bg-[#2F8D8C] hover:text-white transition-colors"
                  >
                    View Profile
                  </Button>
                }
              />
              <Link 
                href={getBookNowHref()}
                className="bg-[#2F8D8C] text-white text-[10px] sm:text-xs font-medium px-2 sm:px-3 py-1 sm:py-1.5 rounded-full hover:bg-[#267373] transition-colors text-center"
              >
                Book Now
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default TeacherCard;