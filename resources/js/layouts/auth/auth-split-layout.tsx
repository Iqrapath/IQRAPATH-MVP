import AppLogoIcon from '@/components/app-logo-icon';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { useState, useEffect, type PropsWithChildren } from 'react';

interface AuthLayoutProps {
    title?: string;
    description?: string;
}

export default function AuthSplitLayout({ children, title, description }: PropsWithChildren<AuthLayoutProps>) {
    const { name, quote } = usePage<SharedData>().props;
    const [currentImageIndex, setCurrentImageIndex] = useState(0);

    const carouselImages = [
        '/assets/images/auth/e6cf1cb683a55e67e8aa299255defc36.png',
        '/assets/images/auth/side-view-islamic-man-typing.png',
        '/assets/images/auth/muslim-lady-wear-headphone-using-laptop-talk-colleagues-about-sale-report-conference-video-call-while-working-from-home-office-night 1.png',
    ];

    const carouselContent = [
        {
            heading: "Welcome to IqraPath!",
            text: "Find expert Quran teachers for Hifz, Tajweed, Hadith, and more."
        },
        {
            heading: "Learn from Qualified Teachers",
            text: "We verify every teacher to ensure quality and trust."
        },
        {
            heading: "Secure Payments & Ratings",
            text: "Your payment is safe and only released after lessons are completed."
        }
    ];

    // Auto-scroll carousel images
    useEffect(() => {
        const interval = setInterval(() => {
            setCurrentImageIndex((prevIndex) =>
                prevIndex === carouselImages.length - 1 ? 0 : prevIndex + 1
            );
        }, 5000); // Change image every 5 seconds

        return () => clearInterval(interval);
    }, []);

    // Function to handle manual indicator clicks
    const handleIndicatorClick = (index: number) => {
        setCurrentImageIndex(index);
    };

    return (
        <div className="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            <div className="relative hidden h-full flex-col p-10 text-white lg:flex">
                <div className="absolute inset-0 " />

                {/* Vertical rectangle in the middle of the left side - base image */}
                <div className="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 z-10">
                    <img
                        src="/assets/images/auth/verticalRectangle.png"
                        alt="Vertical Rectangle"
                        className="w-[800px] h-auto object-contain"
                        style={{ width: "1000px !important", maxWidth: "120%" }}
                    />
                </div>

                {/* Second image - vertical rectangle with carousel auth images as background */}
                <div className="absolute left-[55%] top-1/2 -translate-x-1/2 -translate-y-1/2 z-20">
                    <div className="relative" style={{
                        width: "400px",
                        height: "auto",
                        filter: "drop-shadow(0px 10px 25px rgba(253, 252, 252, 0.5))"
                    }}>
                        <img
                            src="/assets/images/auth/verticalRectangle.png"
                            alt="Vertical Rectangle Shape"
                            className="w-full h-auto"
                            style={{ opacity: 0 }}
                        />

                        {carouselImages.map((src, index) => (
                            <div
                                key={index}
                                className="absolute inset-0 transition-opacity duration-1000 ease-in-out"
                                style={{
                                    opacity: currentImageIndex === index ? 1 : 0,
                                    maskImage: "url('/assets/images/auth/verticalRectangle.png')",
                                    maskSize: "contain",
                                    maskRepeat: "no-repeat",
                                    WebkitMaskImage: "url('/assets/images/auth/verticalRectangle.png')",
                                    WebkitMaskSize: "contain",
                                    WebkitMaskRepeat: "no-repeat"
                                }}
                            >
                                <img
                                    src={src}
                                    alt={`Auth Image ${index + 1}`}
                                    className="w-full h-full object-cover"
                                />

                                {/* Text overlay */}
                                <div className="absolute bottom-20 left-0 right-0 p-6 flex flex-col items-center">
                                    <h2 className="text-white text-xl font-semibold text-center mb-2">
                                        {carouselContent[index].heading}
                                    </h2>
                                    <p className="text-white text-xs text-center max-w-[80%] mx-auto">
                                        {carouselContent[index].text}
                                    </p>
                                </div>
                            </div>
                        ))}

                        {/* Carousel indicators matching the image style - tilted 45 degrees */}
                        <div
                            className="absolute bottom-12 left-1/1 -translate-x-1/2 flex space-x-3 z-30"
                            style={{ transform: "rotate(-15deg)" }}
                        >
                            {carouselImages.map((_, index) => (
                                <button
                                    key={index}
                                    onClick={() => handleIndicatorClick(index)}
                                    className={`w-2 h-2 rounded-full transition-all duration-300 ${
                                        currentImageIndex === index ? 'bg-[#2B6B65]' : 'bg-[#D0E7E5]'
                                    }`}
                                    aria-label={`Go to slide ${index + 1}`}
                                />
                            ))}
                        </div>
                    </div>
                </div>

            </div>
            <div className="w-full lg:p-2">
                <div className="mx-auto flex w-full flex-col justify-start sm:w-[390px] ">
                    <Link href={route('home')} className="relative z-20 flex items-center justify-center lg:hidden">
                        <AppLogoIcon className="h-10 w-auto fill-current text-black sm:h-12" />
                    </Link>
                    {children}
                </div>
            </div>
        </div>
    );
}