import { Head } from '@inertiajs/react';
import TeacherLayout from '@/layouts/teacher/teacher-layout';
import ProfileSummary from './components/profile-summary';
import Bio from './components/bio';
import AboutMe from './components/about-me';
import TeachingSubject from './components/teaching-subject';
import Availability from './components/availability';
import IntroVideo from './components/intro-video';

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
        bio: string;
        experience_years: string;
        languages: string[] | string;
        teaching_type: string;
        teaching_mode: string;
        education: string;
        qualification: string;
        certifications: string;
        intro_video_url?: string | null;
    
        custom_subjects?: string[];
    } | null;
    subjects: {
        id: number;
        name: string;
        is_selected: boolean;
    }[];
    documents: {
        certificates: Array<{
            id: number;
            name: string;
            status: string;
            metadata: {
                issuer?: string;
                issue_date?: string;
            };
            created_at: string;
            verified_at?: string;
            rejection_reason?: string;
        }>;
    };
    availabilities: {
        available_days: Array<{
            id: number;
            name: string;
            is_selected: boolean;
        }>;
        preferred_hours: string;
        availability_type: string;
        time_zone: string;
    };
    reviews: Review[];
}

export default function TeacherProfile({ user, profile, subjects, documents, availabilities, reviews }: TeacherProfileProps) {
    return (
        <TeacherLayout pageTitle="Profile Settings">
            <Head title="Profile Settings" />

            <div className="container mx-auto py-6 px-4">
                <h1 className="text-3xl font-bold mb-6">Profile Settings</h1>

                <div className="space-y-6">
                    {/* Profile Summary */}
                    <ProfileSummary user={user} profile={profile} reviews={reviews} />

                    {/* Profile Picture & Bio */}
                    <Bio user={user} />

                                         {/* About Me */}
                     <AboutMe profile={profile} />

                     {/* Intro Video */}
                     <IntroVideo intro_video_url={profile?.intro_video_url} />

                     {/* Teaching Subjects & Expertise */}
                     <TeachingSubject 
                         subjects={subjects}
                         experience_years={profile?.experience_years || ''}
                         documents={documents}
                     />

                     {/* Availability & Time Zone */}
                     <Availability 
                         available_days={availabilities.available_days}
                         preferred_teaching_hours={availabilities.preferred_hours}
                         available_time={availabilities.availability_type}
                         time_zone={availabilities.time_zone}
                     />
                </div>
            </div>
        </TeacherLayout>
    );
}
