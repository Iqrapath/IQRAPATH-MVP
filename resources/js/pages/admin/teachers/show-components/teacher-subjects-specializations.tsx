import React from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

interface TeacherProfile {
  subjects?: any[];
  experience_years?: number;
  teaching_type?: string;
  teaching_mode?: string;
  languages?: string[];
}

interface TeacherAvailability {
  day_name: string;
  time_range: string;
  is_active: boolean;
}

interface Props {
  profile: TeacherProfile | null;
  availabilities: TeacherAvailability[];
}

export default function TeacherSubjectsSpecializations({ profile, availabilities }: Props) {
  const subjectsList = profile?.subjects?.map(subject => subject.name).join(', ') || 'No subjects assigned';
  const experience = profile?.experience_years ? `${profile.experience_years} Years Experience teaching online and in madrasahs` : 'No experience specified';
  const teachingType = profile?.teaching_type || 'Not specified';
  const teachingMode = profile?.teaching_mode || 'Not specified';
  const languagesList = profile?.languages?.join(', ') || 'Not specified';

  // Format availabilities
  const formatAvailabilities = () => {
    if (!availabilities || availabilities.length === 0) {
      return 'No availability set';
    }

    const activeAvailabilities = availabilities.filter(av => av.is_active);
    if (activeAvailabilities.length === 0) {
      return 'No availability set';
    }

    // Format like the image: with bullet points for multiple days
    if (activeAvailabilities.length === 1) {
      return `${activeAvailabilities[0].day_name}: ${activeAvailabilities[0].time_range}`;
    }

    // For multiple days, format with bullet points
    return activeAvailabilities.map(av => `- ${av.day_name}: ${av.time_range}`).join('\n');
  };

  return (
    <Card className="mb-8 shadow-sm">
      <CardContent className="p-6">
        <div className="flex-1">
          <h3 className="text-lg font-bold text-gray-800 mb-4">Subjects & Specializations</h3>
          <div className="space-y-3 text-sm">
            <div className="flex">
              <span className="font-medium text-gray-700 w-40">Subjects Taught:</span>
              <span className="text-gray-600">{subjectsList}</span>
            </div>
            <div className="flex">
              <span className="font-medium text-gray-700 w-40">Teaching Experience:</span>
              <span className="text-gray-600">{experience}</span>
            </div>
            <div className="flex">
              <span className="font-medium text-gray-700 w-40">Availability Schedule:</span>
              <div className="text-gray-600 whitespace-pre-line">{formatAvailabilities()}</div>
            </div>
            <div className="flex">
              <span className="font-medium text-gray-700 w-40">Teaching Type:</span>
              <span className="text-gray-600">{teachingType}</span>
            </div>
            <div className="flex">
              <span className="font-medium text-gray-700 w-40">Teaching Mode:</span>
              <span className="text-gray-600">{teachingMode}</span>
            </div>
            <div className="flex">
              <span className="font-medium text-gray-700 w-40">Languages Spoken:</span>
              <span className="text-gray-600">{languagesList}</span>
            </div>
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
