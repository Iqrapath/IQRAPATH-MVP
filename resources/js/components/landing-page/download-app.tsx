import React from 'react';
import { GooglePlayIcon } from '@/components/icons/google-play';
import { AppleStoreIcon } from '../icons/apple-store';
import { toast } from 'sonner';
import { Link } from '@inertiajs/react';

const handleGooglePlayClick = () => {
  toast.info('Google Play Store app is coming soon!');
};

const handleAppleStoreClick = () => {
  toast.info('Apple Store app is coming soon!');
};

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
          <Link
            href="#"
            onClick={handleGooglePlayClick}
            className="inline-block transform transition-transform hover:scale-105"
            // target="_blank"
            rel="noopener noreferrer"
          >
            <div className="bg-black text-white rounded-lg px-4 py-2 flex items-center h-14">
              <div className="mr-3">
                  <GooglePlayIcon className="h-5 w-5" />
              </div>
              <div className="text-left">
                <div className="text-xs">GET IT ON</div>
                <div className="text-lg font-medium -mt-1">Google Play</div>
              </div>
            </div>
          </Link>

          {/* App Store Button */}
          <Link
            href="#"
            onClick={handleAppleStoreClick}
            className="inline-block transform transition-transform hover:scale-105"
            // target="_blank"
            rel="noopener noreferrer"
          >
            <div className="bg-black text-white rounded-lg px-4 py-2 flex items-center h-14">
              <div className="mr-3">
                <AppleStoreIcon className="h-5 w-5" />
              </div>
              <div className="text-left">
                <div className="text-xs">Download on the</div>
                <div className="text-lg font-medium -mt-1">App Store</div>
              </div>
            </div>
          </Link>
        </div>
      </div>
    </section>
  );
}