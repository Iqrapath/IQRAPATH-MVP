import React from 'react';

const Download: React.FC = () => {
  return (
    <section className="py-24 bg-gradient-to-r from-[#E2F3EC] via-[#E9F2EB] to-[#F0F5E5] relative overflow-hidden">
      <div className="container mx-auto px-6">
        <div className="max-w-3xl mx-auto text-center">
          <h2 className="text-4xl font-bold text-[#2B6B65] mb-6">Download Our Mobile App</h2>
          <p className="text-gray-600 text-lg mb-12 max-w-2xl mx-auto">
            Book trusted local services anytime, anywhere. With our mobile app, you can search, compare, and book providers on the go — all from the palm of your hand.
          </p>

          {/* App Store Buttons */}
          <div className="flex -mt-20 items-center justify-center gap-4">
            {/* Google Play Button */}
            <a href="#" className="inline-block">
              <img src="/assets/images/download/google-play.png" alt="Get it on Google Play" className="h-50" />
            </a>

            {/* App Store Button */}
            <a href="#" className="inline-block">
              <img src="/assets/images/download/app-store.png" alt="Download on the App Store" className="h-13" />
            </a>
          </div>
        </div>
      </div>
    </section>
  );
};

export default Download;
