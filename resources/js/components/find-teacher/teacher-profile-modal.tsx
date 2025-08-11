import React from 'react';

interface TeacherProfileModalProps {
  isOpen: boolean;
  onClose: () => void;
  name: string;
  image: string;
  subjects: string;
  location: string;
  rating: number;
  availability: string;
  price: number;
  priceNaira: string;
}

const TeacherProfileModal: React.FC<TeacherProfileModalProps> = ({
  isOpen,
  onClose,
  name,
  image,
  subjects,
  location,
  rating,
  availability,
  price,
  priceNaira
}) => {
  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
      {/* Backdrop */}
      <div 
        className="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
        onClick={onClose}
      />

      {/* Modal */}
      <div className="relative min-h-screen flex items-center justify-center p-4">
        <div className="relative bg-white rounded-2xl max-w-2xl w-full p-6 overflow-hidden">
          {/* Close Button */}
          <button
            onClick={onClose}
            className="absolute right-4 top-4 text-gray-400 hover:text-gray-500"
          >
            <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>

          {/* Content */}
          <div className="flex flex-col md:flex-row gap-6">
            {/* Left Column */}
            <div className="md:w-1/3">
              <div className="aspect-[4/3] rounded-xl overflow-hidden mb-4">
                {image ? (
                  <img
                    src={image}
                    alt={name}
                    className="w-full h-full object-cover"
                  />
                ) : (
                  <div className="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                    <svg className="w-16 h-16 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd" />
                    </svg>
                  </div>
                )}
              </div>
              <div className="space-y-3">
                <h3 className="text-xl font-semibold text-gray-900">{name}</h3>
                <div className="flex items-center gap-1">
                  <div className="flex">
                    {[...Array(5)].map((_, i) => (
                      <svg 
                        key={i}
                        className={`w-4 h-4 ${i < Math.floor(rating) ? 'text-yellow-400' : 'text-gray-200'}`} 
                        xmlns="http://www.w3.org/2000/svg" 
                        viewBox="0 0 20 20" 
                        fill="currentColor"
                      >
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                      </svg>
                    ))}
                  </div>
                  <span className="text-sm text-gray-600">{rating}/5</span>
                </div>
                <div className="flex items-center gap-1.5 text-sm text-gray-600">
                  <svg className="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd" />
                  </svg>
                  {location}
                </div>
              </div>
            </div>

            {/* Right Column */}
            <div className="md:w-2/3">
              <div className="space-y-6">
                {/* Subjects */}
                <div>
                  <h4 className="text-sm font-medium text-gray-500 mb-2">Subjects</h4>
                  <p className="text-gray-900">{subjects}</p>
                </div>

                {/* Availability */}
                <div>
                  <h4 className="text-sm font-medium text-gray-500 mb-2">Availability</h4>
                  <p className="text-gray-900">{availability}</p>
                </div>

                {/* Price */}
                <div>
                  <h4 className="text-sm font-medium text-gray-500 mb-2">Price per Session</h4>
                  <div className="bg-[#E6F7F4] inline-block rounded-full px-4 py-2">
                    <div className="flex items-baseline gap-1">
                      <span className="text-lg font-semibold text-gray-900">${price}</span>
                      <span className="text-gray-500">/</span>
                      <span className="text-sm text-gray-500">₦{priceNaira}</span>
                    </div>
                  </div>
                </div>

                {/* Action Buttons */}
                <div className="flex gap-4 pt-4">
                  <button className="flex-1 bg-[#2EBCAE] text-white px-6 py-2.5 rounded-full font-medium hover:bg-[#2EBCAE]/90 transition-colors">
                    Book Now
                  </button>
                  <button className="flex-1 border border-[#2EBCAE] text-[#2EBCAE] px-6 py-2.5 rounded-full font-medium hover:bg-[#2EBCAE]/5 transition-colors">
                    Message
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default TeacherProfileModal;
