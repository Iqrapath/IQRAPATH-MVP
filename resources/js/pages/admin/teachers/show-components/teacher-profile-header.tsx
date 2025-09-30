import React from 'react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { MapPin, Wallet, DollarSign, Clock, CheckCircle, XCircle, Clock as ClockIcon } from 'lucide-react';
import { VerifiedIcon } from '@/components/icons/verified-icon';
import { PeopleIcon } from '@/components/icons/people-icon';
import { ProgressCheckIcon } from '@/components/icons/progress-check-icon';

interface Teacher {
  id: number;
  name: string;
  avatar: string | null;
  location: string;
}

interface TeacherProfile {
  verified: boolean;
}

interface TeacherEarnings {
  wallet_balance: number;
  total_earned: number;
  total_withdrawn: number;
  pending_payouts: number;
}

interface VerificationStatus {
  docs_status: 'pending' | 'verified' | 'rejected';
  video_status: 'not_scheduled' | 'scheduled' | 'completed' | 'passed' | 'failed';
}

interface Props {
  teacher: Teacher;
  profile: TeacherProfile | null;
  earnings?: TeacherEarnings | null;
  verificationStatus?: VerificationStatus;
}

export default function TeacherProfileHeader({ teacher, profile, earnings, verificationStatus }: Props) {
  const getInitials = (name: string) => {
    return name
      .split(' ')
      .map(word => word[0])
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-NG', {
      style: 'currency',
      currency: 'NGN',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(amount);
  };

  const getVerificationStatusDisplay = () => {
    if (!verificationStatus) return null;

    const { docs_status, video_status } = verificationStatus;

    // Handle rejected states first
    if (docs_status === 'rejected') {
      return (
        <div className="flex items-center gap-1">
          <XCircle className="h-4 w-4 text-red-500" />
          <span className="text-red-600 text-sm">Documents Rejected</span>
        </div>
      );
    }

    if (video_status === 'failed') {
      return (
        <div className="flex items-center gap-1">
          <XCircle className="h-4 w-4 text-red-500" />
          <span className="text-red-600 text-sm">Video Verification Failed</span>
        </div>
      );
    }

    // Handle fully verified state
    if (docs_status === 'verified' && video_status === 'passed') {
      return (
        <div className="flex items-center gap-1">
          <VerifiedIcon className="h-4 w-4 text-green-500" />
          <span className="text-green-600 text-sm">Fully Verified</span>
        </div>
      );
    }

    // Show verification progress with individual status indicators
    const getDocsIcon = () => {
      if (docs_status === 'verified') {
        return <CheckCircle className="h-4 w-4 text-green-500" />;
      }
      if ((docs_status as string) === 'rejected') {
        return <XCircle className="h-4 w-4 text-red-500" />;
      }
      // Use type assertion to avoid linter error
      return <ClockIcon className="h-4 w-4 text-yellow-500" />;
    };

    const getDocsColor = () => {
      if (docs_status === 'verified') {
        return 'text-green-600';
      }
      if ((docs_status as string) === 'rejected') {
        return 'text-red-600';
      }
      // Use type assertion to avoid linter error
      return 'text-yellow-600';
    };

    const getVideoIcon = () => {
      if (video_status === 'passed') {
        return <CheckCircle className="h-4 w-4 text-green-500" />;
      }
      if ((video_status as string) === 'failed') {
        return <XCircle className="h-4 w-4 text-red-500" />;
      }
      if (video_status === 'scheduled') {
        return <ClockIcon className="h-4 w-4 text-blue-500" />;
      }
      if (video_status === 'completed') {
        return <ClockIcon className="h-4 w-4 text-orange-500" />;
      }
      // Use type assertion to avoid linter error
      return <ClockIcon className="h-4 w-4 text-gray-400" />;
    };

    const getVideoColor = () => {
      if (video_status === 'passed') {
        return 'text-green-600';
      }
      if ((video_status as string) === 'failed') {
        return 'text-red-600';
      }
      if (video_status === 'scheduled') {
        return 'text-blue-600';
      }
      if (video_status === 'completed') {
        return 'text-orange-600';
      }
      // Use type assertion to avoid linter error
      return 'text-gray-500';
    };

    return (
      <div className="flex items-center gap-2">
        <div className="flex items-center gap-1">
          {getDocsIcon()}
          <span className={`text-xs ${getDocsColor()}`}>
            Docs: {docs_status}
          </span>
        </div>
        <div className="flex items-center gap-1">
          {getVideoIcon()}
          <span className={`text-xs ${getVideoColor()}`}>
            Video: {video_status}
          </span>
        </div>
      </div>
    );
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
      <div className="rounded-b-xl p-4 sm:p-6 -mt-16">
        {/* Avatar and Teacher Info - Responsive layout */}
        <div className="flex flex-col lg:flex-row lg:justify-between lg:items-start gap-6">
          {/* Left side: Avatar and Teacher Information */}
          <div className="flex flex-col items-center lg:items-start gap-4">
            {/* Profile Picture - Overlaps green header */}
            <div className="flex-shrink-0 -mt-6">
              <Avatar className="h-32 w-32 sm:h-40 sm:w-40 border-4 border-white shadow-lg">
                <AvatarImage src={teacher.avatar || undefined} alt={teacher.name} />
                <AvatarFallback className="text-xl sm:text-2xl font-semibold bg-white text-teal-600">
                  {getInitials(teacher.name)}
                </AvatarFallback>
              </Avatar>
            </div>

            {/* Teacher Information - Below avatar on white background */}
            <div className="text-gray-900 flex flex-col items-center lg:items-start text-center lg:text-left">
              <h1 className="text-xl sm:text-2xl font-bold mb-1">{teacher.name}</h1>
              <p className="text-base sm:text-lg mb-0 text-gray-600">Teacher</p>
              <div className="flex items-center gap-1 mb-1">
                <MapPin className="h-4 w-4 text-gray-500" />
                <span className="text-gray-700 text-sm sm:text-base">{teacher.location || 'Location not specified'}</span>
              </div>
              
              {/* Enhanced Verification Status Display */}
              {verificationStatus ? (
                getVerificationStatusDisplay()
              ) : profile?.verified ? (
                <div className="flex items-center gap-1">
                  <VerifiedIcon className="h-4 w-4 text-green-500" />
                  <span className="text-green-600 text-sm">Verified</span>
                </div>
              ) : (
                <div className="flex items-center gap-1">
                  <ClockIcon className="h-4 w-4 text-yellow-500" />
                  <span className="text-yellow-600 text-sm">Pending Verification</span>
                </div>
              )}
            </div>
          </div>

          {/* Right side: Earnings Card - Also overlaps green header */}
          <div className="flex-shrink-0 -mt-4 lg:-mt-4 w-full lg:w-auto">
            {earnings ? (
              <Card className="w-full lg:w-auto shadow-xl bg-white">
                <CardHeader className="pb-3">
                  <CardTitle className="text-base sm:text-lg font-semibold">Earnings</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div className="text-center p-3 bg-blue-50 rounded-lg">
                      <div className="flex items-center justify-between mb-1">
                        <PeopleIcon className="h-4 w-4 sm:h-5 sm:w-4 text-blue-600" />
                        <span className="font-bold text-blue-600 text-sm sm:text-base">
                          {formatCurrency(earnings.wallet_balance)}
                        </span>
                      </div>
                      <div className="text-xs text-gray-600">Wallet Balance</div>
                    </div>
                    <div className="text-center p-3 bg-green-50 rounded-lg">
                      <div className="flex items-center justify-between mb-1">
                        <PeopleIcon className="h-4 w-4 sm:h-5 sm:w-4 text-green-600" />
                        <span className="font-bold text-green-600 text-sm sm:text-base">
                          {formatCurrency(earnings.total_earned)}
                        </span>
                      </div>
                      <div className="text-xs text-gray-600">Total Earned</div>
                    </div>
                    <div className="text-center p-3 bg-yellow-50 rounded-lg">
                      <div className="flex items-center justify-between mb-1">
                        <ProgressCheckIcon className="h-4 w-4 sm:h-5 sm:w-4 text-yellow-600" />
                        <span className="font-bold text-yellow-600 text-sm sm:text-base">
                          {formatCurrency(earnings.pending_payouts)}
                        </span>
                      </div>
                      <div className="text-xs text-gray-600">Pending Payouts</div>
                    </div>
                  </div>
                  {teacher.id > 0 && (
                    <div className="text-center lg:text-right">
                      <Button 
                        variant="link" 
                        className="text-sm p-0 h-auto text-teal-600 hover:text-teal-700"
                        onClick={() => window.location.href = route('admin.teachers.earnings', teacher.id)}
                      >
                        View Teacher Earnings
                      </Button>
                    </div>
                  )}
                </CardContent>
              </Card>
            ) : (
              <Card className="w-full lg:w-auto shadow-xl bg-white">
                <CardHeader className="pb-3">
                  <CardTitle className="text-base sm:text-lg font-semibold">Earnings</CardTitle>
                </CardHeader>
                <CardContent className="p-4 sm:p-6 text-center">
                  <p className="text-gray-500 text-sm sm:text-base">No earnings data available</p>
                </CardContent>
              </Card>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
