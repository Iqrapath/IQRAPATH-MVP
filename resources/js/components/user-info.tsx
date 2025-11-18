import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { UserStatus } from '@/components/user-status';
import { useInitials } from '@/hooks/use-initials';
import { type StatusType, type User } from '@/types';

export function UserInfo({ 
    user, 
    showEmail = false, 
    showStatus = false 
}: { 
    user: User; 
    showEmail?: boolean;
    showStatus?: boolean;
}) {
    const getInitials = useInitials();
    const status: StatusType = (user.status_type as StatusType) || 'offline';

    return (
        <>
            <div className="relative">
                <Avatar className="h-8 w-8 overflow-hidden rounded-full">
                    {user.avatar && <AvatarImage src={user.avatar} alt={user.name} />}
                    <AvatarFallback className="rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                        {getInitials(user.name)}
                    </AvatarFallback>
                </Avatar>
                {showStatus && (
                    <div className="absolute -bottom-0.5 -right-0.5">
                        <UserStatus status={status} size="sm" />
                    </div>
                )}
            </div>
            <div className="grid flex-1 text-left text-sm leading-tight">
                <span className="truncate font-medium">{user.name}</span>
                {showEmail && <span className="truncate text-xs text-muted-foreground">{user.email}</span>}
                {showEmail && user.phone && <span className="truncate text-xs text-muted-foreground">{user.phone}</span>}
                {showEmail && user.status_message && (
                    <span className="truncate text-xs italic text-muted-foreground">"{user.status_message}"</span>
                )}
            </div>
        </>
    );
}
