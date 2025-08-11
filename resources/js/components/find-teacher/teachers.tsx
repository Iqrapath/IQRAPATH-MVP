import React, { useState, useEffect } from 'react';
import TeacherCard from './teacher-card';

interface Teacher {
  id: number;
  name: string;
  subjects: string;
  location: string;
  rating: number;
  availability: string;
  price: number;
  priceNaira: string;
  image: string;
  intro_video_url?: string;
  experience_years: string;
  reviews_count: number;
  bio: string;
  languages: string[];
  teaching_type: string;
  teaching_mode: string;
}

interface TeachersProps {
  teachers?: {
    data: Teacher[];
    total: number;
    current_page: number;
    last_page: number;
  };
  subjects?: string[];
  languages?: string[];
  filters?: {
    search: string;
    subject: string;
    rating: string;
    budget: string;
    timePreference: string;
    language: string;
  };
}

const Teachers: React.FC<TeachersProps> = ({
  teachers: initialTeachers,
  subjects = [],
  languages = [],
  filters: initialFilters = { search: '', subject: '', rating: '', budget: '', timePreference: '', language: '' }
}) => {
  const [teachers, setTeachers] = useState<Teacher[]>(initialTeachers?.data || []);
  const [totalResults, setTotalResults] = useState(initialTeachers?.total || 0);
  const [currentPage, setCurrentPage] = useState(initialTeachers?.current_page || 1);
  const [lastPage, setLastPage] = useState(initialTeachers?.last_page || 1);
  const [filters, setFilters] = useState(initialFilters);
  const [loading, setLoading] = useState(false);

  // Time preference options
  const timePreferences = [
    'Any Time',
    'Morning (6 AM - 12 PM)',
    'Afternoon (12 PM - 6 PM)',
    'Evening (6 PM - 12 AM)',
    'Night (12 AM - 6 AM)',
    'Weekdays Only',
    'Weekends Only'
  ];

  // Language options
  const languagesOptions = [
    'Any Language',
    ...languages
  ];

  // Fetch teachers from API when filters change
  const fetchTeachers = async (newFilters: typeof filters, page: number = 1) => {
    setLoading(true);
    try {
      const params = new URLSearchParams();
      if (newFilters.search) params.append('search', newFilters.search);
      if (newFilters.subject) params.append('subject', newFilters.subject);
      if (newFilters.rating) params.append('rating', newFilters.rating);
      if (newFilters.budget) params.append('budget', newFilters.budget);
      if (newFilters.timePreference) params.append('timePreference', newFilters.timePreference);
      if (newFilters.language) params.append('language', newFilters.language);
      params.append('page', page.toString());

      const response = await fetch(`/api/teachers?${params.toString()}`);
      const data = await response.json();

      setTeachers(data.teachers);
      setTotalResults(data.total);
      setCurrentPage(data.current_page || page);
      setLastPage(data.last_page || 1);
    } catch (error) {
      console.error('Error fetching teachers:', error);
    } finally {
      setLoading(false);
    }
  };

  // Handle search input
  const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const newFilters = { ...filters, search: e.target.value };
    setFilters(newFilters);
    fetchTeachers(newFilters, 1);
  };

  // Handle subject filter
  const handleSubjectChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
    const newFilters = { ...filters, subject: e.target.value };
    setFilters(newFilters);
    fetchTeachers(newFilters, 1);
  };

  // Handle time preference filter
  const handleTimePreferenceChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
    const newFilters = { ...filters, timePreference: e.target.value };
    setFilters(newFilters);
    fetchTeachers(newFilters, 1);
  };

  // Handle language filter
  const handleLanguageChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
    const newFilters = { ...filters, language: e.target.value };
    setFilters(newFilters);
    fetchTeachers(newFilters, 1);
  };

  // Handle budget range change
  const handleBudgetChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const newFilters = { ...filters, budget: e.target.value };
    setFilters(newFilters);
    fetchTeachers(newFilters, 1);
  };

  // Handle apply filters button
  const handleApplyFilters = () => {
    fetchTeachers(filters, 1);
  };

  // Handle page change
  const handlePageChange = (page: number) => {
    fetchTeachers(filters, page);
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
      <div className="relative bg-[#2EBCAE] rounded-[32px] pb-12 lg:pb-16 overflow-hidden">
        {/* Curved Accent */}
        <div className="absolute top-0 right-0 w-1/2 hidden md:block">
          <img src="/assets/images/teacher/curve.png" alt="" className="w-full h-auto object-contain" />
        </div>

        {/* Content Container */}
        <div className="relative px-6 md:px-12 lg:px-16 pt-8 lg:pt-10">
          {/* Header Text */}
          <div className="w-full mx-auto">
            <h2 className="text-2xl md:text-[30px] mb-4 lg:mb-6 bg-gradient-to-r from-[#FEF9E5] via-[#FEF9E5] to-white/80 bg-clip-text text-transparent">
              Browse verified Quran tutors for Tajweed, Hifz, Qaida, and more.
            </h2>

            {/* Search Bar */}
            <div className="w-full mb-12 lg:mb-16">
              <div className="relative max-w-2xl">
                <div className="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                  <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </div>
                <input
                  type="text"
                  placeholder="Search by Teacher Name or Subject..."
                  className="w-full pl-12 pr-4 py-3 md:py-4 rounded-full bg-white/95 text-gray-600 placeholder-gray-400 shadow-lg focus:outline-none text-sm md:text-base"
                  value={filters.search}
                  onChange={handleSearchChange}
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Filter Section - Positioned to overlap */}
      <div className="relative -mt-16 md:-mt-20 lg:-mt-25">
        {/* White Background */}
        <div className="bg-white mx-4 md:mx-8 rounded-[24px] md:rounded-[32px] p-4 md:p-6 lg:p-8">
          {/* Pill Filter */}
          <div className="bg-[#F8F9FC] rounded-[32px] md:rounded-[50px] p-4 md:p-6 lg:p-8 shadow-xl border">
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
              {/* Subject Filter */}
              <div className="relative">
                <div className="flex items-center gap-2 text-gray-600 text-sm mb-2">
                  <svg className="w-5 h-5" viewBox="0 0 24 24" fill="none">
                    <path d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                  </svg>
                  Subject
                </div>
                <select 
                  className="w-full bg-white border border-gray-100 rounded-full px-4 py-3 text-sm text-gray-600"
                  value={filters.subject}
                  onChange={handleSubjectChange}
                >
                  <option value="">All Subject</option>
                  {subjects.map((subject) => (
                    <option key={subject} value={subject}>{subject}</option>
                  ))}
                </select>
              </div>

              {/* Time Preference */}
              <div className="relative">
                <div className="flex items-center gap-2 text-gray-600 text-sm mb-2">
                  <svg className="w-5 h-5" viewBox="0 0 24 24" fill="none">
                    <path d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                  </svg>
                  Time Preference
                </div>
                <select 
                  className="w-full bg-white border border-gray-100 rounded-full px-4 py-3 text-sm text-gray-600"
                  value={filters.timePreference}
                  onChange={handleTimePreferenceChange}
                >
                  <option value="">Select time</option>
                  {timePreferences.map((time) => (
                    <option key={time} value={time}>{time}</option>
                  ))}
                </select>
              </div>

              {/* Budget Range */}
              <div className="relative">
                <div className="flex items-center gap-2 text-gray-600 text-sm mb-2">
                  <svg className="w-5 h-5" viewBox="0 0 24 24" fill="none">
                    <path d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                  </svg>
                  Budget: <span className="text-[#2EBCAE]">PKR</span>
                </div>
                <div className="px-2">
                  <input 
                    type="range" 
                    min="100" 
                    max="10000" 
                    defaultValue="5000" 
                    className="w-full accent-[#2EBCAE]"
                    value={filters.budget || 5000}
                    onChange={handleBudgetChange}
                  />
                  <div className="flex justify-between text-xs text-gray-500 mt-1">
                    <span>$100</span>
                    <span>$10000</span>
                  </div>
                </div>
              </div>

              {/* Language */}
              <div className="flex flex-col md:flex-row items-start md:items-center gap-4">
                <div className="flex-1 w-full">
                  <div className="flex items-center gap-2 text-gray-600 text-sm mb-2">
                    <svg className="w-5 h-5" viewBox="0 0 24 24" fill="none">
                      <path d="M10.5 21l5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 016-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 01-3.827-5.802" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                    </svg>
                    Language
                  </div>
                  <select 
                    className="w-full bg-white border border-gray-100 rounded-full px-4 py-3 text-sm text-gray-600"
                    value={filters.language}
                    onChange={handleLanguageChange}
                  >
                    <option value="">Choose Language</option>
                    {languagesOptions.map((language) => (
                      <option key={language} value={language}>{language}</option>
                    ))}
                  </select>
                </div>
                <button 
                  className="w-full md:w-auto h-[42px] px-6 bg-[#2EBCAE] text-white rounded-full mt-2 md:mt-6"
                  onClick={handleApplyFilters}
                  disabled={loading}
                >
                  {loading ? 'Loading...' : 'Apply'}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Results Section */}
      <div className="mt-8 md:mt-12">
        {/* Results Header */}
        <div className="flex justify-between items-center mb-6 md:mb-8 px-4 md:px-0">
          <h3 className="text-lg md:text-xl font-medium text-gray-900">
            {loading ? 'Loading...' : `${totalResults} Results`}
          </h3>
          <a href="#" className="text-[#2EBCAE] hover:underline">See all</a>
        </div>

        {/* Teachers Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-6 px-4 md:px-0">
          {loading ? (
            // Loading skeleton
            Array.from({ length: 8 }).map((_, index) => (
              <div key={index} className="animate-pulse">
                <div className="bg-gray-200 rounded-2xl p-6 h-48"></div>
              </div>
            ))
          ) : teachers.length > 0 ? (
            teachers.map((teacher) => (
              <TeacherCard
                key={teacher.id}
                id={teacher.id}
                name={teacher.name}
                subjects={teacher.subjects}
                location={teacher.location}
                rating={teacher.rating}
                availability={teacher.availability}
                price={teacher.price}
                priceNaira={teacher.priceNaira}
                image={teacher.image}
                intro_video_url={teacher.intro_video_url}
                experience_years={teacher.experience_years}
                reviews_count={teacher.reviews_count}
                bio={teacher.bio}
                languages={teacher.languages}
                teaching_type={teacher.teaching_type}
                teaching_mode={teacher.teaching_mode}
                verified={true}
              />
            ))
          ) : (
            <div className="col-span-full text-center py-12">
              <p className="text-gray-500 text-lg">No teachers found matching your criteria.</p>
            </div>
          )}
        </div>

        {/* Pagination */}
        {!loading && teachers.length > 0 && lastPage > 1 && (
          <div className="flex justify-center items-center mt-8 md:mt-12 px-4 md:px-0">
            <div className="flex items-center gap-2 bg-white rounded-full px-4 py-2 shadow-sm border border-gray-100">
              {/* Previous Button */}
              <button
                onClick={() => handlePageChange(currentPage - 1)}
                disabled={currentPage === 1}
                className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-colors ${
                  currentPage === 1
                    ? 'text-gray-400 cursor-not-allowed'
                    : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                }`}
              >
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7" />
                </svg>
              </button>

              {/* Page Numbers */}
              <div className="flex items-center gap-1">
                {Array.from({ length: Math.min(5, lastPage) }, (_, i) => {
                  let pageNum;
                  if (lastPage <= 5) {
                    pageNum = i + 1;
                  } else if (currentPage <= 3) {
                    pageNum = i + 1;
                  } else if (currentPage >= lastPage - 2) {
                    pageNum = lastPage - 4 + i;
                  } else {
                    pageNum = currentPage - 2 + i;
                  }

                  return (
                    <button
                      key={pageNum}
                      onClick={() => handlePageChange(pageNum)}
                      className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-colors ${
                        currentPage === pageNum
                          ? 'bg-[#2EBCAE] text-white'
                          : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                      }`}
                    >
                      {pageNum}
                    </button>
                  );
                })}
              </div>

              {/* Next Button */}
              <button
                onClick={() => handlePageChange(currentPage + 1)}
                disabled={currentPage === lastPage}
                className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-colors ${
                  currentPage === lastPage
                    ? 'text-gray-400 cursor-not-allowed'
                    : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                }`}
              >
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
                </svg>
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default Teachers;