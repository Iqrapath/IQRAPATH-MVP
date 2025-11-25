import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '@/layouts/admin/admin-layout';
import { Button } from '@/components/ui/button';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Badge } from '@/components/ui/badge';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { ArrowLeft } from 'lucide-react';
import { toast } from 'sonner';
import SendMessageModal from '@/components/admin/SendMessageModal';
import {
  TeacherProfileHeader,
  TeacherContactDetails,
  TeacherAboutSection,
  TeacherSubjectsSpecializations,
  TeacherDocumentsSection,
  TeacherPerformanceStats,
  TeacherActionButtons
} from './show-components';

interface Teacher {
  id: number;
  name: string;
  email: string;
  phone: string;
  avatar: string | null;
  location: string;
  created_at: string;
  status: string;
  last_active: string;
  // Account management fields
  account_status: string;
  account_status_display: string;
  account_status_color: string;
  suspended_at?: string | null;
  suspension_reason?: string | null;
  is_deleted?: boolean;
}

interface TeacherProfile {
  id: number;
  bio: string;
  experience_years: number;
  verified: boolean;
  languages: string[];
  teaching_type: string;
  teaching_mode: string;
  subjects: any[];
  rating?: number;
  reviews_count?: number;
}

interface TeacherEarnings {
  wallet_balance: number;
  total_earned: number;
  total_withdrawn: number;
  pending_payouts: number;
}

interface TeachingSession {
  id: number;
  session_date: string;
  start_time: string;
  end_time: string;
  status: string;
  student: {
    id: number;
    name: string;
  };
  subject: {
    id: number;
    name: string;
  };
}

interface Props {
  auth: {
    user: {
      id: number;
      name: string;
      email: string;
      role: string;
    };
  };
  teacher: Teacher;
  profile: TeacherProfile | null;
  earnings: TeacherEarnings | null;
  availabilities: any[];
  documents: any;
  sessions_stats: {
    total: number;
    completed: number;
    upcoming: number;
    cancelled: number;
  };
  upcoming_sessions: TeachingSession[];
  verification_status: {
    docs_status: 'pending' | 'verified' | 'rejected';
    video_status: 'not_scheduled' | 'scheduled' | 'completed' | 'passed' | 'failed';
  };
}

