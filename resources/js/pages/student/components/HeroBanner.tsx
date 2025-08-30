import React from 'react';

interface HeroBannerProps {
    name: string;
    subtitle: string;
}

export default function HeroBanner({ name, subtitle }: HeroBannerProps) {
    return (
        <div className="relative rounded-[28px] bg-gradient-to-tr from-teal-600 via-teal-500 to-emerald-400 p-10 md:p-8 text-white overflow-hidden shadow min-h-[260px] md:min-h-[280px] md:max-w-3xl items-center justify-center mx-auto">
            <img
                src="/assets/images/bg-image.png"
                alt="decorative"
                className="pointer-events-none select-none absolute right-0 top-0 h-full w-auto opacity-100"
            />
            <img
                src="/assets/images/Vector-bg.png"
                alt="decorative"
                className="pointer-events-none select-none absolute right-0 top-0 h-full w-auto opacity-100"
            />

            <div className="relative">
                <h1 className="text-3xl md:text-2xl mb-4">Welcome {name}!</h1>
                <p className="max-w-3xl text-white/90 leading-relaxed">
                    Ready to start learning?
                </p>
            </div>
        </div>
    );
}
