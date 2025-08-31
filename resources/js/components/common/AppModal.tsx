/**
 * 🎨 FIGMA REFERENCE
 * URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAPATH?node-id=394-26445&t=O1w7ozri9pYud8IO-0
 * Export: Generic modal container used across the app
 *
 * PURPOSE
 * - Provide a reusable modal wrapper built on top of the shared Dialog primitives
 * - Enforce consistent spacing, header, and max-widths per design system
 */
import { type ReactNode } from 'react';
import {
  Dialog,
  DialogTrigger,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';

interface AppModalProps {
  title?: string;
  description?: string;
  trigger?: ReactNode;
  children: ReactNode;
  footer?: ReactNode;
  open?: boolean;
  onOpenChange?: (open: boolean) => void;
  size?: 'sm' | 'md' | 'lg' | 'xl';
  className?: string;
}

export default function AppModal({
  title,
  description,
  trigger,
  children,
  footer,
  open,
  onOpenChange,
  size = 'lg',
  className,
}: AppModalProps) {
  const sizeClass =
    size === 'sm'
      ? 'sm:max-w-sm'
      : size === 'md'
      ? 'sm:max-w-lg'
      : size === 'xl'
      ? 'sm:max-w-3xl'
      : 'sm:max-w-2xl';

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      {trigger ? <DialogTrigger asChild>{trigger}</DialogTrigger> : null}
      <DialogContent className={cn(sizeClass, 'max-h-[90vh] overflow-hidden flex flex-col', className)}>
        {(title || description) && (
          <DialogHeader className="flex-shrink-0">
            {title ? <DialogTitle>{title}</DialogTitle> : null}
            {description ? (
              <DialogDescription>{description}</DialogDescription>
            ) : null}
          </DialogHeader>
        )}
        <div className="flex-1 overflow-hidden">{children}</div>
        {footer ? <DialogFooter className="flex-shrink-0">{footer}</DialogFooter> : null}
      </DialogContent>
    </Dialog>
  );
}


