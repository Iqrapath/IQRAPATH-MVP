import React from 'react';
import { cn } from '@/lib/utils';

type StatusType = 'online' | 'away' | 'busy' | 'offline';

interface UserStatusProps {
    status: StatusType;
    className?: string;
    showLabel?: boolean;
    size?: 'sm' | 'md' | 'lg';
}

export function UserStatus({ status, className, showLabel = false, size = 'md' }: UserStatusProps) {
    const statusColors = {
        online: 'bg-green-500',
        away: 'bg-yellow-500',
        busy: 'bg-red-500',
        offline: 'bg-gray-400',
    };

    const statusLabels = {
        online: 'Online',
        away: 'Away',
        busy: 'Busy',
        offline: 'Offline',
    };

    const sizeClasses = {
        sm: 'h-2 w-2',
        md: 'h-3 w-3',
        lg: 'h-4 w-4',
    };

    return (
        <div className={cn('flex items-center gap-1.5', className)}>
            <span 
                className={cn(
                    'rounded-full', 
                    statusColors[status], 
                    sizeClasses[size]
                )} 
            />
            {showLabel && (
                <span className="text-xs font-medium text-muted-foreground">
                    {statusLabels[status]}
                </span>
            )}
        </div>
    );
} 