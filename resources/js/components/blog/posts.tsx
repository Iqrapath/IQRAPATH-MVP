import React from 'react';
import { Link } from '@inertiajs/react';

const Posts: React.FC = () => {
  return (
    <section className="py-8 bg-white">
      <div className="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl relative">
        <div className="flex flex-col lg:flex-row">
          {/* Main Content */}
          <div className="w-full lg:w-3/4 lg:pr-10">
            {/* Featured Article */}
            <div className="mb-10 bg-white overflow-hidden">
              <div className="relative">
                <img src="/assets/images/blog/featured-article.png" alt="Featured article" className="w-full h-[300px] object-cover" />
                <div className="absolute top-4 right-4 bg-white px-3 py-1 text-xs rounded">
                  <span>5 min read</span>
                </div>
              </div>
              <div className="pt-6">
                <h2 className="text-2xl font-bold mb-2 text-gray-900">How to Help Your Child Memorize Quran Faster</h2>
                <p className="text-gray-600 text-sm mb-3">Discover practical tips and strategies to make Quran memorization enjoyable for your child. We share proven methods that have helped thousands of parents support their children's Quran learning journey.</p>
                <div className="flex items-center justify-between text-xs text-gray-500">
                  <span>June 12, 2023</span>
                  <Link href="/blog-post" className="text-[#2B6B65] font-medium">Read More</Link>
                </div>
              </div>
            </div>

            {/* Category Filters */}
            <div className="flex flex-wrap items-center mb-8 border-b border-gray-200 pb-3 gap-x-4 overflow-x-auto no-scrollbar">
              <a href="#" className="text-[#2B6B65] border-b-2 border-[#2B6B65] px-1 py-2 font-medium text-sm whitespace-nowrap">All Posts</a>
              <a href="#" className="text-gray-600 hover:text-[#2B6B65] px-1 py-2 font-medium text-sm whitespace-nowrap">Tajweed Tips</a>
              <a href="#" className="text-gray-600 hover:text-[#2B6B65] px-1 py-2 font-medium text-sm whitespace-nowrap">Hifz Journey</a>
              <a href="#" className="text-gray-600 hover:text-[#2B6B65] px-1 py-2 font-medium text-sm whitespace-nowrap">Parents Guide</a>
              <a href="#" className="text-gray-600 hover:text-[#2B6B65] px-1 py-2 font-medium text-sm whitespace-nowrap">Teacher Advice</a>
              <a href="#" className="text-gray-600 hover:text-[#2B6B65] px-1 py-2 font-medium text-sm whitespace-nowrap">Platform Updates</a>
            </div>

            {/* Blog Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {/* Article 1 */}
              <div className="bg-white rounded-lg overflow-hidden border border-gray-100 shadow-sm">
                <img src="/assets/images/blog/article1.png" alt="Article image" className="w-full h-40 object-cover" />
                <div className="p-4">
                  <div className="flex items-center text-xs text-gray-500 mb-2">
                    <span>4 min read</span>
                    <span className="mx-2">•</span>
                    <span>Quran Learning</span>
                  </div>
                  <h3 className="text-base font-bold mb-2 text-gray-900 line-clamp-2">Why Online Quran Learning Works Better for Busy Parents</h3>
                  <p className="text-gray-600 text-xs mb-3 line-clamp-3">Discover how online Quran classes provide flexibility and quality education for families with busy schedules.</p>
                  <div className="flex items-center mt-2">
                    <span className="text-xs text-gray-500">June 10, 2023</span>
                  </div>
                </div>
              </div>

              {/* Article 2 */}
              <div className="bg-white rounded-lg overflow-hidden border border-gray-100 shadow-sm">
                <img src="/assets/images/blog/article1.png" alt="Article image" className="w-full h-40 object-cover" />
                <div className="p-4">
                  <div className="flex items-center text-xs text-gray-500 mb-2">
                    <span>6 min read</span>
                    <span className="mx-2">•</span>
                    <span>Tajweed Tips</span>
                  </div>
                  <h3 className="text-base font-bold mb-2 text-gray-900 line-clamp-2">How to Choose the Right Teacher for Your Child</h3>
                  <p className="text-gray-600 text-xs mb-3 line-clamp-3">Learn what qualifications to look for when selecting a Quran teacher who will effectively connect with your child.</p>
                  <div className="flex items-center mt-2">
                    <span className="text-xs text-gray-500">June 8, 2023</span>
                  </div>
                </div>
              </div>

              {/* Article 3 */}
              <div className="bg-white rounded-lg overflow-hidden border border-gray-100 shadow-sm">
                <img src="/assets/images/blog/article1.png" alt="Article image" className="w-full h-40 object-cover" />
                <div className="p-4">
                  <div className="flex items-center text-xs text-gray-500 mb-2">
                    <span>3 min read</span>
                    <span className="mx-2">•</span>
                    <span>Tajweed Learning</span>
                  </div>
                  <h3 className="text-base font-bold mb-2 text-gray-900 line-clamp-2">Understanding the Rules of Tajweed: Beginner's Guide</h3>
                  <p className="text-gray-600 text-xs mb-3 line-clamp-3">A simple breakdown of essential tajweed rules that will improve your Quran recitation immediately.</p>
                  <div className="flex items-center mt-2">
                    <span className="text-xs text-gray-500">June 5, 2023</span>
                  </div>
                </div>
              </div>

              {/* Article 4 */}
              <div className="bg-white rounded-lg overflow-hidden border border-gray-100 shadow-sm">
                <img src="/assets/images/blog/article1.png" alt="Article image" className="w-full h-40 object-cover" />
                <div className="p-4">
                  <div className="flex items-center text-xs text-gray-500 mb-2">
                    <span>5 min read</span>
                    <span className="mx-2">•</span>
                    <span>Hifz Journey</span>
                  </div>
                  <h3 className="text-base font-bold mb-2 text-gray-900 line-clamp-2">Top 10 Mistakes to Avoid When Memorizing the Quran</h3>
                  <p className="text-gray-600 text-xs mb-3 line-clamp-3">Common pitfalls that slow down memorization progress and how to overcome them effectively.</p>
                  <div className="flex items-center mt-2">
                    <span className="text-xs text-gray-500">June 2, 2023</span>
                  </div>
                </div>
              </div>

              {/* Article 5 */}
              <div className="bg-white rounded-lg overflow-hidden border border-gray-100 shadow-sm">
                <img src="/assets/images/blog/article1.png" alt="Article image" className="w-full h-40 object-cover" />
                <div className="p-4">
                  <div className="flex items-center text-xs text-gray-500 mb-2">
                    <span>7 min read</span>
                    <span className="mx-2">•</span>
                    <span>Teacher Advice</span>
                  </div>
                  <h3 className="text-base font-bold mb-2 text-gray-900 line-clamp-2">Building a Strong Teacher-Student Relationship in Online Classes</h3>
                  <p className="text-gray-600 text-xs mb-3 line-clamp-3">Strategies for creating meaningful connections and maintaining engagement in virtual Quran learning environments.</p>
                  <div className="flex items-center mt-2">
                    <span className="text-xs text-gray-500">May 30, 2023</span>
                  </div>
                </div>
              </div>

              {/* Article 6 */}
              <div className="bg-white rounded-lg overflow-hidden border border-gray-100 shadow-sm">
                <img src="/assets/images/blog/article1.png" alt="Article image" className="w-full h-40 object-cover" />
                <div className="p-4">
                  <div className="flex items-center text-xs text-gray-500 mb-2">
                    <span>4 min read</span>
                    <span className="mx-2">•</span>
                    <span>Platform Updates</span>
                  </div>
                  <h3 className="text-base font-bold mb-2 text-gray-900 line-clamp-2">New Features: Enhanced Video Quality and Interactive Tools</h3>
                  <p className="text-gray-600 text-xs mb-3 line-clamp-3">Discover the latest platform improvements designed to make your Quran learning experience even better.</p>
                  <div className="flex items-center mt-2">
                    <span className="text-xs text-gray-500">May 28, 2023</span>
                  </div>
                </div>
              </div>
            </div>

            {/* Load More Button */}
            <div className="text-center mt-12">
              <button className="bg-[#2B6B65] text-white px-8 py-3 rounded-full font-medium hover:bg-[#235750] transition-colors">
                Load More Articles
              </button>
            </div>
          </div>

          {/* Sidebar */}
          <div className="w-full lg:w-1/4 mt-8 lg:mt-0">
            {/* Search */}
            <div className="bg-white p-6 rounded-lg border border-gray-100 shadow-sm mb-6">
              <h3 className="text-lg font-semibold mb-4 text-gray-900">Search Articles</h3>
              <div className="relative">
                <input
                  type="text"
                  placeholder="Search articles..."
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#2B6B65] focus:border-transparent"
                />
                <button className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-[#2B6B65]">
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </button>
              </div>
            </div>

            {/* Popular Categories */}
            <div className="bg-white p-6 rounded-lg border border-gray-100 shadow-sm mb-6">
              <h3 className="text-lg font-semibold mb-4 text-gray-900">Popular Categories</h3>
              <ul className="space-y-2">
                <li>
                  <a href="#" className="text-gray-600 hover:text-[#2B6B65] text-sm flex items-center justify-between">
                    <span>Tajweed Tips</span>
                    <span className="bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs">12</span>
                  </a>
                </li>
                <li>
                  <a href="#" className="text-gray-600 hover:text-[#2B6B65] text-sm flex items-center justify-between">
                    <span>Hifz Journey</span>
                    <span className="bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs">8</span>
                  </a>
                </li>
                <li>
                  <a href="#" className="text-gray-600 hover:text-[#2B6B65] text-sm flex items-center justify-between">
                    <span>Parents Guide</span>
                    <span className="bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs">15</span>
                  </a>
                </li>
                <li>
                  <a href="#" className="text-gray-600 hover:text-[#2B6B65] text-sm flex items-center justify-between">
                    <span>Teacher Advice</span>
                    <span className="bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs">6</span>
                  </a>
                </li>
                <li>
                  <a href="#" className="text-gray-600 hover:text-[#2B6B65] text-sm flex items-center justify-between">
                    <span>Platform Updates</span>
                    <span className="bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs">4</span>
                  </a>
                </li>
              </ul>
            </div>

            {/* Recent Posts */}
            <div className="bg-white p-6 rounded-lg border border-gray-100 shadow-sm mb-6">
              <h3 className="text-lg font-semibold mb-4 text-gray-900">Recent Posts</h3>
              <div className="space-y-4">
                <div className="flex items-start space-x-3">
                  <img src="/assets/images/blog/article1.png" alt="Recent post" className="w-16 h-12 object-cover rounded" />
                  <div>
                    <h4 className="text-sm font-medium text-gray-900 line-clamp-2">How to Help Your Child Memorize Quran Faster</h4>
                    <p className="text-xs text-gray-500 mt-1">June 12, 2023</p>
                  </div>
                </div>
                <div className="flex items-start space-x-3">
                  <img src="/assets/images/blog/article1.png" alt="Recent post" className="w-16 h-12 object-cover rounded" />
                  <div>
                    <h4 className="text-sm font-medium text-gray-900 line-clamp-2">Why Online Quran Learning Works Better for Busy Parents</h4>
                    <p className="text-xs text-gray-500 mt-1">June 10, 2023</p>
                  </div>
                </div>
                <div className="flex items-start space-x-3">
                  <img src="/assets/images/blog/article1.png" alt="Recent post" className="w-16 h-12 object-cover rounded" />
                  <div>
                    <h4 className="text-sm font-medium text-gray-900 line-clamp-2">How to Choose the Right Teacher for Your Child</h4>
                    <p className="text-xs text-gray-500 mt-1">June 8, 2023</p>
                  </div>
                </div>
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
                  className="w-full px-3 py-2 rounded text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-white"
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

export default Posts;
