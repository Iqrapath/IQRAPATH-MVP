import React from 'react';
import { Link } from '@inertiajs/react';

export interface TeacherProps {
  name: string;
  specialization: string;
  image: string;
  rating: number;
  reviews: number;
  yearsExp: number;
}

const TeacherCard: React.FC<TeacherProps> = ({ name, specialization, image, rating, reviews, yearsExp }) => {
  return (
    <div className="relative p-4">
      <div className="relative overflow-hidden transform transition-transform hover:scale-105">
        {/* White rectangle background */}
        <div className="relative">
          <img src="/assets/images/landing/TeacherRectangle.png" alt="" className="w-full h-auto" />

          <div className="absolute inset-0 p-6 pb-0 flex flex-col items-center">
            <div className="w-32 h-32 overflow-hidden mb-1">
              <img src={image} alt={name} className="w-full h-full object-cover" />
            </div>
            <h3 className="text-lg font-bold text-[#2F4F4C] mb-0">{name}</h3>
            <p className="text-sm text-gray-500 mb-4">{specialization}</p>
          </div>
        </div>

        {/* Yellow rectangle background - positioned on top of the bottom of white rectangle */}
        <div className="relative -mt-30 z-10 mx-auto w-[85%]">
          <img src="/assets/images/landing/TeacherRectangle2.png" alt="" className="w-full h-auto" />

          <div className="absolute inset-0 p-4">
            {/* Ratings and years exp */}
            <div className="flex justify-between items-center mb-3">
              <div className="flex items-center bg-white rounded-full px-1 py-1">
                <svg className="w-3.5 h-3.5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                <span className="text-xs font-bold text-gray-500 ml-1">{rating.toFixed(1)}</span>
                <span className="text-[10px] text-gray-500 ml-1">({reviews} Reviews)</span>
              </div>
              <div className="text-[10px] text-gray-500">{yearsExp}+ Years Exp</div>
            </div>

            {/* Action buttons */}
            <div className="flex justify-between">
              <Link href={`/teacher/${name.toLowerCase().replace(/\s+/g, '-')}`} className="text-[#2F8D8C] text-xs font-medium px-3 py-1.5 rounded-full border border-[#2F8D8C] hover:bg-[#2F8D8C] hover:text-white transition-colors">
                View Profile
              </Link>
              <Link href={`/book/${name.toLowerCase().replace(/\s+/g, '-')}`} className="bg-[#2F8D8C] text-white text-xs font-medium px-3 py-1.5 rounded-full hover:bg-[#267373] transition-colors">
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