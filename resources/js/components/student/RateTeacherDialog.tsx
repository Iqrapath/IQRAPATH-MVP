import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import { Star, User, BookOpen } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { BookingData } from '@/types';

interface RateTeacherDialogProps {
  booking: BookingData;
  isOpen: boolean;
  onClose: () => void;
}

export default function RateTeacherDialog({ booking, isOpen, onClose }: RateTeacherDialogProps) {
  const [rating, setRating] = useState(booking.teachingSession?.student_rating || booking.user_rating || 0);
  const [review, setReview] = useState(
    booking.teachingSession?.booking_notes?.student_review || 
    booking.teachingSession?.student_review || 
    booking.teachingSession?.student_notes || 
    booking.user_feedback || 
    ''
  );
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async () => {
    if (rating === 0) {
      toast.error('Please select a rating before submitting.');
      return;
    }

    setIsSubmitting(true);

    try {
      router.post(`/student/bookings/${booking.id}/review`, {
        rating: rating,
        review: review,
      }, {
        onSuccess: () => {
          toast.success('Thank you for rating the teacher!');
          onClose();
        },
        onError: (errors) => {
          toast.error('Failed to submit rating. Please try again.');
          console.error('Rating submission errors:', errors);
        },
        onFinish: () => {
          setIsSubmitting(false);
        }
      });
    } catch (error) {
      console.error('Rating submission error:', error);
      toast.error('Failed to submit rating. Please try again.');
      setIsSubmitting(false);
    }
  };

  const handleCancel = () => {
    // Reset to original values
    setRating(booking.teachingSession?.student_rating || booking.user_rating || 0);
    setReview(
      booking.teachingSession?.booking_notes?.student_review || 
      booking.teachingSession?.student_review || 
      booking.teachingSession?.student_notes || 
      booking.user_feedback || 
      ''
    );
    onClose();
  };

  const getTeacherName = () => {
    if (typeof booking.teacher === 'object' && booking.teacher?.name) {
      return `Ustadh ${booking.teacher.name}`;
    } else if (typeof booking.teacher === 'string' && booking.teacher !== 'Unknown Teacher') {
      return `Ustadh ${booking.teacher}`;
    }
    return 'Teacher';
  };

  const getSubjectName = () => {
    if (typeof booking.subject === 'object' && booking.subject?.name) {
      return booking.subject.name;
    } else if (typeof booking.subject === 'string') {
      return booking.subject;
    }
    return booking.title || 'Class';
  };

  return (
    <AlertDialog open={isOpen} onOpenChange={onClose}>
      <AlertDialogContent className="max-w-md">
        <AlertDialogHeader>
          <AlertDialogTitle className="flex items-center gap-2">
            <Star className="w-5 h-5 text-yellow-500" />
            Rate Your Teacher
          </AlertDialogTitle>
          <AlertDialogDescription className="text-left">
            Help other students by sharing your experience with this teacher.
          </AlertDialogDescription>
        </AlertDialogHeader>

        <div className="space-y-4">
          {/* Class Information */}
          <div className="bg-gray-50 rounded-lg p-3 space-y-2">
            <div className="flex items-center gap-2">
              <BookOpen className="w-4 h-4 text-gray-600" />
              <span className="font-medium text-sm">{getSubjectName()}</span>
            </div>
            <div className="flex items-center gap-2">
              <User className="w-4 h-4 text-gray-600" />
              <span className="text-sm text-gray-600">{getTeacherName()}</span>
            </div>
          </div>

          {/* Star Rating */}
          <div className="space-y-2">
            <label className="text-sm font-medium text-gray-700">
              How would you rate this teacher? *
            </label>
            <div className="flex items-center gap-1">
              {[1, 2, 3, 4, 5].map((star) => (
                <button
                  key={star}
                  type="button"
                  onClick={() => setRating(star)}
                  className={`text-2xl transition-colors ${
                    star <= rating
                      ? 'text-yellow-400 hover:text-yellow-500'
                      : 'text-gray-300 hover:text-yellow-400'
                  }`}
                >
                  ★
                </button>
              ))}
              <span className="text-sm text-gray-500 ml-2">
                {rating > 0 ? `(${rating}/5 stars)` : '(Select a rating)'}
              </span>
            </div>
          </div>

          {/* Review Text */}
          <div className="space-y-2">
            <label className="text-sm font-medium text-gray-700">
              Share your experience (optional)
            </label>
            <Textarea
              value={review}
              onChange={(e) => setReview(e.target.value)}
              placeholder="Tell other students about your experience with this teacher..."
              className="min-h-[80px] text-sm resize-none"
              maxLength={500}
            />
            <div className="text-xs text-gray-500 text-right">
              {review.length}/500 characters
            </div>
          </div>
        </div>

        <AlertDialogFooter>
          <AlertDialogCancel onClick={handleCancel} disabled={isSubmitting}>
            Cancel
          </AlertDialogCancel>
          <AlertDialogAction 
            onClick={handleSubmit}
            disabled={isSubmitting || rating === 0}
            className="bg-teal-600 hover:bg-teal-700"
          >
            {isSubmitting ? 'Submitting...' : 'Submit Rating'}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}
