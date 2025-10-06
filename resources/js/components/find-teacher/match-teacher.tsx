import React, { useState, useEffect } from 'react';
import { router, usePage } from '@inertiajs/react';
import { toast } from 'sonner';

interface Subject {
  id: number;
  name: string;
}

interface MatchedTeacher {
  id: number;
  name: string;
  image: string | null;
  subjects: string;
  rating: string | number; // API returns string, but we convert to number
  reviews_count: number;
  experience_years: string;
  price_naira: string | number; // API returns string, but we convert to number
  bio: string;
  availability: string[];
}

interface MatchResponse {
  success: boolean;
  matched_teachers: MatchedTeacher[];
  total_matches: number;
  message: string;
}

interface MatchTeacherProps {
  subjects?: Subject[];
}

const MatchTeacher: React.FC<MatchTeacherProps> = ({ subjects = [] }) => {
  const { auth } = usePage().props as { auth?: { user?: any } };
  const isAuthenticated = !!auth?.user;

  const [formData, setFormData] = useState({
    name: '',
    email: '',
    student_age: '',
    preferred_subject: '',
    best_time: '',
  });
  const [isLoading, setIsLoading] = useState(false);
  const [matchedTeachers, setMatchedTeachers] = useState<MatchedTeacher[]>([]);
  const [showResults, setShowResults] = useState(false);

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);

    try {
      const response = await fetch('/api/match-teachers', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify(formData),
      });

      const data: MatchResponse = await response.json();
      // console.log('API Response:', data);

      if (data.success) {
        // Convert object with numeric keys to array
        let teachers: MatchedTeacher[] = [];
        if (Array.isArray(data.matched_teachers)) {
          teachers = data.matched_teachers;
        } else if (data.matched_teachers && typeof data.matched_teachers === 'object') {
          // Convert object with numeric keys to array
          teachers = Object.values(data.matched_teachers) as MatchedTeacher[];
        }
        // console.log('Teachers array:', teachers);
        setMatchedTeachers(teachers);
        setShowResults(true);
        toast.success(data.message);
      } else {
        toast.error(data.message || 'No teachers found matching your preferences.');
      }
    } catch (error) {
      console.error('Error matching teachers:', error);
      toast.error('Something went wrong. Please try again.');
    } finally {
      setIsLoading(false);
    }
  };

  const handleBookTeacher = (teacherId: number) => {
    if (isAuthenticated) {
      // User is logged in, go directly to booking page
      router.visit(`/student/book-class?teacher_id=${teacherId}`);
    } else {
      // User is not logged in, redirect to login with return URL
      router.visit(`/login?redirect=/student/book-class?teacher_id=${teacherId}`);
    }
  };

  const handleBackToForm = () => {
    setShowResults(false);
    setMatchedTeachers([]);
  };

  if (showResults) {
    return (
      <div id="match-teacher" className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div className="text-center mb-8">
          <h2 className="text-[32px] font-medium text-gray-900 leading-tight mb-4">
            Your Matched Teachers
          </h2>
          <p className="text-gray-600 mb-6">
            We found {matchedTeachers.length} teacher(s) that match your preferences!
          </p>
          <button
            onClick={handleBackToForm}
            className="text-[#2EBCAE] hover:text-[#267373] font-medium"
          >
            ← Back to form
          </button>
        </div>

        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          {Array.isArray(matchedTeachers) && matchedTeachers.map((teacher) => (
            <div key={teacher.id} className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
              <div className="text-center mb-4">
                <div className="w-20 h-20 mx-auto mb-3 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
                  {teacher.image ? (
                    <img src={teacher.image} alt={teacher.name} className="w-full h-full object-cover" />
                  ) : (
                    <span className="text-2xl font-bold text-gray-600">
                      {teacher.name.split(' ').map(n => n[0]).join('').substring(0, 2)}
                    </span>
                  )}
                </div>
                <h3 className="text-lg font-semibold text-gray-900">{teacher.name}</h3>
                <p className="text-sm text-gray-600">{teacher.subjects}</p>
              </div>

              <div className="space-y-2 mb-4">
                <div className="flex items-center justify-between text-sm">
                  <span className="text-gray-600">Rating:</span>
                  <div className="flex items-center">
                    <span className="text-yellow-400 mr-1">★</span>
                    <span className="font-medium">{Number(teacher.rating).toFixed(1)}</span>
                    <span className="text-gray-500 ml-1">({teacher.reviews_count})</span>
                  </div>
                </div>
                <div className="flex items-center justify-between text-sm">
                  <span className="text-gray-600">Experience:</span>
                  <span className="font-medium">{teacher.experience_years}</span>
                </div>
                <div className="flex items-center justify-between text-sm">
                  <span className="text-gray-600">Rate:</span>
                  <span className="font-medium">₦{Number(teacher.price_naira).toLocaleString()}/hr</span>
                </div>
              </div>

              {teacher.bio && (
                <p className="text-sm text-gray-600 mb-4 line-clamp-2">{teacher.bio}</p>
              )}

              <button
                onClick={() => handleBookTeacher(teacher.id)}
                className="w-full bg-[#2EBCAE] text-white py-2 px-4 rounded-lg font-medium hover:bg-[#267373] transition-colors"
              >
                Book This Teacher
              </button>
            </div>
          ))}
        </div>
      </div>
    );
  }

  return (
    <div id="match-teacher" className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
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

          <form onSubmit={handleSubmit} className="space-y-6">
            {/* Name Input */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Your Name</label>
              <input
                type="text"
                name="name"
                value={formData.name}
                onChange={handleInputChange}
                required
                className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[#2EBCAE] focus:ring-1 focus:ring-[#2EBCAE] transition-colors"
                placeholder="Enter your full name"
              />
            </div>

            {/* Email Input */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Your Email</label>
              <input
                type="email"
                name="email"
                value={formData.email}
                onChange={handleInputChange}
                required
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
                  name="student_age"
                  value={formData.student_age}
                  onChange={handleInputChange}
                  required
                  min="5"
                  max="100"
                  className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[#2EBCAE] focus:ring-1 focus:ring-[#2EBCAE] transition-colors"
                  placeholder="Age"
                />
              </div>

              {/* Preferred Subjects */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Preferred Subjects</label>
                <select
                  name="preferred_subject"
                  value={formData.preferred_subject}
                  onChange={handleInputChange}
                  required
                  className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[#2EBCAE] focus:ring-1 focus:ring-[#2EBCAE] transition-colors text-gray-600"
                >
                  <option value="">Select Subject</option>
                  {subjects.map((subject) => (
                    <option key={subject.id} value={subject.name}>
                      {subject.name}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            {/* Best Time to Learn */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Best Time to Learn</label>
              <select
                name="best_time"
                value={formData.best_time}
                onChange={handleInputChange}
                required
                className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[#2EBCAE] focus:ring-1 focus:ring-[#2EBCAE] transition-colors text-gray-600"
              >
                <option value="">Select preferred time</option>
                <option value="morning">Morning (6 AM - 12 PM)</option>
                <option value="afternoon">Afternoon (12 PM - 5 PM)</option>
                <option value="evening">Evening (5 PM - 10 PM)</option>
              </select>
            </div>

            {/* Submit Button */}
            <button
              type="submit"
              disabled={isLoading}
              className="w-auto px-8 py-3 bg-[#2EBCAE] text-white rounded-full font-medium hover:bg-[#2EBCAE]/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {isLoading ? 'Finding Teachers...' : 'Match Me With a Teacher'}
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
