import React, { useEffect, useState } from 'react';
import axios from 'axios';
import { toast } from 'sonner';

// Add a declaration for the window.App property
declare global {
  interface Window {
    App?: {
      user?: {
        id?: number;
        role?: string;
      }
    };
    Echo: any;
  }
}

interface GlobalNotificationListenerProps {
  // No props needed as this is a global component
}

export default function GlobalNotificationListener({}: GlobalNotificationListenerProps) {
  const [userId, setUserId] = useState<string | null>(null);
  const [userRole, setUserRole] = useState<string | null>(null);
  const [isConnected, setIsConnected] = useState<boolean>(false);
  const [connectionAttempts, setConnectionAttempts] = useState<number>(0);

  // Helper function to find user ID using various methods
  const findUserId = (): string | null => {
    // Try to get user ID from meta tag first (most reliable)
    const metaUserId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
    if (metaUserId) {
      console.log('Global notification listener: Found user ID in meta tag:', metaUserId);
      return metaUserId;
    }
    
    // Try to get user ID from window object if available
    if (window.App && window.App.user && window.App.user.id) {
      console.log('Global notification listener: Found user ID in window.App:', window.App.user.id);
      return window.App.user.id.toString();
    }
    
    // Try to get user ID from localStorage as last resort
    const localStorageUserId = localStorage.getItem('user_id');
    if (localStorageUserId) {
      console.log('Global notification listener: Found user ID in localStorage:', localStorageUserId);
      return localStorageUserId;
    }
    
    // If all else fails, try to fetch it from the API
    axios.get('/api/user')
      .then(response => {
        if (response.data && response.data.id) {
          console.log('Global notification listener: Fetched user ID from API:', response.data.id);
          // Store in localStorage for future use
          localStorage.setItem('user_id', response.data.id.toString());
          setUserId(response.data.id.toString());
          return response.data.id.toString();
        }
        return null;
      })
      .catch(error => {
        console.error('Global notification listener: Failed to fetch user ID from API:', error);
        return null;
      });
    
    return null;
  };

  // Helper function to find user role using various methods
  const findUserRole = (): string | null => {
    // Try to get user role from meta tag first
    const metaUserRole = document.querySelector('meta[name="user-role"]')?.getAttribute('content');
    if (metaUserRole) {
      console.log('Global notification listener: Found user role in meta tag:', metaUserRole);
      return metaUserRole;
    }
    
    // Try to get user role from window object if available
    if (window.App && window.App.user && window.App.user.role) {
      console.log('Global notification listener: Found user role in window.App:', window.App.user.role);
      return window.App.user.role;
    }
    
    // Try to get user role from localStorage as last resort
    const localStorageUserRole = localStorage.getItem('user_role');
    if (localStorageUserRole) {
      console.log('Global notification listener: Found user role in localStorage:', localStorageUserRole);
      return localStorageUserRole;
    }
    
    return null;
  };

  // Set up user ID and role on component mount
  useEffect(() => {
    const foundUserId = findUserId();
    const foundUserRole = findUserRole();
    
    if (foundUserId) {
      setUserId(foundUserId);
    }
    
    if (foundUserRole) {
      setUserRole(foundUserRole);
    }
  }, []);

  // Set up real-time notification listeners
  useEffect(() => {
    if (!userId || !window.Echo) {
      // If no user ID or Echo is not available, retry after a delay
      if (connectionAttempts < 5) {
        const timeout = setTimeout(() => {
          console.log('Global notification listener: Retrying connection, attempt', connectionAttempts + 1);
          setConnectionAttempts(prev => prev + 1);
          const retryUserId = findUserId();
          if (retryUserId) {
            setUserId(retryUserId);
          }
        }, 2000);
        
        return () => clearTimeout(timeout);
      }
      return;
    }

    console.log('Global notification listener: Setting up global notification listeners for user', userId);
    
    // Set up listener for notifications
    const notificationChannel = window.Echo.private(`notifications.${userId}`);
    
    notificationChannel.listen('.notification.received', (data: any) => {
      console.log('Global notification listener: Notification received:', data);
      
      // Create and dispatch a custom event that other components can listen for
      const event = new CustomEvent('notificationReceived', { detail: data });
      window.dispatchEvent(event);
      
      // Show a toast notification
      if (data.notification && data.notification.title) {
        toast.info(data.notification.title, {
          description: data.notification.body,
          duration: 5000,
        });
      }
    });
    
    // Set up listener for messages
    const messageChannel = window.Echo.private(`messages.${userId}`);
    
    messageChannel.listen('.message.received', (data: any) => {
      console.log('Global notification listener: Message received:', data);
      
      // Create and dispatch a custom event that other components can listen for
      const event = new CustomEvent('messageReceived', { detail: data });
      window.dispatchEvent(event);
      
      // Show a toast notification
      if (data.message && data.message.content) {
        toast.info('New Message', {
          description: data.message.content,
          duration: 5000,
        });
      }
    });
    
    // Set up listener for session requests (for teachers)
    if (userRole === 'teacher') {
      const sessionRequestChannel = window.Echo.private(`session-requests.${userId}`);
      
      sessionRequestChannel.listen('.session-request.received', (data: any) => {
        console.log('Global notification listener: Session request received:', data);
        
        // Create and dispatch a custom event that other components can listen for
        const event = new CustomEvent('sessionRequestReceived', { detail: data });
        window.dispatchEvent(event);
        
        // Show a toast notification
        if (data.session_request) {
          toast.info('New Session Request', {
            description: `You have a new session request from ${data.session_request.student_name}`,
            duration: 5000,
          });
        }
      });
    }
    
    // Mark as connected
    setIsConnected(true);
    console.log('Global notification listener: Global notification listeners set up successfully');
    
    // Clean up listeners when component unmounts
    return () => {
      notificationChannel.stopListening('.notification.received');
      messageChannel.stopListening('.message.received');
      
      if (userRole === 'teacher') {
        const sessionRequestChannel = window.Echo.private(`session-requests.${userId}`);
        sessionRequestChannel.stopListening('.session-request.received');
      }
      
      setIsConnected(false);
    };
  }, [userId, userRole, connectionAttempts]);

  // This component doesn't render anything visible
  return null;
} 