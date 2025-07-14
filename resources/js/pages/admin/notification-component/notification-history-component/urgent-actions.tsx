import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { CheckCircle2 } from 'lucide-react';
import { useState, useEffect } from 'react';

// CSS for SweetAlert2-style animation
const sweetAlertStyles = `
  @keyframes swal2-animate-success-line-tip {
    0% {
      top: 19px;
      left: 1px;
      width: 0;
    }
    54% {
      top: 17px;
      left: 2px;
      width: 0;
    }
    70% {
      top: 35px;
      left: -6px;
      width: 54px;
    }
    84% {
      top: 48px;
      left: 21px;
      width: 17px;
    }
    100% {
      top: 45px;
      left: 14px;
      width: 25px;
    }
  }
  
  @keyframes swal2-animate-success-line-long {
    0% {
      top: 54px;
      right: 46px;
      width: 0;
    }
    65% {
      top: 54px;
      right: 46px;
      width: 0;
    }
    84% {
      top: 35px;
      right: 0px;
      width: 55px;
    }
    100% {
      top: 38px;
      right: 8px;
      width: 47px;
    }
  }
  
  @keyframes swal2-rotate-success-circular-line {
    0% {
      transform: rotate(-45deg);
    }
    5% {
      transform: rotate(-45deg);
    }
    12% {
      transform: rotate(-405deg);
    }
    100% {
      transform: rotate(-405deg);
    }
  }
  
  @keyframes swal2-animate-success-ring {
    0% {
      transform: scale(0.7);
      opacity: 0;
    }
    50% {
      transform: scale(1);
      opacity: 1;
    }
    100% {
      transform: scale(1);
      opacity: 1;
    }
  }
`;

interface UrgentActionsProps {
  urgentActions: {
    withdrawalRequests: number;
    teacherApplications: number;
    pendingSessions: number;
    reportedDisputes: number;
  };
}

