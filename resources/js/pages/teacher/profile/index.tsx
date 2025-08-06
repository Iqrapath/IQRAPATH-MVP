import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import TeacherLayout from '@/layouts/teacher/teacher-layout';
import { CheckCircle, Edit, MapPin, Star, Upload, VerifiedIcon, Camera, X } from 'lucide-react';
import { FormEventHandler } from 'react';

interface Review {
    id: number;
    rating: number;
    review: string | null;
    created_at: string;
    formatted_date?: string;
    student: {
        id: number;
        name: string;
        avatar?: string | null;
    };
}

interface TeacherProfileProps {
    user: {
        id: number;
        name: string;
        email: string;
        phone: string;
        avatar: string | null;
        location: string;
        created_at: string;
    };
    profile: {
        verified: boolean;
        rating: number;
        reviews_count: number;
        formatted_rating: string;
        join_date: string | null;
    } | null;
    reviews: Review[];
}

export default function TeacherProfile({ user, profile, reviews }: TeacherProfileProps) {
    const getInitials = (name: string) => {
        return name.split(' ').map(n => n[0]).join('').toUpperCase();
    };

    const [avatarPreview, setAvatarPreview] = useState<string | null>(user.avatar || null);

    const { data, setData, post, processing, errors } = useForm({
        avatar: user.avatar || null as File | null,
    });

    const formatJoinDate = (dateString: string | null) => {
        if (!dateString) return null;

        const date = new Date(dateString);

        // Check if the date is valid
        if (isNaN(date.getTime())) {
            return null;
        }

        const day = date.getDate();
        const month = date.toLocaleDateString('en-US', { month: 'long' });
        const year = date.getFullYear();

        // Add ordinal suffix to day
        const getOrdinalSuffix = (day: number) => {
            if (day > 3 && day < 21) return 'th';
            switch (day % 10) {
                case 1: return 'st';
                case 2: return 'nd';
                case 3: return 'rd';
                default: return 'th';
            }
        };

        return `${day}${getOrdinalSuffix(day)} ${month}, ${year}`;
    };

    const joinDate = formatJoinDate(profile?.join_date || user.created_at);

    const handleAvatarChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setData('avatar', file);
            const reader = new FileReader();
            reader.onload = (e) => {
                setAvatarPreview(e.target?.result as string);
            };
            reader.readAsDataURL(file);
        }
    };

    const removeAvatar = () => {
        setData('avatar', null);
        setAvatarPreview(null);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('teacher.profile.update-avatar'), {
            preserveScroll: true,
            forceFormData: true,
        });
    };

    return (
        <TeacherLayout pageTitle="Profile Settings">
            <Head title="Profile Settings" />

            <div className="container mx-auto py-6 px-4">
                <h1 className="text-3xl font-bold mb-6">Profile Settings</h1>

                <div className="space-y-6">
                    {/* Profile Summary */}
                    <form onSubmit={submit} className="space-y-6">
                        <div className="bg-white rounded-4xl shadow-md p-8">
                            <div className="flex items-start">
                                {/* Left Side - Profile Picture */}
                                <div className="flex flex-col items-center mr-12">
                                    <div className="relative">
                                        <Avatar className="h-28 w-28 overflow-hidden rounded-full">
                                            {avatarPreview ? (
                                                <AvatarImage src={avatarPreview} alt={user.name} className="object-cover" />
                                            ) : (
                                                <AvatarFallback className="text-2xl">
                                                    {getInitials(user.name)}
                                                </AvatarFallback>
                                            )}
                                        </Avatar>
                                        <div className="absolute bottom-0 right-0 flex gap-1">
                                            <label htmlFor="avatar-upload" className="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-primary text-white shadow-md hover:bg-primary/90">
                                                <Camera size={16} />
                                                <input
                                                    id="avatar-upload"
                                                    type="file"
                                                    className="hidden"
                                                    onChange={handleAvatarChange}
                                                    accept="image/*"
                                                />
                                            </label>
                                            {avatarPreview && (
                                                <button
                                                    type="button"
                                                    onClick={removeAvatar}
                                                    className="flex h-8 w-8 items-center justify-center rounded-full bg-destructive text-white shadow-md hover:bg-destructive/90"
                                                >
                                                    <X size={16} />
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                    {data.avatar && (
                                        <div className="flex items-center gap-4 mt-4">
                                            <Button disabled={processing} className="bg-[#338078] text-white hover:bg-[#338078]/90">Upload</Button>
                                        </div>
                                    )}
                                </div>

                                {/* Vertical Separator */}
                                <div className="w-px bg-gray-300 mx-8 self-stretch"></div>

                                {/* Right Side - User Details */}
                                <div className="flex-1 pl-8">
                                    <div className="flex justify-between items-start mb-6">
                                        <div className="space-y-1">
                                            <h2 className="text-3xl font-bold text-gray-900">{user.name}</h2>
                                            {joinDate && <p className="text-gray-500 text-sm">Joined: {joinDate}</p>}
                                        </div>
                                        {profile?.verified && (
                                            <div className="bg-[#E4FFFC] text-[#338078] px-3 py-1 rounded-full flex items-center gap-2 text-lg font-medium">
                                                <VerifiedIcon className="h-6 w-6" />
                                                <span>Verified</span>
                                            </div>
                                        )}
                                    </div>

                                    <div className="space-y-4">
                                        <div className="flex items-center space-x-3">
                                            <div className="flex items-center space-x-1">
                                                <Star className="h-5 w-5 text-yellow-400 fill-current" />
                                                <Star className="h-5 w-5 text-yellow-400 fill-current" />
                                                <Star className="h-5 w-5 text-yellow-400 fill-current" />
                                                <Star className="h-5 w-5 text-yellow-400 fill-current" />
                                                <Star className="h-5 w-5 text-yellow-400 fill-current" />
                                            </div>
                                            <span className="font-medium text-gray-900">{profile?.formatted_rating} ({profile?.reviews_count} Reviews)</span>
                                        </div>

                                        <div className="flex items-center">
                                            <MapPin className="h-4 w-4 mr-2 text-gray-600" />
                                            <span className="font-medium text-[#338078]">{user.location || 'N/A'} </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Reviews Section */}
                        <div className="bg-white rounded-xl shadow p-6 mt-6">
                            <h3 className="text-xl font-semibold mb-4">Recent Reviews</h3>
                            {reviews.length === 0 ? (
                                <p className="text-gray-500">No reviews yet.</p>
                            ) : (
                                <div className="space-y-6">
                                    {reviews.map((review) => (
                                        <div key={review.id} className="border-b pb-4 last:border-b-0 last:pb-0">
                                            <div className="flex items-center gap-3 mb-1">
                                                <Avatar className="h-10 w-10">
                                                    {review.student.avatar ? (
                                                        <AvatarImage src={review.student.avatar} alt={review.student.name} />
                                                    ) : (
                                                        <AvatarFallback>{getInitials(review.student.name)}</AvatarFallback>
                                                    )}
                                                </Avatar>
                                                <div>
                                                    <div className="font-semibold text-gray-900">{review.student.name}</div>
                                                    <div className="text-xs text-gray-500">{review.formatted_date || formatJoinDate(review.created_at)}</div>
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-2 mb-1">
                                                {[1,2,3,4,5].map((star) => (
                                                    <Star key={star} className={`h-4 w-4 ${star <= review.rating ? 'text-yellow-400 fill-current' : 'text-gray-300'}`} />
                                                ))}
                                                <span className="ml-2 text-sm text-gray-700">{review.rating} / 5</span>
                                            </div>
                                            {review.review && (
                                                <div className="text-gray-800 text-sm mt-1">{review.review}</div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </form>

                    {/* Profile Picture & Bio */}
                    <div className="bg-white rounded-xl shadow-md border">
                        <div className="p-6 border-b border-gray-200">
                            <div className="flex items-center justify-between">
                                <h3 className="text-lg font-semibold text-gray-900">Profile Picture & Bio</h3>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="text-green-600 border-green-600 hover:bg-green-50"
                                >
                                    <Edit className="h-4 w-4 mr-2" />
                                    Edit
                                </Button>
                            </div>
                        </div>
                        <div className="p-6">
                            <div className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <span className="text-sm font-medium text-gray-600">Name:</span>
                                        <p className="text-sm text-gray-900">{user.name}</p>
                                    </div>
                                    <div>
                                        <span className="text-sm font-medium text-gray-600">Email:</span>
                                        <p className="text-sm text-gray-900">{user.email}</p>
                                    </div>
                                    <div>
                                        <span className="text-sm font-medium text-gray-600">Phone:</span>
                                        <p className="text-sm text-gray-900">{user.phone}</p>
                                    </div>
                                    <div>
                                        <span className="text-sm font-medium text-gray-600">Location:</span>
                                        <p className="text-sm text-gray-900">{user.location}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </TeacherLayout>
    );
}
