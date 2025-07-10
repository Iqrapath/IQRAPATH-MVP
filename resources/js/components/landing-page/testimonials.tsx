import React, { useState } from 'react';

interface TestimonialProps {
  content: string;
  rating: number;
  name: string;
  location: string;
  position: string;
  image: string;
}

export default function Testimonials() {
  const [currentIndex, setCurrentIndex] = useState(1);

  const testimonials: TestimonialProps[] = [
    {
      content: "Tajweed lessons are very interactive, and I feel more confident in my recitation.",
      rating: 5,
      name: "Ahmed S.",
      location: "UK",
      position: "CEO Universal",
      image: "/assets/images/landing/testimonial1.png"
    },
    {
      content: "Finding a female Quran teacher for my daughter was difficult until I found this platform. Now, she enjoys her Tajweed classes.",
      rating: 5,
      name: "Aisha K.",
      location: "Canada",
      position: "CEO Elrahman",
      image: "/assets/images/landing/testimonial2.png"
    },
    {
      content: "I always struggled with pronunciation, but my teacher's patience and guidance helped me improve.",
      rating: 5,
      name: "Bilal R.",
      location: "UAE",
      position: "CEO Universal",
      image: "/assets/images/landing/testimonial3.png"
    }
  ];

  const handlePrevious = () => {
    setCurrentIndex((prevIndex) =>
      prevIndex === 0 ? testimonials.length - 1 : prevIndex - 1
    );
  };

  const handleNext = () => {
    setCurrentIndex((prevIndex) =>
      prevIndex === testimonials.length - 1 ? 0 : prevIndex + 1
    );
  };

  const renderStars = (rating: number) => {
    return Array(5).fill(0).map((_, i) => (
      <svg
        key={i}
        className={`w-5 h-5 ${i < rating ? 'text-[#FF6B6B]' : 'text-gray-300'}`}
        fill="currentColor"
        viewBox="0 0 20 20"
      >
        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
      </svg>
    ));
  };

  // Calculate indices for the three visible testimonials
  const prevIndex = currentIndex === 0 ? testimonials.length - 1 : currentIndex - 1;
  const nextIndex = currentIndex === testimonials.length - 1 ? 0 : currentIndex + 1;

  return (
    <section className="py-16 md:py-40 bg-white relative overflow-hidden">
      {/* Top background element */}
      <div className="absolute top-0 right-0 ">
        <img
          src="/assets/images/landing/testimonial-bg.png"
          alt=""
          className="w-full h-auto opacity-100"
        />
      </div>

      {/* Bottom background element */}
      <div className="absolute -bottom-35 left-0 w-48 md:w-64">
        <img
          src="/assets/images/landing/Beautiful_quran.png"
          alt=""
          className="w-full h-auto"
        />
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div className="text-center mb-16">
          <p className="text-[#2F8D8C] font-medium uppercase tracking-wider mb-2">TESTIMONIAL</p>
          <h2 className="text-4xl md:text-5xl font-bold text-[#2F8D8C]">
            What Our Students Say
          </h2>
        </div>

        <div className="relative max-w-5xl mx-auto">
          {/* Testimonial cards */}
          <div className="flex justify-center items-stretch">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
              {/* Left testimonial */}
              <div className="bg-white rounded-lg p-6 shadow-lg transform md:translate-y-4 opacity-70 hidden md:block">
                <div className="mb-4">
                  <div className="flex justify-start mb-4">
                    <div className="w-10 h-10 bg-black rounded-full flex items-center justify-center">
                      <svg className="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path>
                      </svg>
                    </div>
                  </div>
                  <div className="flex mb-3">
                    {renderStars(testimonials[prevIndex].rating)}
                  </div>
                  <p className="text-gray-600">
                    {testimonials[prevIndex].content}
                  </p>
                </div>

                <div className="flex items-center mt-6">
                  <img
                    src={testimonials[prevIndex].image}
                    alt={testimonials[prevIndex].name}
                    className="w-12 h-12 rounded-full mr-4"
                  />
                  <div>
                    <p className="font-bold text-gray-800">{testimonials[prevIndex].name}, {testimonials[prevIndex].location}</p>
                    <p className="text-sm text-gray-500">{testimonials[prevIndex].position}</p>
                  </div>
                </div>
              </div>

              {/* Center testimonial - highlighted */}
              <div className="bg-white rounded-lg p-8 shadow-xl border border-gray-100 transform scale-105 z-20">
                <div className="mb-4">
                  <div className="flex justify-start mb-4">
                    <div className="w-10 h-10 bg-black rounded-full flex items-center justify-center">
                      <svg className="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path>
                      </svg>
                    </div>
                  </div>
                  <div className="flex mb-3">
                    {renderStars(testimonials[currentIndex].rating)}
                  </div>
                  <p className="text-gray-600">
                    {testimonials[currentIndex].content}
                  </p>
                </div>

                <div className="flex items-center mt-6">
                  <img
                    src={testimonials[currentIndex].image}
                    alt={testimonials[currentIndex].name}
                    className="w-12 h-12 rounded-full mr-4"
                  />
                  <div>
                    <p className="font-bold text-gray-800">{testimonials[currentIndex].name}, {testimonials[currentIndex].location}</p>
                    <p className="text-sm text-gray-500">{testimonials[currentIndex].position}</p>
                  </div>
                </div>
              </div>

              {/* Right testimonial */}
              <div className="bg-white rounded-lg p-6 shadow-lg transform md:translate-y-4 opacity-70 hidden md:block">
                <div className="mb-4">
                  <div className="flex justify-start mb-4">
                    <div className="w-10 h-10 bg-black rounded-full flex items-center justify-center">
                      <svg className="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path>
                      </svg>
                    </div>
                  </div>
                  <div className="flex mb-3">
                    {renderStars(testimonials[nextIndex].rating)}
                  </div>
                  <p className="text-gray-600">
                    {testimonials[nextIndex].content}
                  </p>
                </div>

                <div className="flex items-center mt-6">
                  <img
                    src={testimonials[nextIndex].image}
                    alt={testimonials[nextIndex].name}
                    className="w-12 h-12 rounded-full mr-4"
                  />
                  <div>
                    <p className="font-bold text-gray-800">{testimonials[nextIndex].name}, {testimonials[nextIndex].location}</p>
                    <p className="text-sm text-gray-500">{testimonials[nextIndex].position}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Navigation arrows */}
          <div className="flex justify-center mt-10 space-x-4">
            <button
              onClick={handlePrevious}
              className="w-10 h-10 rounded-full border border-gray-300 flex items-center justify-center hover:bg-gray-100 transition-colors"
              aria-label="Previous testimonial"
            >
              <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            <button
              onClick={handleNext}
              className="w-10 h-10 rounded-full border border-gray-300 flex items-center justify-center hover:bg-gray-100 transition-colors"
              aria-label="Next testimonial"
            >
              <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </section>
  );
}