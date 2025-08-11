import React, { useState } from 'react';
import Video from './video';

interface TeacherProfileCardProps {
  id?: number;
  image?: string;
  intro_video_url?: string;
  name?: string;
  subjects?: string;
  location?: string;
  rating?: number;
  availability?: string;
  price?: number;
  priceNaira?: string;
  experience_years?: string;
  reviews_count?: number;
  bio?: string;
  languages?: string[];
  teaching_type?: string;
  teaching_mode?: string;
  verified?: boolean;
}

const TeacherProfileCard: React.FC<TeacherProfileCardProps> = ({
  id = 0,
  image = '',
  intro_video_url = '',
  name = '',
  subjects = '',
  location = '',
  rating = 0,
  availability = '',
  price = 0,
  priceNaira = '0',
  experience_years = '',
  reviews_count = 0,
  bio = '',
  languages = [],
  teaching_type = '',
  teaching_mode = '',
  verified = true
}) => {
  const [isOpen, setIsOpen] = useState(false);
  const [activeTab, setActiveTab] = useState('bio');

  const handleOpen = () => setIsOpen(true);
  const handleClose = () => setIsOpen(false);

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
    
    let hash = 0;
    for (let i = 0; i < fullName.length; i++) {
      hash = fullName.charCodeAt(i) + ((hash << 5) - hash);
    }
    const index = Math.abs(hash) % colors.length;
    return colors[index];
  };

  const initials = getInitials(name);
  const initialsColor = getInitialsColor(name);

  const tabs = [
    { id: 'bio', label: 'Bio & Experience' },
    { id: 'calendar', label: 'Availability Calendar' },
    { id: 'pricing', label: 'Pricing' },
    { id: 'reviews', label: 'Ratings & Reviews' }
  ];

  // Generate teaching style based on database data
  const getTeachingStyles = () => {
    const styles = [];
    if (experience_years && experience_years !== 'Not specified') {
      styles.push(`Teaching for ${experience_years} in different Islamic centers`);
    }
    if (teaching_mode) {
      styles.push(`Specializes in ${teaching_mode.toLowerCase()} learning`);
    }
    if (teaching_type) {
      styles.push(`Teaches ${teaching_type.toLowerCase()} classes`);
    }
    styles.push('Teaches students of all ages & levels');
    return styles;
  };

  return (
    <>
      <div className="flex flex-col items-center bg-white rounded-2xl p-3 shadow-lg">
        {/* Video Component */}
        <Video 
          videoUrl={intro_video_url} 
          name={name} 
          showPlayButton={true} 
          className="mb-3"
        />

        {/* View Profile Button */}
        <button
          onClick={handleOpen}
          type="button"
          className="text-[#2EBCAE] text-base font-medium hover:underline"
        >
          View {name}'s Profile
        </button>
      </div>

      {/* Profile Modal */}
      {isOpen && (
        <div className="fixed inset-0 z-50 overflow-y-auto">
          {/* Backdrop */}
          <div
            className="fixed inset-0 bg-white/30 backdrop-blur-sm transition-opacity"
            onClick={handleClose}
          />

          {/* Modal */}
          <div className="relative min-h-screen flex items-center justify-center p-4">
            <div className="relative bg-white/95 backdrop-blur-md rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-white/20">
              {/* Close Button */}
              <button
                onClick={handleClose}
                className="absolute right-4 top-4 text-gray-400 hover:text-gray-500 z-10"
              >
                <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>

              {/* Header Section */}
              <div className="p-6 border-b border-gray-100/50">
                <div className="flex flex-col md:flex-row gap-6">
                  {/* Profile Picture */}
                  <div className="flex-shrink-0">
                    <div className="w-24 h-24 rounded-full overflow-hidden">
                      {image ? (
                        <img
                          src={image}
                          alt={name}
                          className="w-full h-full object-cover"
                        />
                      ) : (
                        <div className={`w-full h-full ${initialsColor} flex items-center justify-center`}>
                          <span className="text-white font-bold text-2xl">{initials}</span>
                        </div>
                      )}
                    </div>
                  </div>

                  {/* Teacher Info */}
                  <div className="flex-1">
                    <div className="flex items-start justify-between mb-2">
                      <h2 className="text-2xl font-bold text-gray-900">{name}</h2>
                      {verified && (
                        <div className="flex items-center gap-1 text-green-600 text-sm">
                          <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                          </svg>
                          <span>Certified Quran Tutor</span>
                        </div>
                      )}
                    </div>

                    {/* Location */}
                    <div className="flex items-center gap-1.5 text-gray-600 mb-2">
                      <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd" />
                      </svg>
                      <span>{location}</span>
                    </div>

                    {/* Rating */}
                    <div className="flex items-center gap-2 mb-3">
                      <div className="flex">
                        {[...Array(5)].map((_, i) => (
                          <svg 
                            key={i}
                            className={`w-4 h-4 ${i < Math.floor(rating) ? 'text-yellow-400' : 'text-gray-200'}`} 
                            fill="currentColor" 
                            viewBox="0 0 20 20"
                          >
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                          </svg>
                        ))}
                      </div>
                      <span className="text-gray-600">{rating}/5 from {reviews_count} Students</span>
                    </div>

                    {/* Subjects */}
                    <div className="mb-3">
                      <span className="text-sm font-medium text-gray-500">Subjects Taught: </span>
                      <span className="text-sm font-semibold text-gray-900">{subjects}</span>
                    </div>

                    {/* Availability */}
                    <div>
                      <span className="text-sm font-medium text-gray-500">Availability: </span>
                      <span className="text-sm text-gray-900">{availability}</span>
                    </div>
                  </div>
                </div>
              </div>

              {/* Navigation Tabs */}
              <div className="px-6 border-b border-gray-100/50">
                <div className="flex space-x-8">
                  {tabs.map((tab) => (
                    <button
                      key={tab.id}
                      onClick={() => setActiveTab(tab.id)}
                      className={`py-3 text-sm font-medium border-b-2 transition-colors ${
                        activeTab === tab.id
                          ? 'border-[#2EBCAE] text-[#2EBCAE]'
                          : 'border-transparent text-gray-500 hover:text-gray-700'
                      }`}
                    >
                      {tab.label}
                    </button>
                  ))}
                </div>
              </div>

              {/* Tab Content */}
              <div className="p-6">
                {activeTab === 'bio' && (
                  <div className="space-y-6">
                    {/* Bio */}
                    {bio && bio.trim() !== '' ? (
                      <div>
                        <p className="text-gray-700 leading-relaxed">{bio}</p>
                      </div>
                    ) : (
                      <div>
                        <p className="text-gray-700 leading-relaxed">
                          I am a certified Quran teacher with {experience_years !== 'Not specified' ? experience_years : 'extensive'} experience teaching Tajweed and Hifz. I focus on personalized learning, ensuring my students grasp the rules of Quran recitation with ease.
                        </p>
                      </div>
                    )}
                    
                    {/* Experience & Teaching Style */}
                    <div>
                      <h4 className="font-semibold text-gray-900 mb-3">Experience & Teaching Style:</h4>
                      <ul className="space-y-2 text-gray-700">
                        {getTeachingStyles().map((style, index) => (
                          <li key={index} className="flex items-start gap-2">
                            <span className="w-1.5 h-1.5 bg-gray-400 rounded-full mt-2 flex-shrink-0"></span>
                            <span>{style}</span>
                          </li>
                        ))}
                      </ul>
                    </div>

                    {/* Teaching Type & Mode */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      {teaching_type && teaching_type !== 'Online' && (
                        <div>
                          <h4 className="font-semibold text-gray-900 mb-2">Teaching Type:</h4>
                          <span className="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">
                            {teaching_type}
                          </span>
                        </div>
                      )}
                      {teaching_mode && teaching_mode !== 'One-to-One' && (
                        <div>
                          <h4 className="font-semibold text-gray-900 mb-2">Teaching Mode:</h4>
                          <span className="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm">
                            {teaching_mode}
                          </span>
                        </div>
                      )}
                    </div>

                    {/* Languages */}
                    {languages && languages.length > 0 && (
                      <div>
                        <h4 className="font-semibold text-gray-900 mb-3">Languages:</h4>
                        <div className="flex flex-wrap gap-2">
                          {languages.map((lang, index) => (
                            <span key={index} className="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm">
                              {lang}
                            </span>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                )}

                {activeTab === 'calendar' && (
                  <div className="space-y-4">
                    <div className="bg-gray-50 rounded-lg p-4">
                      <h4 className="font-semibold text-gray-900 mb-2">Availability</h4>
                      <p className="text-gray-700">{availability || 'Availability not specified'}</p>
                    </div>
                    <p className="text-xs text-gray-500">Availability is based on the teacher's schedule and preferred hours.</p>
                  </div>
                )}

                {activeTab === 'pricing' && (
                  <div className="space-y-6">
                    {/* Session Pricing */}
                    <div className="bg-gray-50 rounded-lg p-4">
                      <h4 className="font-semibold text-gray-900 mb-2">Session Pricing</h4>
                      <div className="flex items-baseline gap-2">
                        <span className="text-2xl font-bold text-gray-900">${price}</span>
                        <span className="text-gray-500">/</span>
                        <span className="text-lg text-gray-500">₦{priceNaira}</span>
                        <span className="text-gray-500">per session</span>
                      </div>
                    </div>

                    {/* Experience Years */}
                    {experience_years && experience_years !== 'Not specified' && (
                      <div className="bg-gray-50 rounded-lg p-4">
                        <h4 className="font-semibold text-gray-900 mb-2">Experience</h4>
                        <div className="flex items-baseline gap-2">
                          <span className="text-xl font-bold text-gray-900">{experience_years}</span>
                          <span className="text-gray-500">of teaching experience</span>
                        </div>
                      </div>
                    )}

                    {/* Statistics */}
                    <div className="grid grid-cols-2 gap-4">
                      <div className="bg-gray-50 rounded-lg p-4 text-center">
                        <div className="text-2xl font-bold text-gray-900">{reviews_count}</div>
                        <div className="text-sm text-gray-500">Total Reviews</div>
                      </div>
                      <div className="bg-gray-50 rounded-lg p-4 text-center">
                        <div className="flex items-center justify-center gap-2">
                          <span className="text-2xl font-bold text-gray-900">{rating}</span>
                          <div className="flex">
                            {[...Array(5)].map((_, i) => (
                              <svg 
                                key={i}
                                className={`w-4 h-4 ${i < Math.floor(rating) ? 'text-yellow-400' : 'text-gray-200'}`} 
                                fill="currentColor" 
                                viewBox="0 0 20 20"
                              >
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                              </svg>
                            ))}
                          </div>
                        </div>
                        <div className="text-sm text-gray-500">Average Rating</div>
                      </div>
                    </div>
                  </div>
                )}

                {activeTab === 'reviews' && (
                  <div className="space-y-6">
                    <div className="bg-gray-50 rounded-lg p-4">
                      <h4 className="font-semibold text-gray-900 mb-2">Ratings Summary</h4>
                      <div className="flex items-center gap-3">
                        <div className="text-3xl font-bold text-gray-900">{rating}</div>
                        <div className="flex">
                          {[...Array(5)].map((_, i) => (
                            <svg 
                              key={i}
                              className={`w-5 h-5 ${i < Math.floor(rating) ? 'text-yellow-400' : 'text-gray-200'}`} 
                              fill="currentColor" 
                              viewBox="0 0 20 20"
                            >
                              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                          ))}
                        </div>
                      </div>
                      <div className="text-sm text-gray-500 mt-1">Based on {reviews_count} reviews</div>
                    </div>
                    <p className="text-xs text-gray-500">Detailed reviews will be available on the teacher’s full profile page.</p>
                  </div>
                )}
              </div>

              {/* Action Buttons */}
              <div className="px-6 py-4 border-t border-gray-100/50">
                <div className="flex gap-4">
                  <button className="flex-1 bg-[#2EBCAE] text-white px-6 py-3 rounded-full font-medium hover:bg-[#2EBCAE]/90 transition-colors">
                    Book Now
                  </button>
                  <button className="flex-1 border border-[#2EBCAE] text-[#2EBCAE] px-6 py-3 rounded-full font-medium hover:bg-[#2EBCAE]/5 transition-colors flex items-center justify-center gap-2">
                    <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    Send Message
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  );
};

export default TeacherProfileCard;
