import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Edit } from 'lucide-react';
import { useForm } from '@inertiajs/react';
import { toast } from 'sonner';
import AboutMeModal from '../modals/about-me-modal';

interface AboutMeProps {
    profile: {
        bio: string;
        experience_years: string;
        languages: string[] | string;
        teaching_type: string;
        teaching_mode: string;
        education: string;
        qualification: string;
    } | null;
}

export default function AboutMe({ profile }: AboutMeProps) {
    // Ensure languages is always an array
    const getLanguagesArray = (languages: any): string[] => {
        if (Array.isArray(languages)) {
            return languages;
        }
        if (typeof languages === 'string') {
            try {
                const parsed = JSON.parse(languages);
                return Array.isArray(parsed) ? parsed : [];
            } catch {
                return [];
            }
        }
        return [];
    };

    const [isModalOpen, setIsModalOpen] = useState(false);
    const [currentProfile, setCurrentProfile] = useState(profile);

    const { data, setData, put, processing } = useForm({
        bio: profile?.bio || '',
        experience_years: profile?.experience_years || '',
        languages: getLanguagesArray(profile?.languages),
        teaching_type: profile?.teaching_type || '',
        teaching_mode: profile?.teaching_mode || '',
        education: profile?.education || '',
        qualification: profile?.qualification || '',
    });

    const handleSave = (formData: {
        bio: string;
        experience_years: string;
        languages: string[];
        teaching_type: string;
        teaching_mode: string;
        education: string;
        qualification: string;
    }) => {
        setData(formData);
        put(route('teacher.profile.update-teacher-info'), {
            preserveScroll: true,
            onSuccess: () => {
                setIsModalOpen(false);
                // Update the local state immediately for optimistic UI update
                setCurrentProfile(prev => ({
                    ...prev,
                    ...formData
                }));
                // Show success toast
                toast.success('About Me updated successfully!', {
                    description: 'Your profile information has been saved.',
                });
            },
            onError: (errors) => {
                // Show error toast
                toast.error('Failed to update About Me', {
                    description: Object.values(errors).flat().join(', '),
                });
            },
        });
    };

    return (
        <>
            <div className="bg-white rounded-xl shadow-md border">
                <div className="p-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-6">About Me</h3>
                    <div className="mb-6 space-y-4">
                        {/* Bio */}
                        {currentProfile?.bio && (
                            <div>
                                <p className="text-base text-gray-900 leading-relaxed">
                                    {currentProfile.bio}
                                </p>
                            </div>
                        )}
                        
                        {/* Combined Information */}
                        {currentProfile && (currentProfile.experience_years || currentProfile.teaching_type || currentProfile.teaching_mode || currentProfile.education || currentProfile.qualification || (getLanguagesArray(currentProfile.languages).length > 0)) && (
                            <div className="pt-4 border-t border-gray-100">
                                <p className="text-sm text-gray-700 leading-relaxed">
                                    {[
                                        currentProfile.experience_years && `I have ${currentProfile.experience_years} years of teaching experience`,
                                        currentProfile.teaching_type && `specializing in ${currentProfile.teaching_type} and`,
                                        currentProfile.teaching_mode && ` ${currentProfile.teaching_mode} instruction`,
                                        currentProfile.education && `with a background in ${currentProfile.education}`,
                                        currentProfile.qualification && `and ${currentProfile.qualification}`,
                                        getLanguagesArray(currentProfile.languages).length > 0 && `I am fluent in ${getLanguagesArray(currentProfile.languages).join(', ')}`
                                    ].filter(Boolean).join('. ')}
                                    {(currentProfile.experience_years || currentProfile.teaching_type || currentProfile.teaching_mode || currentProfile.education || currentProfile.qualification || (getLanguagesArray(currentProfile.languages).length > 0)) ? '.' : ''}
                                </p>
                            </div>
                        )}
                        
                        {/* Show message if no information */}
                        {!currentProfile?.bio && !currentProfile?.experience_years && !currentProfile?.teaching_type && !currentProfile?.teaching_mode && !currentProfile?.education && !currentProfile?.qualification && getLanguagesArray(currentProfile?.languages).length === 0 && (
                            <p className="text-base text-gray-500 italic">
                                No information available. Click Edit to add your details.
                            </p>
                        )}
                    </div>
                    <div className="flex justify-end">
                        <Button
                            variant="outline"
                            size="sm"
                            className="text-green-600 border-green-600 hover:bg-green-50"
                            onClick={() => setIsModalOpen(true)}
                        >
                            <Edit className="h-4 w-4 mr-2" />
                            Edit
                        </Button>
                    </div>
                </div>
            </div>

            {/* About Me Modal */}
            <AboutMeModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                profile={profile ? {
                    ...profile,
                    languages: getLanguagesArray(profile.languages)
                } : null}
                onSave={handleSave}
                processing={processing}
            />
        </>
    );
}
