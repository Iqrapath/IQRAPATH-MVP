import React from 'react';

export default function DownloadApp() {
  return (
    <section className="py-16 md:py-24 bg-gradient-to-r from-[#ffff] via-[#ffff] to-[#F3E5C3] relative overflow-hidden">
      {/* Decorative Arabic calligraphy in background */}
      <div className="absolute top-0 right-0 h-full w-1/3 opacity-10">
        <div className="h-full w-full bg-contain bg-no-repeat bg-right" style={{ backgroundImage: "url('/assets/images/landing/arabic-calligraphy.png')" }}></div>
      </div>

      <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
        <h2 className="text-4xl md:text-5xl font-bold text-[#2F8D8C] mb-6">
          Download Our Mobile App
        </h2>

        <p className="text-gray-600 max-w-2xl mx-auto mb-10 text-lg">
          Book trusted local services anytime, anywhere. With our mobile app, you can search,
          compare, and book providers on the go — all from the palm of your hand.
        </p>

        <div className="flex flex-wrap justify-center gap-6">
          {/* Google Play Button */}
          <a
            href="#"
            className="inline-block transform transition-transform hover:scale-105"
            target="_blank"
            rel="noopener noreferrer"
          >
            <div className="bg-black text-white rounded-lg px-4 py-2 flex items-center h-14">
              <div className="mr-3">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M4.5 2.5L15.5 12L4.5 21.5V2.5Z" fill="#4CAF50"/>
                  <path d="M4.5 2.5L12.5 9L17 4.5L4.5 2.5Z" fill="#F44336"/>
                  <path d="M4.5 21.5L12.5 15L17 19.5L4.5 21.5Z" fill="#FFC107"/>
                  <path d="M12.5 9L17 4.5V19.5L12.5 15L15.5 12L12.5 9Z" fill="#1976D2"/>
                </svg>
              </div>
              <div className="text-left">
                <div className="text-xs">GET IT ON</div>
                <div className="text-lg font-medium -mt-1">Google Play</div>
              </div>
            </div>
          </a>

          {/* App Store Button */}
          <a
            href="#"
            className="inline-block transform transition-transform hover:scale-105"
            target="_blank"
            rel="noopener noreferrer"
          >
            <div className="bg-black text-white rounded-lg px-4 py-2 flex items-center h-14">
              <div className="mr-3">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="white" xmlns="http://www.w3.org/2000/svg">
                  <path d="M19.08,12.67c0-3.21,2.62-4.74,2.73-4.82a5.84,5.84,0,0,0-4.59-2.48c-1.95-.2-3.83,1.16-4.82,1.16s-2.54-1.14-4.16-1.11A6.12,6.12,0,0,0,3.17,8.68C1.08,12.13,2.66,17.22,4.67,20c1,1.39,2.16,3,3.7,2.91s2.05-.93,3.85-.93,2.3.93,3.86.9,2.6-1.43,3.56-2.83a11.7,11.7,0,0,0,1.62-3.3A5.37,5.37,0,0,1,19.08,12.67Z" />
                  <path d="M16.05,6.24a5.65,5.65,0,0,0,1.29-4A5.76,5.76,0,0,0,14,4.36a5.37,5.37,0,0,0-1.32,3.9A4.75,4.75,0,0,0,16.05,6.24Z" />
                </svg>
              </div>
              <div className="text-left">
                <div className="text-xs">Download on the</div>
                <div className="text-lg font-medium -mt-1">App Store</div>
              </div>
            </div>
          </a>
        </div>
      </div>
    </section>
  );
}