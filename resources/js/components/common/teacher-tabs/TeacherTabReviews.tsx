import React, { useState } from 'react';
import { Star, ChevronDown } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';

interface Review {
  id: number;
  student_name: string;
  rating: number;
  review: string;
  created_at: string;
  formatted_date: string;
  student_display_name: string;
}

interface TeacherTabReviewsProps {
  teacherId: number;
  teacher?: {
    reviews_data?: {
      overall_rating?: number;
      total_reviews?: number;
      recent_reviews?: Review[];
    };
    [key: string]: any;
  };
}

export default function TeacherTabReviews({ teacherId, teacher }: TeacherTabReviewsProps) {
  const [selectedFilter, setSelectedFilter] = useState('Recent Reviews');
  
  const reviewsData = teacher?.reviews_data;
  const overallRating = reviewsData?.overall_rating ?? 4.9;
  const totalReviews = reviewsData?.total_reviews ?? 120;
  const recentReviews = reviewsData?.recent_reviews ?? [];

  const renderStars = (rating: number) => {
    return Array.from({ length: 5 }, (_, i) => (
      <Star 
        key={i} 
        className={`w-4 h-4 ${i < rating ? 'text-amber-400 fill-amber-400' : 'text-gray-300'}`}
      />
    ));
  };

  // Fallback review data if no reviews exist
  const fallbackReview = {
    id: 1,
    student_name: 'Fatima Ibrahim',
    rating: 5,
    review: 'Very patient and explains concepts clearly. Highly recommended!',
    created_at: 'March 3, 2025',
    formatted_date: '3rd March, 2025',
    student_display_name: 'Fatima Ibrahim'
  };

  const displayReviews = recentReviews.length > 0 ? recentReviews : [fallbackReview];

  return (
    <div className="bg-white border border-gray-100 rounded-2xl p-6">
      {/* Overall Rating Header */}
      <div className="flex items-center gap-3 mb-6">
        <div className="flex items-center gap-2">
          <Star className="w-5 h-5 text-amber-400 fill-amber-400" />
          <span className="text-2xl font-bold text-gray-900">{overallRating}</span>
          <span className="text-gray-600">({totalReviews} Reviews)</span>
        </div>
      </div>

      {/* Filter Dropdown */}
      <div className="flex items-center justify-between mb-6">
        <div className="relative">
          <button 
            className="flex items-center gap-2 text-gray-700 hover:text-gray-900"
            onClick={() => {/* Add filter functionality if needed */}}
          >
            <span className="font-medium">{selectedFilter}</span>
            <ChevronDown className="w-4 h-4" />
          </button>
        </div>
        <button className="text-[#2C7870] hover:text-[#236158] font-medium text-sm">
          View all
        </button>
      </div>

      {/* Reviews List */}
      <div className="space-y-6">
        {displayReviews.map((review, index) => (
          <div key={review.id || index} className="border-b border-gray-100 pb-6 last:border-b-0 last:pb-0">
            <div className="flex items-start gap-4">
              {/* Student Avatar */}
              <Avatar className="w-12 h-12">
                <AvatarImage src="" alt={review.student_name} />
                <AvatarFallback className="bg-gray-100 text-gray-600 font-medium">
                  {review.student_name.split(' ').map(n => n[0]).join('').substring(0, 2)}
                </AvatarFallback>
              </Avatar>

              {/* Review Content */}
              <div className="flex-1">
                <div className="flex items-center justify-between mb-2">
                  <h4 className="font-semibold text-gray-900">{review.student_name}</h4>
                  <span className="text-sm text-gray-500">{review.created_at}</span>
                </div>
                
                <div className="flex items-center gap-2 mb-3">
                  {renderStars(review.rating)}
                  <span className="text-sm font-medium text-gray-700">{review.rating.toFixed(1)}</span>
                </div>
                
                <p className="text-gray-700 leading-relaxed">
                  {review.review || 'Very patient and explains concepts clearly. Highly recommended!'}
                </p>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Show more reviews message if no reviews */}
      {recentReviews.length === 0 && (
        <div className="text-center py-4 text-gray-500 text-sm">
          More reviews will appear here as students complete sessions.
        </div>
      )}
    </div>
  );
}


