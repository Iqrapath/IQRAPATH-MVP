import React, { useState } from 'react';
import TeacherProfileCard from './teacher--profile-card';

interface TeacherCardProps {
  id?: number;
  name: string;
  subjects: string;
  location: string;
  rating: number;
  availability: string;
  price: number;
  priceNaira: string;
  image: string;
  intro_video_url?: string;
  experience_years?: string;
  reviews_count?: number;
  bio?: string;
  languages?: string[];
  teaching_type?: string;
  teaching_mode?: string;
  verified?: boolean;
}

const TeacherCard: React.FC<TeacherCardProps> = ({
  id = 0,
  name,
  subjects,
  location,
  rating,
  availability,
  price,
  priceNaira,
  image,
  intro_video_url,
  experience_years,
  reviews_count,
  bio,
  languages,
  teaching_type,
  teaching_mode,
  verified = true
}) => {
  const [showModal, setShowModal] = useState(false);

  // Function to get initials from name
  const getInitials = (fullName: string): string => {
    if (!fullName) return '';
    
    const names = fullName.trim().split(' ');
    if (names.length === 1) {
      return names[0].charAt(0).toUpperCase();
    } else {
      return (names[0].charAt(0) + names[names.length - 1].charAt(0)).toUpperCase();
    }
  };

  // Function to get random color for initials background
  const getInitialsColor = (fullName: string): string => {
    const colors = [
      'bg-blue-500',
      'bg-green-500', 
      'bg-purple-500',
      'bg-pink-500',
      'bg-indigo-500',
      'bg-teal-500',
      'bg-orange-500',
      'bg-red-500'
    ];
    
    // Use name to consistently generate the same color for the same name
    let hash = 0;
    for (let i = 0; i < fullName.length; i++) {
      hash = fullName.charCodeAt(i) + ((hash << 5) - hash);
    }
    const index = Math.abs(hash) % colors.length;
    return colors[index];
  };

  const initials = getInitials(name);
  const initialsColor = getInitialsColor(name);

  return (
    <>
      <div className="relative bg-white rounded-2xl p-4 shadow-lg hover:shadow-xl transition-shadow cursor-pointer" onMouseEnter={() => setShowModal(true)} onMouseLeave={() => setShowModal(false)}>
        <div className="flex gap-4">
          {/* Teacher Image */}
          <div className="flex-shrink-0">
            <div className="w-16 h-16 rounded-full overflow-hidden">
              {image ? (
                <img
                  src={image}
                  alt={name}
                  className="w-full h-full object-cover"
                />
              ) : (
                <div className={`w-full h-full ${initialsColor} flex items-center justify-center`}>
                  <span className="text-white font-bold text-xl">{initials}</span>
                </div>
              )}
            </div>
          </div>

          {/* Teacher Info */}
          <div className="flex-1 min-w-0 space-y-2">
            {/* Name */}
            <h4 className="text-lg font-bold text-gray-900 truncate">{name}</h4>

            {/* Subject */}
            <div className="flex items-center">
              <span className="text-gray-500 text-sm">Subject: </span>
              <span className="text-gray-900 text-sm font-semibold ml-1">{subjects}</span>
            </div>

            {/* Location */}
            <div className="flex items-center gap-1.5">
              <svg className="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                <circle cx="12" cy="10" r="3"/>
              </svg>
              <span className="text-gray-500 text-sm">{location}</span>
            </div>

            {/* Rating */}
            <div className="flex items-center gap-1.5">
              <div className="flex">
                {[...Array(5)].map((_, i) => (
                  <svg 
                    key={i}
                    className={`w-4 h-4 ${i < Math.floor(rating) ? 'text-yellow-400' : 'text-gray-200'}`} 
                    xmlns="http://www.w3.org/2000/svg" 
                    viewBox="0 0 24 24" 
                    fill="currentColor"
                  >
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                  </svg>
                ))}
              </div>
              <span className="text-gray-500 text-sm">{rating}/5</span>
            </div>

            {/* Availability */}
            <div className="flex items-center">
              <span className="text-gray-500 text-sm">Availability: </span>
              <span className="text-gray-900 text-sm font-semibold ml-1">{availability}</span>
            </div>

            {/* Price and Actions */}
            <div className="flex items-center justify-between pt-2">
              {/* Price */}
              <div className="bg-[#E6F7F4] rounded-lg px-3 py-2">
                <div className="flex items-baseline gap-1">
                  <span className="text-sm font-bold text-gray-900">${price}</span>
                  <span className="text-gray-500 text-xs">/</span>
                  <span className="text-xs text-gray-500">₦{priceNaira}</span>
                </div>
                <div className="text-xs text-gray-500 font-medium mt-0.5">Per session</div>
              </div>

              {/* Action Buttons */}
              <div className="flex items-center gap-3">
                <button className="text-[#2EBCAE] font-semibold hover:text-[#2EBCAE]/80 text-sm whitespace-nowrap transition-colors">
                  Book Now
                </button>
                <button className="w-8 h-8 flex items-center justify-center rounded-full border-2 border-[#2EBCAE] text-[#2EBCAE] hover:bg-[#2EBCAE] hover:text-white transition-colors">
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        {/* Hover Modal */}
        {showModal && (
          <div className="absolute left-0 bottom-full w-[320px] z-50">
            <TeacherProfileCard
              id={id}
              name={name}
              subjects={subjects}
              location={location}
              rating={rating}
              availability={availability}
              price={price}
              priceNaira={priceNaira}
              image={image}
              intro_video_url={intro_video_url}
              experience_years={experience_years}
              reviews_count={reviews_count}
              bio={bio}
              languages={languages}
              teaching_type={teaching_type}
              teaching_mode={teaching_mode}
              verified={verified}
            />
          </div>
        )}
      </div>
    </>
  );
};

export default TeacherCard;
