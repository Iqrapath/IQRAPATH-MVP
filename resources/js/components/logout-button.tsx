import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { LogOutIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { useAuthLoading } from '@/hooks/use-auth-loading';
import LogoutIcon from './icons/logout-icon';

interface LogoutButtonProps {
  className?: string;
  iconOnly?: boolean;
  variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
  size?: 'default' | 'sm' | 'lg' | 'icon';
}

/**
 * A logout button component that shows a confirmation modal before logout
 */
export default function LogoutButton({
  className = '',
  iconOnly = false,
  variant = 'ghost',
  size = 'default',
}: LogoutButtonProps) {
  const { handleAuthAction } = useAuthLoading();
  const [showConfirmDialog, setShowConfirmDialog] = useState(false);

  const handleLogout = () => {
    setShowConfirmDialog(false);
    handleAuthAction(() => {
      router.post(route('logout'), {}, {
        preserveScroll: true
      });
    }, 'Logging out...');
  };

  return (
    <>
      <Button 
        onClick={() => setShowConfirmDialog(true)} 
        variant={variant}
        size={size}
        className={className}
      >
        <LogoutIcon className="h-5 w-5 mr-2.5" />
        {!iconOnly && 'Logout'}
      </Button>

      <Dialog open={showConfirmDialog} onOpenChange={setShowConfirmDialog}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Confirm Logout</DialogTitle>
            <DialogDescription>
              Are you sure you want to logout? You will need to sign in again to access your account.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter className="flex-col sm:flex-row gap-2">
            <Button
              type="button"
              variant="outline"
              onClick={() => setShowConfirmDialog(false)}
              className="w-full sm:w-auto"
            >
              Cancel
            </Button>
            <Button
              type="button"
              variant="destructive"
              onClick={handleLogout}
              className="w-full sm:w-auto"
            >
              Yes, Logout
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
} 