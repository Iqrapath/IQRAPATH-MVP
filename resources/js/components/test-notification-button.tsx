import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import axios from 'axios';
import { Bell, Loader2 } from 'lucide-react';

export default function TestNotificationButton() {
  const [loading, setLoading] = useState(false);
  
  const createTestNotification = async () => {
    setLoading(true);
    try {
      const response = await axios.post('/api/create-test-notification');
      alert('Test notification created successfully!');
    } catch (error) {
      console.error('Error creating test notification:', error);
      alert('Failed to create test notification.');
    } finally {
      setLoading(false);
    }
  };

  // Only show in development environment
  if (process.env.NODE_ENV !== 'development') {
    return null;
  }

  return (
    <Button 
      variant="outline" 
      size="sm" 
      onClick={createTestNotification}
      className="flex items-center gap-1"
      disabled={loading}
    >
      {loading ? (
        <Loader2 className="h-3 w-3 animate-spin" />
      ) : (
        <Bell className="h-3 w-3" />
      )}
      <span>Test Notification</span>
    </Button>
  );
} 