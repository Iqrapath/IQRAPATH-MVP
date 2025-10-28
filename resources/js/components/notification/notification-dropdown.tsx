import React, { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useNotifications } from '@/hooks/use-notifications';
import { BellNotificationIcon } from '@/components/icons/bell-notification-icon';
import { formatDistanceToNow } from 'date-fns';
import { Check, Trash2, Bell, BellOff } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';

interface NotificationDropdownProps {
  className?: string;
  iconSize?: number;
}

export function NotificationDropdown({ className, iconSize = 24 }: NotificationDropdownProps) {
  const { auth } = usePage().props as any;
  const userRole = auth?.user?.role;
  const [currentTime, setCurrentTime] = useState(new Date());
  
  // Update current time every minute for countdown timers
  useEffect(() => {
    const interval = setInterval(() => {
      setCurrentTime(new Date());
    }, 60000); // Update every minute
    
    return () => clearInterval(interval);
  }, []);
  
  
  const {
    notifications,
    unreadCount,
    isLoading,
    fetchNotifications,
    markAsRead,
    markAllAsRead,
    deleteNotification,
  } = useNotifications({
    pollingInterval: 30000, // Poll every 30 seconds as fallback
    useWebSockets: true,
    showToasts: true, // Enable toasts for verification calls
  });


  const handleMarkAsRead = (notificationId: string) => {
    markAsRead(notificationId);
  };

  const handleDeleteNotification = (
    e: React.MouseEvent,
    notificationId: string
  ) => {
    e.preventDefault();
    e.stopPropagation();
    deleteNotification(notificationId);
  };

  // Listen for manual refresh events
  useEffect(() => {
    const handleRefresh = () => {
      fetchNotifications();
    };

    window.addEventListener('refresh-notifications', handleRefresh);
    
    return () => {
      window.removeEventListener('refresh-notifications', handleRefresh);
    };
  }, [fetchNotifications]);

  // Fallback mechanism: Show toast for verification calls if notifications fail to load
  useEffect(() => {
    const checkForVerificationCalls = async () => {
      try {
        // If notifications are not loading and we're not in loading state, try to fetch manually
        if (!isLoading && notifications.length === 0) {
          const response = await fetch('/api/user/notifications', {
            credentials: 'include',
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
            },
          });
          
          if (response.ok) {
            const data = await response.json();
            const verificationNotifications = data.data?.filter((notification: any) => 
              notification.type === 'App\\Notifications\\VerificationCallScheduledNotification' && 
              !notification.read_at
            ) || [];
            
            // Show toast for unread verification calls
            verificationNotifications.forEach((notification: any) => {
              const verificationData = notification.data;
              const scheduledTime = verificationData.scheduled_at_human || 'the scheduled time';
              const platform = verificationData.platform_label || 'the video platform';
              const meetingLink = verificationData.meeting_link;
              
              const enhancedMessage = `${verificationData.message}\n\n📅 Scheduled: ${scheduledTime}\n📹 Platform: ${platform}${meetingLink ? '\n🔗 Meeting link available' : ''}\n\n📧 Please check your email for detailed verification instructions.`;
              
              toast.success(`📞 ${verificationData.title}`, {
                description: enhancedMessage,
                duration: 10000,
                action: verificationData.action_text ? {
                  label: verificationData.action_text,
                  onClick: () => {
                    if (verificationData.action_url) {
                      if (verificationData.action_url.startsWith('/')) {
                        window.location.href = verificationData.action_url;
                      } else {
                        window.open(verificationData.action_url, '_blank');
                      }
                    }
                  },
                } : {
                  label: 'Check Email',
                  onClick: () => {
                    toast.info('📧 Check your email for verification details and meeting link!');
                  }
                },
              });
            });
          }
        }
      } catch (error) {
        toast.error('Failed to check for verification calls' + error);
        // console.error('Failed to check for verification calls:', error);
      }
    };

    // Check for verification calls after a delay to allow normal notification loading
    const timeoutId = setTimeout(checkForVerificationCalls, 5000);
    
    return () => clearTimeout(timeoutId);
  }, [isLoading, notifications.length]);

  const getNotificationIcon = (type: string, level?: string) => {
    // You can customize this based on notification types in your system
    switch (type) {
      case 'App\\Notifications\\MessageNotification':
        return <Bell className="h-4 w-4 text-blue-500" />;
      case 'App\\Notifications\\PaymentNotification':
        return <Bell className="h-4 w-4 text-green-500" />;
      case 'App\\Notifications\\SessionRequestNotification':
        return <Bell className="h-4 w-4 text-amber-500" />;
      case 'App\\Notifications\\BookingNotification':
        return <Bell className="h-4 w-4 text-purple-500" />;
      case 'App\\Notifications\\AvailabilityUpdatedNotification':
        return <Bell className="h-4 w-4 text-emerald-500" />;
      case 'teacher_rejected':
      case 'App\\Notifications\\VerificationRejectedNotification':
        return <Bell className="h-4 w-4 text-red-500" />;
      case 'App\\Notifications\\VerificationCallScheduledNotification':
        return <Bell className="h-4 w-4 text-indigo-500" />;
      case 'App\\Notifications\\SystemNotification':
        return <Bell className="h-4 w-4 text-cyan-500" />;
      case 'new_user_registration':
        return <Bell className="h-4 w-4 text-teal-500" />;
      default:
        return <Bell className="h-4 w-4 text-gray-500" />;
    }
  };
  
  // Get the correct notification route based on user role
  const getNotificationsRoute = () => {
    if (userRole === 'super-admin') {
      return route('admin.notifications.index');
    } else if (userRole === 'teacher') {
      return route('teacher.notifications');
    } else if (userRole === 'student') {
      return route('student.notifications');
    } else if (userRole === 'guardian') {
      return route('guardian.notifications');
    }
    
    // Fallback to the general notifications route
    return route('notifications');
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button 
          variant="ghost" 
          size="icon" 
          className={cn("relative", className)}
          // Add a subtle pulse animation when there are unread notifications
          data-has-unread={unreadCount > 0 ? "true" : "false"}
        >
          <BellNotificationIcon 
            style={{ 
              width: iconSize, 
              height: iconSize,
              // Add a subtle color change for unread notifications
              color: unreadCount > 0 ? 'var(--primary)' : 'currentColor'
            }} 
          />
          {unreadCount > 0 && (
            <span className="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-destructive text-[10px] font-medium text-white animate-pulse">
              {unreadCount > 99 ? '99+' : unreadCount}
            </span>
          )}
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-80">
        <DropdownMenuLabel className="flex items-center justify-between">
          <span>Notifications</span>
          {unreadCount > 0 && (
            <Button
              variant="ghost"
              size="sm"
              className="h-8 text-xs"
              onClick={() => markAllAsRead()}
            >
              <Check className="mr-1 h-3 w-3" />
              Mark all as read
            </Button>
          )}
        </DropdownMenuLabel>
        <DropdownMenuSeparator />
        
        {isLoading && (
          <div className="flex justify-center py-4">
            <div className="h-6 w-6 animate-spin rounded-full border-2 border-primary border-t-transparent"></div>
          </div>
        )}
        
        {!isLoading && notifications.length === 0 && (
          <div className="flex flex-col items-center justify-center py-6 px-4 text-center">
            <BellOff className="mb-2 h-8 w-8 text-muted-foreground" />
            <p className="text-sm font-medium">No notifications</p>
            <p className="text-xs text-muted-foreground">
              You're all caught up! We'll notify you when something new arrives.
            </p>
          </div>
        )}
        
        {!isLoading && notifications.length > 0 && (
          <ScrollArea className="h-[300px]">
            <DropdownMenuGroup>
              {notifications.map((notification) => {
                const isUnread = !notification.read_at;
                return (
                  <DropdownMenuItem
                    key={notification.id}
                    className={cn(
                      "flex items-start gap-2 p-3 cursor-pointer",
                      isUnread ? "bg-muted/50 border-l-2 border-primary" : ""
                    )}
                    onClick={() => {
                      if (isUnread) {
                        handleMarkAsRead(notification.id);
                      }
                      
                      // If there's an action URL, Inertia will handle navigation
                      if (notification.data.action_url) {
                        // Navigation will be handled by Link component
                      }
                    }}
                  >
                    <div className="mt-1">
                      {getNotificationIcon(notification.type, notification.level)}
                    </div>
                    <div className="flex-1 overflow-hidden">
                      <div className="flex items-center justify-between">
                        <p className={cn(
                          "text-sm font-medium line-clamp-1",
                          isUnread && "font-semibold text-primary"
                        )}>
                          {notification.data.title}
                        </p>
                      </div>
                      <p className="text-xs text-muted-foreground line-clamp-2">
                        {notification.data.message}
                      </p>
                      
                      {/* Special handling for rejection notifications */}
                      {(notification.type === 'teacher_rejected' || notification.type === 'document_rejected') && notification.data.rejection_reason && (
                        <div className="mt-2 p-2 bg-red-50 rounded border border-red-200">
                          <div className="text-xs text-red-800">
                            <p><strong>Rejection Reason:</strong></p>
                            <p className="mt-1">{notification.data.rejection_reason}</p>
                            {notification.data.resubmission_instructions && (
                              <p className="mt-1"><strong>Instructions:</strong> {notification.data.resubmission_instructions}</p>
                            )}
                            {notification.data.remaining_attempts !== undefined && (
                              <p className="mt-1"><strong>Remaining Attempts:</strong> {notification.data.remaining_attempts}</p>
                            )}
                          </div>
                        </div>
                      )}
                      
                      {/* Special handling for booking notifications */}
                      {notification.type === 'App\\Notifications\\BookingNotification' && (
                        <div className="mt-2 p-2 bg-purple-50 rounded border border-purple-200">
                          <div className="text-xs text-purple-800">
                            {(notification.data as any).teacher_name && (
                              <p><strong>Teacher:</strong> {(notification.data as any).teacher_name}</p>
                            )}
                            {(notification.data as any).student_name && (
                              <p><strong>Student:</strong> {(notification.data as any).student_name}</p>
                            )}
                            {(notification.data as any).subject_name && (
                              <p><strong>Subject:</strong> {(notification.data as any).subject_name}</p>
                            )}
                            {(notification.data as any).booking_date && (notification.data as any).start_time && (
                              <p><strong>Date:</strong> {new Date((notification.data as any).booking_date).toLocaleDateString()} at {(notification.data as any).start_time}</p>
                            )}
                            {(notification.data as any).meeting_link && (
                              <p><strong>Meeting Link:</strong> Available</p>
                            )}
                          </div>
                        </div>
                      )}
                      
                      {/* Special handling for verification call notifications */}
                      {notification.type === 'App\\Notifications\\VerificationCallScheduledNotification' && (
                        <div className="mt-2 p-2 bg-indigo-50 rounded border border-indigo-200">
                          <div className="text-xs text-indigo-800">
                            {(notification.data as any).scheduled_at_human && (
                              <p><strong>Scheduled:</strong> {(notification.data as any).scheduled_at_human}</p>
                            )}
                            {(notification.data as any).platform_label && (
                              <p><strong>Platform:</strong> {(notification.data as any).platform_label}</p>
                            )}
                            {(notification.data as any).meeting_link && (
                              <p><strong>Meeting Link:</strong> Available</p>
                            )}
                            {(notification.data as any).notes && (
                              <p><strong>Notes:</strong> {(notification.data as any).notes}</p>
                            )}
                          </div>
                        </div>
                      )}
                      
                      {/* Special handling for new user registrations */}
                      {notification.type === 'new_user_registration' && (
                        <div className="mt-2 p-2 bg-teal-50 rounded border border-teal-200">
                          <div className="text-xs text-teal-800">
                            <p><strong>New User:</strong> {notification.data.new_user_name}</p>
                            {notification.data.new_user_email && notification.data.new_user_email !== 'No email provided' && (
                              <p><strong>Email:</strong> {notification.data.new_user_email}</p>
                            )}
                            {notification.data.new_user_phone && notification.data.new_user_phone !== 'No phone provided' && (
                              <p><strong>Phone:</strong> {notification.data.new_user_phone}</p>
                            )}
                            {notification.data.registration_time && (
                              <p><strong>Registered:</strong> {notification.data.registration_time}</p>
                            )}
                          </div>
                        </div>
                      )}
                      
                      {/* Special handling for teacher rejection notifications */}
                      {(notification.type === 'teacher_rejected' || notification.type === 'App\\Notifications\\VerificationRejectedNotification') && (
                        <div className="mt-2 p-2 bg-red-50 rounded border border-red-200">
                          <div className="text-xs text-red-800">
                            {notification.data.rejection_reason && (
                              <p><strong>Reason:</strong> {notification.data.rejection_reason}</p>
                            )}
                            {notification.data.support_contact && (
                              <p><strong>Support:</strong> {notification.data.support_contact}</p>
                            )}
                          </div>
                        </div>
                      )}
                      
                      {notification.data.action_text && notification.data.action_url && (
                        <>
                          {/* Check if action_url is external (contains http/https) */}
                          {notification.data.action_url.startsWith('http://') || notification.data.action_url.startsWith('https://') ? (
                            (() => {
                              // Check if this is a scheduled event with time restrictions
                              const scheduledAt = (notification.data as any).scheduled_at;
                              const isScheduled = !!scheduledAt;
                              const scheduledTime = scheduledAt ? new Date(scheduledAt) : null;
                              
                              // Allow access 30 minutes before scheduled time (for teachers/students to prepare)
                              const earlyAccessTime = scheduledTime ? new Date(scheduledTime.getTime() - 30 * 60 * 1000) : null;
                              const canAccessNow = !isScheduled || !earlyAccessTime || currentTime >= earlyAccessTime;
                              
                              // Calculate time until access is available
                              const msUntilAccess = earlyAccessTime ? (earlyAccessTime.getTime() - currentTime.getTime()) : 0;
                              const minutesUntilAccess = Math.ceil(msUntilAccess / (60 * 1000));
                              const hoursUntilAccess = Math.floor(minutesUntilAccess / 60);
                              const remainingMinutes = minutesUntilAccess % 60;
                              
                              // Format countdown display
                              let countdownText = '';
                              if (hoursUntilAccess > 0) {
                                countdownText = `${hoursUntilAccess}h ${remainingMinutes}m`;
                              } else if (minutesUntilAccess > 0) {
                                countdownText = `${minutesUntilAccess} min`;
                              }
                              
                              if (!canAccessNow) {
                                return (
                                  <div className="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded">
                                    <div className="flex items-center gap-2">
                                      <span className="text-xs text-yellow-800">
                                        🔒 {notification.data.action_text}
                                      </span>
                                    </div>
                                    <p className="text-[10px] text-yellow-700 mt-1">
                                      Available in {countdownText}
                                    </p>
                                  </div>
                                );
                              }
                              
                              return (
                                <a
                                  href={notification.data.action_url}
                                  target="_blank"
                                  rel="noopener noreferrer"
                                  className="mt-1 text-xs text-primary hover:underline inline-flex items-center gap-1 font-medium"
                                  onClick={(e) => {
                                    e.stopPropagation();
                                    if (isUnread) {
                                      handleMarkAsRead(notification.id);
                                    }
                                  }}
                                >
                                  {notification.data.action_text} ↗
                                </a>
                              );
                            })()
                          ) : (
                            <Link
                              href={notification.data.action_url}
                              className="mt-1 text-xs text-primary hover:underline inline-block"
                              onClick={() => {
                                if (isUnread) {
                                  handleMarkAsRead(notification.id);
                                }
                              }}
                            >
                              {notification.data.action_text}
                            </Link>
                          )}
                        </>
                      )}
                      
                      <div className="flex items-center justify-between mt-1">
                        <p className="text-[10px] text-muted-foreground">
                          {formatDistanceToNow(new Date(notification.created_at), {
                            addSuffix: true,
                          })}
                        </p>
                        
                        <div className="flex items-center gap-1">
                          {isUnread && (
                            <Button
                              variant="ghost"
                              size="icon"
                              className="h-6 w-6"
                              onClick={(e) => {
                                e.stopPropagation();
                                e.preventDefault();
                                handleMarkAsRead(notification.id);
                              }}
                            >
                              <Check className="h-3 w-3" />
                            </Button>
                          )}
                          
                          <Button
                            variant="ghost"
                            size="icon"
                            className="h-6 w-6"
                            onClick={(e) => handleDeleteNotification(e, notification.id)}
                          >
                            <Trash2 className="h-3 w-3" />
                          </Button>
                        </div>
                      </div>
                    </div>
                  </DropdownMenuItem>
                );
              })}
            </DropdownMenuGroup>
          </ScrollArea>
        )}
        
        <DropdownMenuSeparator />
        <Link
          href={getNotificationsRoute()}
          className="block w-full rounded-sm px-3 py-2 text-center text-xs font-medium hover:bg-muted"
        >
          View all notifications
        </Link>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}

export default NotificationDropdown; 