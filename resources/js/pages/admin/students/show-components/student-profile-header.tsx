import React from 'react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { MapPin, Clock as ClockIcon } from 'lucide-react';
import { VerifiedIcon } from '@/components/icons/verified-icon';

interface Student {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  avatar: string | null;
  role: string;
  status: string;
  registration_date: string | null;
  location: string | null;
  guardian?: {
    id: number;
    name: string;
    email: string;
    phone: string | null;
  } | null;
  profile?: {
    date_of_birth: string | null;
    gender: string | null;
    grade_level: string | null;
    school_name: string | null;
    learning_goals: string | null;
    subjects_of_interest: string[] | null;
    preferred_learning_times: string[] | null;
    teaching_mode: string | null;
    additional_notes: string | null;
  } | null;
}

interface Props {
  student: Student;
}

export default function StudentProfileHeader({ student }: Props) {
  const getInitials = (name: string) => {
    return name
      .split(' ')
      .map(word => word[0])
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };

  const getStatusDisplay = () => {
    if (student.status === 'active') {
      return (
        <div className="flex items-center gap-1">
          <VerifiedIcon className="h-4 w-4 text-green-500" />
          <span className="text-green-600 text-sm">Verified</span>
        </div>
      );
    } else if (student.status === 'suspended') {
      return (
        <div className="flex items-center gap-1">
          <ClockIcon className="h-4 w-4 text-red-500" />
          <span className="text-red-600 text-sm">Suspended</span>
        </div>
      );
    } else {
      return (
        <div className="flex items-center gap-1">
          <ClockIcon className="h-4 w-4 text-yellow-500" />
          <span className="text-yellow-600 text-sm">Pending Verification</span>
        </div>
      );
    }
  };

  const getUserRole = () => {
    return student.role === 'student' ? 'Student' : 'Parent';
  };

  const getLocationInfo = () => {
    // For students, show guardian info if available
    if (student.role === 'student' && student.guardian) {
      return `Parent: ${student.guardian.name}`;
    }
    // For guardians or students without guardian, show location
    return student.location || 'Location not specified';
  };

  return (
    <div className="relative">
      {/* Green Header - Just background image, no styling */}
      <div className="h-32 rounded-t-xl overflow-hidden">
        <img
          src="/assets/admin/profile-bg.png"
          alt="Profile Background"
          className="w-full h-full object-cover"
          onError={(e) => {
            // Fallback to gradient if image fails to load
            e.currentTarget.style.display = 'none';
            e.currentTarget.nextElementSibling?.classList.add('bg-gradient-to-r', 'from-teal-500', 'to-emerald-400');
          }}
        />
      </div>

      {/* Main content area - White background */}
      <div className="rounded-b-xl p-6 -mt-16">
        {/* Avatar and Student Info - Left side */}
        <div className="flex justify-between items-start">
          {/* Left side: Avatar and Student Information */}
          <div className="flex flex-col items-start gap-4 ml-4">
            {/* Profile Picture - Overlaps green header */}
            <div className="flex-shrink-0 -mt-6 ml-10">
              <Avatar className="h-40 w-40 border-4 border-white shadow-lg">
                <AvatarImage src={student.avatar || undefined} alt={student.name} />
                <AvatarFallback className="text-2xl font-semibold bg-white text-teal-600">
                  {getInitials(student.name)}
                </AvatarFallback>
              </Avatar>
            </div>

            {/* Student Information - Below avatar on white background */}
            <div className="text-gray-900 flex flex-col items-center">
              <h1 className="text-2xl font-bold mb-1">{student.name}</h1>
              <p className="text-lg mb-0 text-gray-600">{getUserRole()}</p>
              <div className="flex items-center gap-1 mb-1">
                <MapPin className="h-4 w-4 text-gray-500" />
                <span className="text-gray-700">{getLocationInfo()}</span>
              </div>
              
              {/* Status Display */}
              {getStatusDisplay()}

              <div className="text-center text-sm text-gray-500 mt-2">
                Join on: {student.registration_date || 'Unknown'}
              </div>
            </div>
          </div>

          {/* Right side: Track Learning Progress Button - positioned on green background as in image */}
          <div className="flex-shrink-0 mt-4 mr-20">
            <Button 
              variant="link"
              onClick={() => {
                window.location.href = `/admin/students/${student.id}/learning-progress`;
              }}
              className="bg-white text-teal-600 hover:bg-gray-50 px-6 py-3 rounded-lg shadow-md h-12 text-sm font-semibold cursor-pointer"
            >
              Track Learning Progress
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
}
