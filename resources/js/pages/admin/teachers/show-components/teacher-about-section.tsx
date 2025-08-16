import React from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

interface TeacherProfile {
  bio?: string;
}

interface Props {
  profile: TeacherProfile | null;
}

export default function TeacherAboutSection({ profile }: Props) {
  const bio = profile?.bio || 'No bio information available.';

  return (
    <Card className="mb-8 shadow-sm">
      <CardContent className="p-6">
        <div className="flex-1">
          <h3 className="text-lg font-bold text-black mb-4">About:</h3>
          <div className="text-sm text-gray-600 space-y-3">
            <p>{bio}</p>
          </div>
          <div className="flex justify-end mt-4">
            <Button variant="link" className="text-sm p-0 h-auto text-gray-500 hover:text-gray-700" disabled>
              Edit
            </Button>
            </div>
        </div>
      </CardContent>
    </Card>
  );
}
