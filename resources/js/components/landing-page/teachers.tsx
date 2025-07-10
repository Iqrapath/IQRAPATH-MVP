import React from 'react';
import { Link } from '@inertiajs/react';
import TeacherCard, { TeacherProps } from './teacher-card';

export default function Teachers() {
  const teachers: TeacherProps[] = [
    {
      name: 'Ustadh Ahmad Ali',
      specialization: 'Tajweed & Hifz',
      image: '/assets/images/landing/teacher1.png',
      rating: 4.95,
      reviews: 128,
      yearsExp: 7
    },
    {
      name: 'Ustadh Ahmad Ali',
      specialization: 'Tajweed & Hifz',
      image: '/assets/images/landing/teacher2.png',
      rating: 4.90,
      reviews: 120,
      yearsExp: 5
    },
    {
      name: 'Ustadh Ahmad Ali',
      specialization: 'Tajweed & Hifz',
      image: '/assets/images/landing/teacher3.png',
      rating: 4.97,
      reviews: 130,
      yearsExp: 10
    },
    {
      name: 'Ustadh Ahmad Ali',
      specialization: 'Tajweed & Hifz',
      image: '/assets/images/landing/teacher4.png',
      rating: 4.93,
      reviews: 125,
      yearsExp: 7
    }
  ];

  return (
    <section className="py-16 md:py-40 overflow-hidden bg-white relative">
      {/* Decorative background elements - positioned first to be behind content */}
      <img
        src="/assets/images/landing/TeacherBgRectangle.png"
        alt=""
        className="absolute -bottom-8 left-0 w-full object-cover mb-4"
      />

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-10 relative z-10">
        <h2 className="text-4xl md:text-5xl font-bold text-center text-[#2F8D8C] mb-20">
          Meet Our Certified Quran Teachers
        </h2>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {teachers.map((teacher, index) => (
            <TeacherCard key={index} {...teacher} />
          ))}
        </div>
      </div>
    </section>
  );
}