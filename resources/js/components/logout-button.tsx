import React from 'react';
import { router } from '@inertiajs/react';
import { LogOutIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useAuthLoading } from '@/hooks/use-auth-loading';
import LogoutIcon from './icons/logout-icon';

interface LogoutButtonProps {
  className?: string;
  iconOnly?: boolean;
  variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
  size?: 'default' | 'sm' | 'lg' | 'icon';
}

/**
 * A logout button component that shows the loading screen during logout
 */
export default function LogoutButton({
  className = '',
  iconOnly = false,
  variant = 'ghost',
  size = 'default',
}: LogoutButtonProps) {
  const { handleAuthAction } = useAuthLoading();

  const handleLogout = () => {
    handleAuthAction(() => {
      router.post(route('logout'), {}, {
        preserveScroll: true
      });
    }, 'Logging out...');
  };

  return (
    <Button 
      onClick={handleLogout} 
      variant={variant}
      size={size}
      className={className}
    >
      <LogoutIcon className="h-5 w-5 mr-2.5" />
      {!iconOnly && 'Logout'}
    </Button>
  );
} 