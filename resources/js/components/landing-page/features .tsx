import React from 'react';

export default function Features() {
    return (
        <div className="relative -mt-48 sm:-mt-56 md:-mt-60 lg:-mt-64 z-30 px-3 sm:px-6 lg:px-8 max-w-6xl mx-auto mb-[-50px] sm:mb-[-60px] md:mb-[-70px] lg:mb-[-50px]">
            <div className="bg-gradient-to-r from-[#2F4F4C] via-[#317B74] to-[#2F4F4C] rounded-3xl sm:rounded-full py-4 sm:py-4 md:py-6 px-4 sm:px-4 md:px-8 shadow-xl">
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-5 sm:gap-4 md:gap-6 text-white">
                    {/* Feature 1: Verified Tutors */}
                    <div className="flex items-center text-left sm:text-center md:text-left sm:flex-col md:flex-row sm:border-b md:border-b-0 sm:border-r-0 md:border-r sm:pb-4 md:pb-0 sm:border-[#5AADAC]/30 md:border-[#5AADAC]/50 md:pr-4">
                        <div className="flex justify-center items-center bg-[#ffffff]/10 rounded-full w-12 h-12 flex-shrink-0 mr-4 sm:mr-0 sm:mb-3 md:mr-3 md:mb-0 md:bg-transparent md:w-auto md:h-auto">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="40" height="40" color="#ffffff" fill="none" className="w-6 h-6 sm:w-8 sm:h-8 md:w-7 md:h-7">
                                <path d="M2.5 13.8333L6 17.5L7.02402 16.4272M16.5 6.5L10.437 12.8517" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M7.5 13.8333L11 17.5L21.5 6.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        <div>
                            <h3 className="text-base font-bold mb-0.5 sm:mb-1">Verified Tutors</h3>
                            <p className="text-xs text-gray-100/90">
                                Learn from certified and experienced Quran teachers.
                            </p>
                        </div>
                    </div>

                    {/* Feature 2: 24/7 Availability */}
                    <div className="flex items-center text-left sm:text-center md:text-left sm:flex-col md:flex-row sm:border-b md:border-b-0 sm:border-r-0 md:border-r sm:pb-4 md:pb-0 sm:border-[#5AADAC]/30 md:border-[#5AADAC]/50 md:pr-4">
                        <div className="flex justify-center items-center bg-[#ffffff]/10 rounded-full w-12 h-12 flex-shrink-0 mr-4 sm:mr-0 sm:mb-3 md:mr-3 md:mb-0 md:bg-transparent md:w-auto md:h-auto">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" className="w-6 h-6 sm:w-8 sm:h-8 md:w-7 md:h-7">
                                <path d="M4.99021 7.69035C4.99021 6.47564 5.97443 5.49142 7.18913 5.49045H8.18988C8.77049 5.49045 9.32679 5.25996 9.73914 4.85149L10.4384 4.15126C11.2952 3.28959 12.6879 3.28472 13.5496 4.14153L13.5505 4.14251L13.5593 4.15029L14.2595 4.85052C14.6709 5.25996 15.2282 5.48948 15.8088 5.48948H16.8115C18.0262 5.48948 19.0114 6.47467 19.0114 7.68937V8.68915C19.0114 9.26976 19.2409 9.82703 19.6503 10.2394L20.3506 10.9396C21.2122 11.7955 21.2171 13.1881 20.3613 14.0508L20.3603 14.0518L20.3515 14.0605L19.6513 14.7607C19.2419 15.1721 19.0124 15.7284 19.0124 16.309V17.3127C19.0104 18.5264 18.0252 19.5097 16.8115 19.5087H15.8068C15.2262 19.5087 14.669 19.7392 14.2576 20.1486L13.5574 20.8479C12.7025 21.7105 11.3098 21.7164 10.4481 20.8615C10.4472 20.8615 10.4462 20.8605 10.4452 20.8596L10.4365 20.8508L9.7372 20.1516C9.32581 19.7421 8.76855 19.5126 8.18794 19.5116H7.18913C5.97443 19.5116 4.99021 18.5274 4.99021 17.3127V16.3071C4.99021 15.7265 4.75972 15.1702 4.35028 14.7588L3.65102 14.0586C2.78837 13.2047 2.78254 11.813 3.63643 10.9503C3.6374 10.9493 3.63935 10.9474 3.64032 10.9464L3.64907 10.9377L4.34833 10.2374C4.75777 9.82508 4.98827 9.26781 4.98827 8.6872V7.69035" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                <path d="M15.4981 14.9045V13.695H16.5226H15.4981H12.5488L15.4981 9.59375V13.695" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                <path d="M8.02539 11.0271C8.02539 10.2467 8.65883 9.61328 9.43919 9.61328C10.2196 9.61328 10.853 10.2467 10.853 11.0271C10.853 12.7941 8.02636 12.7941 8.02636 14.8861H10.853" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 className="text-base font-bold mb-0.5 sm:mb-1">24/7 Availability</h3>
                            <p className="text-xs text-gray-100/90">
                                Schedule lessons at your convenience, anytime, anywhere.
                            </p>
                        </div>
                    </div>

                    {/* Feature 3: Tajweed, Hifz & More */}
                    <div className="flex items-center text-left sm:text-center md:text-left sm:flex-col md:flex-row">
                        <div className="flex justify-center items-center bg-[#ffffff]/10 rounded-full w-12 h-12 flex-shrink-0 mr-4 sm:mr-0 sm:mb-3 md:mr-3 md:mb-0 md:bg-transparent md:w-auto md:h-auto">
                            <svg className="w-6 h-6 sm:w-8 sm:h-8 md:w-7 md:h-7" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 className="text-base font-bold mb-0.5 sm:mb-1">Tajweed, Hifz & More</h3>
                            <p className="text-xs text-gray-100/90">
                                Master Quran recitation, memorization, and Islamic studies.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}