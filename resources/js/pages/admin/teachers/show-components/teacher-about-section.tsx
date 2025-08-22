import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Edit } from 'lucide-react';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';

interface TeacherProfile {
  id: number;
  bio?: string;
}

interface Props {
  profile: TeacherProfile | null;
  teacherName: string;
}

export default function TeacherAboutSection({ profile, teacherName }: Props) {
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [bioText, setBioText] = useState(profile?.bio || '');

  const bio = profile?.bio || 'No bio information available.';

  const handleSave = async () => {
    if (!profile?.id) {
      toast.error('Teacher profile not found');
      return;
    }

    setIsLoading(true);
    try {
      await router.patch(`/admin/teachers/${profile.id}/about`, 
        { bio: bioText.trim() },
        {
          preserveScroll: true,
          onSuccess: () => {
            toast.success('About section updated successfully');
            setIsEditModalOpen(false);
          },
          onError: (errors) => {
            const errorMessage = Object.values(errors).flat().join(', ');
            toast.error(errorMessage || 'Failed to update about section');
          }
        }
      );
    } catch (error) {
      toast.error('Failed to update about section');
    } finally {
      setIsLoading(false);
    }
  };

  const openEditModal = () => {
    setBioText(profile?.bio || '');
    setIsEditModalOpen(true);
  };

  return (
    <>
      <Card className="mb-8 shadow-sm">
        <CardContent className="p-6">
          <div className="flex-1">
            <h3 className="text-lg font-bold text-black mb-4">About:</h3>
            <div className="text-sm text-gray-600 space-y-3">
              <p>{bio}</p>
            </div>
            <div className="flex justify-end mt-4">
              <Button 
                variant="link" 
                className="text-sm p-0 h-auto text-teal-600 hover:text-teal-700 cursor-pointer"
                onClick={openEditModal}
              >
                Edit
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Edit About Modal */}
      <Dialog open={isEditModalOpen} onOpenChange={setIsEditModalOpen}>
        <DialogContent className="sm:max-w-[600px]">
          <DialogHeader>
            <DialogTitle className="text-xl font-semibold text-gray-900">
              About {teacherName}
            </DialogTitle>
          </DialogHeader>

          <div className="py-4">
            <Textarea
              placeholder="Dedicated Quran teacher with 10+ years of experience in Hifz and Tajweed."
              value={bioText}
              onChange={(e) => setBioText(e.target.value)}
              className="min-h-[200px] resize-none bg-gray-50 border-gray-200 focus:bg-white focus:border-teal-500 focus:ring-teal-500"
              maxLength={1000}
            />
          </div>

          <DialogFooter className="flex justify-end space-x-3 pt-6">
            <Button
              variant="outline"
              onClick={() => setIsEditModalOpen(false)}
              disabled={isLoading}
              className="px-6"
            >
              Cancel
            </Button>
            <Button
              onClick={handleSave}
              disabled={isLoading}
              className="px-6 bg-teal-600 hover:bg-teal-700 text-white"
            >
              {isLoading ? 'Saving...' : 'Save and Continue'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}