export default function UrgentActions({ urgentActions }: UrgentActionsProps) {
  const [isVisible, setIsVisible] = useState(false);
  const [isAnimating, setIsAnimating] = useState(false);
  
  // Check if there are any urgent actions
  const hasUrgentActions = 
    urgentActions.withdrawalRequests > 0 ||
    urgentActions.teacherApplications > 0 ||
    urgentActions.pendingSessions > 0 ||
    urgentActions.reportedDisputes > 0;
  
  // Animation effect for initial render
  useEffect(() => {
    const timer = setTimeout(() => {
      setIsVisible(true);
    }, 100);
    
    return () => clearTimeout(timer);
  }, []);
  
  // SweetAlert2-style animation every 10 seconds
  useEffect(() => {
    if (!hasUrgentActions) {
      // Initial animation after component mounts
      const initialAnimation = setTimeout(() => {
        setIsAnimating(true);
        setTimeout(() => setIsAnimating(false), 2000);
      }, 1000);
      
      // Repeating animation every 10 seconds
      const interval = setInterval(() => {
        setIsAnimating(true);
        setTimeout(() => setIsAnimating(false), 2000);
      }, 10000);
      
      return () => {
        clearTimeout(initialAnimation);
        clearInterval(interval);
      };
    }
  }, [hasUrgentActions]);
  
  // If no urgent actions, show a simplified message with animation
  if (!hasUrgentActions) {
    return (
      <div 
        className={`bg-gray-50 rounded-lg p-6 mb-8 shadow-sm transition-all duration-500 ease-in-out ${
          isVisible ? 'opacity-100 transform translate-y-0' : 'opacity-0 transform -translate-y-4'
        }`}
      >
        <style dangerouslySetInnerHTML={{ __html: sweetAlertStyles }} />
        
        <div className="flex justify-between items-center mb-4">
          <h2 className="text-lg font-semibold text-gray-700">System Status</h2>
        </div>
        
        <div className="flex items-center justify-center py-4">
          <div className="flex flex-col items-center text-center">
            {/* SweetAlert2-style success icon */}
            <div className="relative w-20 h-20 mb-3">
              {/* Success ring */}
              <div 
                className={`absolute w-full h-full border-4 border-green-100 rounded-full ${
                  isAnimating ? 'animate-[swal2-animate-success-ring_0.75s]' : ''
                }`}
              />
              
              {/* Circular line */}
              <div 
                className={`absolute w-full h-full border-4 border-green-100 rounded-full ${
                  isAnimating ? 'animate-[swal2-rotate-success-circular-line_4.25s_ease-in]' : ''
                }`}
                style={{ borderColor: 'rgba(165, 220, 134, 0.3)', borderTopColor: 'transparent', borderRightColor: 'transparent', transform: 'rotate(-45deg)' }}
              />
              
              {/* Success icon body */}
              <div className="absolute w-12 h-24 top-0 left-4 bg-transparent border-r-2 border-green-500 rounded-r-3xl" 
                style={{ transform: 'rotate(45deg)', transformOrigin: 'right bottom' }} 
              />
              
              {/* Success icon tip line */}
              <div 
                className={`absolute h-2 bg-green-500 rounded ${
                  isAnimating ? 'animate-[swal2-animate-success-line-tip_0.75s]' : ''
                }`}
                style={{ width: '25px', left: '14px', top: '45px', transform: 'rotate(45deg)' }}
              />
              
              {/* Success icon long line */}
              <div 
                className={`absolute h-2 bg-green-500 rounded ${
                  isAnimating ? 'animate-[swal2-animate-success-line-long_0.75s]' : ''
                }`}
                style={{ width: '47px', right: '8px', top: '38px', transform: 'rotate(-45deg)' }}
              />
            </div>
            
            <p className="text-gray-600 font-medium">All systems operational</p>
            <p className="text-gray-500 text-sm mt-1">There are no urgent actions requiring your attention at the moment.</p>
          </div>
        </div>
      </div>
    );
  }
  
  // Get total number of urgent actions
  const totalUrgentActions = 
    urgentActions.withdrawalRequests + 
    urgentActions.teacherApplications + 
    urgentActions.pendingSessions + 
    urgentActions.reportedDisputes;
  
  // Otherwise, show the urgent actions with animation
  return (
    <div 
      className={`bg-gray-50 rounded-lg p-6 mb-8 shadow-sm border-l-4 border-amber-500 transition-all duration-500 ease-in-out ${
        isVisible ? 'opacity-100 transform translate-y-0' : 'opacity-0 transform -translate-y-4'
      }`}
    >
      <div className="flex justify-between items-center mb-6">
        <div className="flex items-center">
          <h2 className="text-lg font-semibold text-gray-800">Urgent Actions Required</h2>
          <Badge variant="destructive" className="ml-3 px-2 py-0.5">
            {totalUrgentActions}
          </Badge>
        </div>
      </div>
      
      <div className="space-y-4">
        {urgentActions.withdrawalRequests > 0 && (
          <div className="flex items-center justify-between p-3 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-300 border-l-4 border-blue-500">
            <div className="flex items-center">
              <Badge variant="outline" className="mr-3 bg-blue-50 text-blue-700 border-blue-200">
                {urgentActions.withdrawalRequests}
              </Badge>
              <span className="text-gray-700 font-medium">Withdrawal Requests Pending Approval</span>
            </div>
            <Link href="/admin/withdrawal-requests">
              <Button variant="outline" size="sm" className="text-blue-600 border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                View Requests
              </Button>
            </Link>
          </div>
        )}
        
        {urgentActions.teacherApplications > 0 && (
          <div className="flex items-center justify-between p-3 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-300 border-l-4 border-purple-500">
            <div className="flex items-center">
              <Badge variant="outline" className="mr-3 bg-purple-50 text-purple-700 border-purple-200">
                {urgentActions.teacherApplications}
              </Badge>
              <span className="text-gray-700 font-medium">Teacher Applications Awaiting Verification</span>
            </div>
            <Link href="/admin/teacher-applications">
              <Button variant="outline" size="sm" className="text-purple-600 border-purple-200 hover:bg-purple-50 hover:text-purple-700">
                Review Now
              </Button>
            </Link>
          </div>
        )}
        
        {urgentActions.pendingSessions > 0 && (
          <div className="flex items-center justify-between p-3 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-300 border-l-4 border-teal-500">
            <div className="flex items-center">
              <Badge variant="outline" className="mr-3 bg-teal-50 text-teal-700 border-teal-200">
                {urgentActions.pendingSessions}
              </Badge>
              <span className="text-gray-700 font-medium">Sessions Pending Teacher Assignment</span>
            </div>
            <Link href="/admin/pending-sessions">
              <Button variant="outline" size="sm" className="text-teal-600 border-teal-200 hover:bg-teal-50 hover:text-teal-700">
                Assign Teachers
              </Button>
            </Link>
          </div>
        )}
        
        {urgentActions.reportedDisputes > 0 && (
          <div className="flex items-center justify-between p-3 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-300 border-l-4 border-red-500">
            <div className="flex items-center">
              <Badge variant="outline" className="mr-3 bg-red-50 text-red-700 border-red-200">
                {urgentActions.reportedDisputes}
              </Badge>
              <span className="text-gray-700 font-medium">Reported Dispute Requires Resolution</span>
            </div>
            <Link href="/admin/disputes">
              <Button variant="outline" size="sm" className="text-red-600 border-red-200 hover:bg-red-50 hover:text-red-700">
                Open Dispute
              </Button>
            </Link>
          </div>
        )}
      </div>
    </div>
  );
} 