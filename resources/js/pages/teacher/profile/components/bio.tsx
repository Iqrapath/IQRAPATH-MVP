import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Edit } from 'lucide-react';
import { useForm } from '@inertiajs/react';
import { toast } from 'sonner';
import BioModal from '../modals/bio-modal';

interface BioProps {
    user: {
        name: string;
        email: string;
        phone: string;
        location: string;
    };
}

export default function Bio({ user }: BioProps) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [currentUser, setCurrentUser] = useState(user);

    const { data, setData, put, processing } = useForm({
        name: user.name || '',
        email: user.email || '',
        phone: user.phone || '',
        location: user.location || '',
    });

    const handleSave = (formData: { name: string; email: string; phone: string; location: string }) => {
        setData(formData);
        put(route('teacher.profile.update-basic-info'), {
            preserveScroll: true,
            onSuccess: () => {
                setIsModalOpen(false);
                // Update the local state immediately for optimistic UI update
                setCurrentUser(prev => ({
                    ...prev,
                    ...formData
                }));
                // Show success toast
                toast.success('Profile updated successfully!', {
                    description: 'Your personal information has been saved.',
                });
            },
            onError: (errors) => {
                // Show error toast
                toast.error('Failed to update profile', {
                    description: Object.values(errors).flat().join(', '),
                });
            },
        });
    };
    return (
        <>
            <div className="bg-white rounded-xl shadow-md border">
                <div className="p-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-6">Profile Picture & Bio</h3>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <span className="text-sm font-medium text-gray-600">Name:</span>
                            <p className="text-base font-medium text-gray-900 mt-1">{currentUser.name}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-gray-600">Email:</span>
                            <p className="text-base font-medium text-gray-900 mt-1">{currentUser.email}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-gray-600">Phone:</span>
                            <p className="text-base font-medium text-gray-900 mt-1">{currentUser.phone || 'N/A'}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-gray-600">Location:</span>
                            <p className="text-base font-medium text-gray-900 mt-1">{currentUser.location || 'N/A'}</p>
                        </div>
                    </div>
                    <div className="flex justify-end mt-6">
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

            {/* Bio Modal */}
            <BioModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                user={user}
                onSave={handleSave}
                processing={processing}
            />
        </>
    );
}
