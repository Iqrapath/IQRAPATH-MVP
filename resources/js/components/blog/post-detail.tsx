import React from 'react';

const PostDetail: React.FC = () => {
  return (
    <section className="py-8 bg-white">
      <div className="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">
        {/* Featured Image */}
        <div className="mb-8">
          <img src="/assets/images/blog/featured-article.png" alt="Featured article" className="w-full h-[400px] object-cover rounded-lg" />
        </div>

        <div className="flex flex-col lg:flex-row gap-10">
          {/* Main Content */}
          <div className="w-full lg:w-3/4">
            {/* Post Header */}
            <div className="mb-8">
              <h1 className="text-3xl font-bold mb-4 text-gray-900">How to Help Your Child Memorize Quran Faster</h1>
              <div className="flex items-center text-sm text-gray-500 mb-6">
                <span>5 min read</span>
                <span className="mx-3">•</span>
                <span>June 12, 2023</span>
              </div>
              <p className="text-gray-600 text-base font-medium mb-6">
                Every parent wants the best for their child's Quranic education. This is especially important when seeking to make Quran memorization both enjoyable and effective for children. We've compiled proven strategies to help your child memorize the Quran more efficiently while keeping the process meaningful and engaging.
              </p>
              <p className="text-gray-600 text-base mb-8">
                In this article, we share techniques that have been used successfully by many parents and teachers to help their children memorize Quran verses more effectively.
              </p>
            </div>

            {/* Post Content */}
            <div className="prose max-w-none">
              <h2>1. Create the Right Environment</h2>
              <ul>
                <li>Select a quiet, dedicated space</li>
                <li>Ensure good lighting and ventilation</li>
                <li>Remove distractions - consider using a wooden stand to safely hold the Quran</li>
                <li>Maintain consistency with time and place</li>
              </ul>
              <p>An organized space helps your child focus better and establishes that Quran study is an important part of their daily routine. Many parents find that creating a special "Quran corner" helps children mentally prepare for their memorization session.</p>

              <h2>2. Set a Consistent Schedule</h2>
              <ul>
                <li>Break memorization into small chunks for 10-15 minutes daily</li>
                <li>Study in the morning when the mind is fresh</li>
                <li>Review after Asr and Isha' prayers</li>
                <li>Be realistic about how much your child can memorize at once</li>
              </ul>

              <h2>3. Choose the Right Teacher</h2>
              <ul>
                <li>Find a teacher who specializes in teaching children</li>
                <li>Ensure they have proper Tajweed knowledge</li>
                <li>Look for patience and understanding</li>
                <li>Communication style matters - children respond to positive reinforcement</li>
              </ul>

              <h2>4. Focus on Repetition</h2>
              <p>Memorization without repetition rarely leads to long-term retention. Consider this approach:</p>
              <ul>
                <li>Repeat each verse at least 20 times</li>
                <li>First day: Learn new verses with intense focus</li>
                <li>Next 7 days: Review daily what was memorized</li>
                <li>Weekly and monthly review of previously memorized portions</li>
              </ul>

              <h2>5. Use Visual Aids & Listen to the Quran</h2>
              <p>Children often benefit from multi-sensory learning:</p>
              <ul>
                <li>Listen to recitations by qualified Qaris</li>
                <li>Watch videos of proper recitation</li>
                <li>Consider a Quran pen reader to make learning interactive</li>
              </ul>

              <h3>Conclusion</h3>
              <p>Remember that every child is different, and memorization speeds vary based on age, interest, and natural ability. The key is consistency, positive reinforcement, and making the process enjoyable rather than burdensome.</p>
            </div>

            {/* Tags */}
            <div className="mt-10 mb-6 flex flex-wrap gap-2">
              <span className="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">Quran Memorization</span>
              <span className="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">Islamic Education</span>
              <span className="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">Children</span>
              <span className="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">Parenting</span>
            </div>

            {/* Related Articles Section */}
            <div className="mt-16">
              <h3 className="text-xl font-bold mb-6">You may also like</h3>
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                {/* Related Post 1 */}
                <div className="bg-white rounded-lg overflow-hidden border border-gray-100 shadow-sm">
                  <img src="/assets/images/blog/article1.png" alt="Article image" className="w-full h-32 object-cover" />
                  <div className="p-4">
                    <h4 className="text-sm font-bold mb-2 text-gray-900 line-clamp-2">Top Strategies for Child's Quran Learning</h4>
                    <div className="flex items-center mt-2">
                      <span className="text-xs text-gray-500">June 10, 2023</span>
                    </div>
                  </div>
                </div>

                {/* Related Post 2 */}
                <div className="bg-white rounded-lg overflow-hidden border border-gray-100 shadow-sm">
                  <img src="/assets/images/blog/article1.png" alt="Article image" className="w-full h-32 object-cover" />
                  <div className="p-4">
                    <h4 className="text-sm font-bold mb-2 text-gray-900 line-clamp-2">Understanding Tajweed Rules for Beginners</h4>
                    <div className="flex items-center mt-2">
                      <span className="text-xs text-gray-500">June 8, 2023</span>
                    </div>
                  </div>
                </div>

                {/* Related Post 3 */}
                <div className="bg-white rounded-lg overflow-hidden border border-gray-100 shadow-sm">
                  <img src="/assets/images/blog/article1.png" alt="Article image" className="w-full h-32 object-cover" />
                  <div className="p-4">
                    <h4 className="text-sm font-bold mb-2 text-gray-900 line-clamp-2">How to Choose the Right Quran Teacher</h4>
                    <div className="flex items-center mt-2">
                      <span className="text-xs text-gray-500">June 5, 2023</span>
                    </div>
                  </div>
                </div>

                {/* Related Post 4 */}
                <div className="bg-white rounded-lg overflow-hidden border border-gray-100 shadow-sm">
                  <img src="/assets/images/blog/article1.png" alt="Article image" className="w-full h-32 object-cover" />
                  <div className="p-4">
                    <h4 className="text-sm font-bold mb-2 text-gray-900 line-clamp-2">Online vs Traditional Quran Learning</h4>
                    <div className="flex items-center mt-2">
                      <span className="text-xs text-gray-500">June 2, 2023</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Sidebar */}
          <div className="w-full lg:w-1/4">
            {/* Author Info */}
            <div className="bg-white p-6 rounded-lg border border-gray-100 shadow-sm mb-6">
              <h3 className="text-lg font-semibold mb-4 text-gray-900">About the Author</h3>
              <div className="flex items-center space-x-3">
                <img src="/assets/images/blog/author.png" alt="Author" className="w-12 h-12 rounded-full object-cover" />
                <div>
                  <h4 className="font-medium text-gray-900">Aisha Rahman</h4>
                  <p className="text-sm text-gray-600">Quran Teacher & Education Specialist</p>
                </div>
              </div>
              <p className="text-sm text-gray-600 mt-3">
                With over 10 years of experience in Islamic education, Aisha specializes in teaching Quran to children and helping parents create effective learning environments.
              </p>
            </div>

            {/* Share This Post */}
            <div className="bg-white p-6 rounded-lg border border-gray-100 shadow-sm mb-6">
              <h3 className="text-lg font-semibold mb-4 text-gray-900">Share This Post</h3>
              <div className="flex space-x-3">
                <a href="#" className="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center hover:bg-blue-700 transition-colors">
                  <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" />
                  </svg>
                </a>
                <a href="#" className="w-10 h-10 bg-blue-400 text-white rounded-full flex items-center justify-center hover:bg-blue-500 transition-colors">
                  <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723 10.054 10.054 0 01-3.127 1.184 4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z" />
                  </svg>
                </a>
                <a href="#" className="w-10 h-10 bg-green-600 text-white rounded-full flex items-center justify-center hover:bg-green-700 transition-colors">
                  <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z" />
                  </svg>
                </a>
              </div>
            </div>

            {/* Newsletter Signup */}
            <div className="bg-gradient-to-r from-[#2B6B65] to-[#338078] p-6 rounded-lg text-white">
              <h3 className="text-lg font-semibold mb-3">Stay Updated</h3>
              <p className="text-sm mb-4 opacity-90">Get the latest Quran learning tips and articles delivered to your inbox.</p>
              <div className="space-y-3">
                <input
                  type="email"
                  placeholder="Enter your email"
                  className="w-full px-3 py-2 rounded text-gray-100 text-sm focus:outline-none focus:ring-2 focus:ring-white rounded-xl border border-gray-100"
                />
                <button className="w-full bg-white text-[#2B6B65] py-2 rounded font-medium text-sm hover:bg-gray-100 transition-colors">
                  Subscribe
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default PostDetail;