export default function TeacherShow({ 
  auth,
  teacher, 
  profile, 
  earnings, 
  availabilities, 
  documents, 
  sessions_stats, 
  upcoming_sessions,
  verification_status
}: Props) {
  const totalSessions = sessions_stats.completed || 0;

  // Account management modal state
  const [accountModalOpen, setAccountModalOpen] = useState(false);
  const [accountAction, setAccountAction] = useState<'suspend' | 'unsuspend' | 'delete' | 'restore' | 'force-delete'>('suspend');
  const [accountReason, setAccountReason] = useState("");
  const [isProcessingAccount, setIsProcessingAccount] = useState(false);

  // Send message modal state
  const [sendMessageModalOpen, setSendMessageModalOpen] = useState(false);

  // Account management functions
  const openAccountModal = (action: 'suspend' | 'unsuspend' | 'delete' | 'restore' | 'force-delete') => {
    setAccountAction(action);
    setAccountReason("");
    setAccountModalOpen(true);
  };

  const handleAccountAction = async () => {
    setIsProcessingAccount(true);
    try {
      const endpoint = `/admin/user-management/${teacher.id}/${accountAction}`;
      
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ reason: accountReason }),
      });

      const data = await response.json();

      if (data.success) {
        toast.success(data.message);
        setAccountModalOpen(false);
        setAccountReason("");
        // Refresh the page to get updated data
        window.location.reload();
      } else {
        toast.error(data.message || `Failed to ${accountAction} teacher account`);
      }
    } catch (error) {
      toast.error(`Failed to ${accountAction} teacher account`);
    } finally {
      setIsProcessingAccount(false);
    }
  };

  const cancelAccountModal = () => {
    setAccountModalOpen(false);
    setAccountReason("");
  };

  return (
    <AdminLayout pageTitle={`Teacher Profile - ${teacher.name}`} showRightSidebar={false}>
      <Head title={`Teacher Profile - ${teacher.name}`} />
      
      <div className="container py-6">
        {/* Breadcrumb */}
        <div className="mb-8">
          <Breadcrumbs
            breadcrumbs={[
              { title: 'Dashboard', href: '/admin/dashboard' },
              { title: 'Teachers', href: '/admin/teachers' },
              { title: teacher.name, href: `/admin/teachers/${teacher.id}` },
            ]}
          />
        </div>

        {/* Back Button */}
        <div className="mb-6">
          <Link href="/admin/teachers">
            <Button variant="outline" className="flex items-center gap-2">
              <ArrowLeft className="h-4 w-4" />
              Back to Teachers
            </Button>
          </Link>
        </div>

        {/* Profile Header Section */}
        <div className="relative mb-8">
          <TeacherProfileHeader 
            teacher={teacher} 
            profile={profile} 
            earnings={earnings} 
            verificationStatus={verification_status}
          />
          
          {/* Account Status and Management Actions */}
          <div className="mt-4 p-4 bg-gray-50 rounded-lg">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-4">
                <div>
                  <h3 className="text-sm font-medium text-gray-700">Account Status</h3>
                  <Badge 
                    variant="outline" 
                    className={`mt-1 ${
                      teacher.account_status === 'active' 
                        ? 'text-green-600 border-green-600 bg-green-50' 
                        : teacher.account_status === 'suspended' 
                        ? 'text-red-600 border-red-600 bg-red-50'
                        : teacher.account_status === 'inactive'
                        ? 'text-gray-600 border-gray-600 bg-gray-50'
                        : teacher.account_status === 'pending'
                        ? 'text-yellow-600 border-yellow-600 bg-yellow-50'
                        : 'text-gray-600 border-gray-600 bg-gray-50'
                    }`}
                  >
                    {teacher.account_status_display || teacher.account_status}
                  </Badge>
                </div>
                
                {teacher.suspended_at && (
                  <div>
                    <h4 className="text-sm font-medium text-gray-700">Suspended</h4>
                    <p className="text-xs text-gray-500">
                      {new Date(teacher.suspended_at).toLocaleDateString()}
                    </p>
                    {teacher.suspension_reason && (
                      <p className="text-xs text-red-600 mt-1">
                        Reason: {teacher.suspension_reason}
                      </p>
                    )}
                  </div>
                )}
              </div>
              
              <div className="flex gap-2">
                {teacher.account_status === 'active' && (
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => openAccountModal('suspend')}
                    className="text-orange-600 border-orange-200 hover:bg-orange-50"
                  >
                    Suspend Account
                  </Button>
                )}
                
                {teacher.account_status === 'suspended' && (
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => openAccountModal('unsuspend')}
                    className="text-green-600 border-green-200 hover:bg-green-50"
                  >
                    Unsuspend Account
                  </Button>
                )}
                
                {!teacher.is_deleted && (
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => openAccountModal('delete')}
                    className="text-red-600 border-red-200 hover:bg-red-50"
                  >
                    Delete Account
                  </Button>
                )}
                
                {teacher.is_deleted && (
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => openAccountModal('restore')}
                    className="text-green-600 border-green-200 hover:bg-green-50"
                  >
                    Restore Account
                  </Button>
                )}
              </div>
            </div>
          </div>
        </div>

        {/* Contact and Professional Details */}
        <TeacherContactDetails 
          teacher={teacher} 
          profile={profile} 
          totalSessions={totalSessions} 
        />
        
        {/* About Section */}
        <TeacherAboutSection profile={profile} teacherName={teacher.name} />

        {/* Subjects and Specializations Section */}
        <TeacherSubjectsSpecializations 
          profile={profile} 
          availabilities={availabilities}
          teacherId={teacher.id}
        />
        
        {/* Documents Section */}
        <TeacherDocumentsSection documents={documents} teacherId={teacher.id} />

        {/* Performance Stats Section */}
        <TeacherPerformanceStats 
          totalSessions={sessions_stats.total}
          averageRating={profile?.rating || 0}
          totalReviews={profile?.reviews_count || 0}
          upcomingSessions={upcoming_sessions}
        />

        {/* Action Buttons Section */}
        <div className="mt-8 mb-8">
          <TeacherActionButtons 
            teacherId={teacher.id}
            verificationStatus={verification_status}
            onSendMessage={() => {
              setSendMessageModalOpen(true);
            }}
            onRefresh={() => {
              // Refresh the page to get updated verification status
              window.location.reload();
            }}
          />
        </div>

      </div>

      {/* Account Management Modal */}
      <Dialog open={accountModalOpen} onOpenChange={setAccountModalOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>
              {accountAction === 'suspend' && 'Suspend Account'}
              {accountAction === 'unsuspend' && 'Unsuspend Account'}
              {accountAction === 'delete' && 'Delete Account'}
              {accountAction === 'restore' && 'Restore Account'}
              {accountAction === 'force-delete' && 'Permanently Delete Account'}
            </DialogTitle>
            <DialogDescription>
              {accountAction === 'suspend' && `Are you sure you want to suspend ${teacher.name}'s account?`}
              {accountAction === 'unsuspend' && `Are you sure you want to unsuspend ${teacher.name}'s account?`}
              {accountAction === 'delete' && `Are you sure you want to delete ${teacher.name}'s account? This can be restored.`}
              {accountAction === 'restore' && `Are you sure you want to restore ${teacher.name}'s account?`}
              {accountAction === 'force-delete' && `Are you sure you want to permanently delete ${teacher.name}'s account? This cannot be undone.`}
            </DialogDescription>
          </DialogHeader>
          
          <div className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="account-reason">Reason *</Label>
              <Textarea
                id="account-reason"
                placeholder={`Please provide a reason for ${accountAction}ing this account...`}
                value={accountReason}
                onChange={(e) => setAccountReason(e.target.value)}
                className="min-h-[100px] resize-none"
                maxLength={500}
              />
              <div className="text-xs text-gray-500 text-right">
                {accountReason.length}/500 characters
              </div>
            </div>
          </div>

          <DialogFooter className="flex gap-2">
            <Button
              variant="outline"
              onClick={cancelAccountModal}
              disabled={isProcessingAccount}
            >
              Cancel
            </Button>
            <Button
              variant={accountAction === 'delete' || accountAction === 'force-delete' ? 'destructive' : 'default'}
              onClick={handleAccountAction}
              disabled={isProcessingAccount || !accountReason.trim()}
              className="flex items-center gap-2"
            >
              {isProcessingAccount ? (
                <>
                  <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                  Processing...
                </>
              ) : (
                <>
                  {accountAction === 'suspend' && 'Suspend Account'}
                  {accountAction === 'unsuspend' && 'Unsuspend Account'}
                  {accountAction === 'delete' && 'Delete Account'}
                  {accountAction === 'restore' && 'Restore Account'}
                  {accountAction === 'force-delete' && 'Permanently Delete'}
                </>
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Send Message Modal */}
      <SendMessageModal
        isOpen={sendMessageModalOpen}
        onClose={() => setSendMessageModalOpen(false)}
        onSuccess={() => {
          toast.success('Message sent successfully to ' + teacher.name);
        }}
        recipientId={teacher.id}
        recipientName={teacher.name}
        currentUserId={auth.user.id}
        currentUserRole={auth.user.role || 'admin'}
      />
    </AdminLayout>
  );
}
