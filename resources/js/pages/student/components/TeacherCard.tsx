import React from 'react';
import { MapPin, Star } from 'lucide-react';
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';

interface TeacherCardProps {
    name: string;
    subjects: string;
    location: string;
    rating: number; // 0-5
    price: string; // e.g. "$35 / session"
    avatarUrl: string;
}

export default function TeacherCard({ name, subjects, location, rating, price, avatarUrl }: TeacherCardProps) {
    return (
        <div className="bg-white border border-gray-100 rounded-3xl shadow-sm p-4 flex gap-4 items-center">
            <Avatar className="w-20 h-20 rounded-2xl">
                <AvatarImage src={avatarUrl} alt={name} className="object-cover" />
                <AvatarFallback className="bg-gradient-to-br from-teal-100 to-teal-200 text-teal-700 font-semibold text-lg rounded-2xl">
                    {name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()}
                </AvatarFallback>
            </Avatar>
            <div className="flex-1 min-w-0">
                <div className="text-lg font-semibold text-gray-900 truncate">{name}</div>
                <div className="text-xs text-gray-500 mt-1">Subject: {subjects}</div>
                <div className="flex items-center gap-2 text-xs text-gray-500 mt-1">
                    <MapPin className="w-3.5 h-3.5" /> {location}
                </div>
                <div className="flex items-center gap-2 mt-2 text-xs">
                    {[...Array(5)].map((_, i) => (
                        <Star key={i} className={`w-4 h-4 ${i < Math.round(rating) ? 'text-amber-400 fill-amber-400' : 'text-gray-300'}`} />
                    ))}
                    <span className="text-gray-600 ml-1">{rating.toFixed(1)}</span>
                </div>
                <div className="flex items-center justify-between mt-3">
                    <div>
                        <span className="bg-[#f4faf9] text-[#2c7870] rounded-full px-3 py-1 text-xs">{price}</span>
                    </div>
                    <div className="text-[#2c7870] text-xs font-medium cursor-pointer">View Profile</div>
                </div>
            </div>
        </div>
    );
}
