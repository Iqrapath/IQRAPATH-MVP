import { DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuSub, DropdownMenuSubContent, DropdownMenuSubTrigger } from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { UserStatus } from '@/components/user-status';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { type StatusType, type User } from '@/types';
import { Link, router } from '@inertiajs/react';
import { Clock, LogOut, MessageSquare, Settings } from 'lucide-react';
import { useState } from 'react';

interface UserMenuContentProps {
    user: User;
}

export function UserMenuContent({ user }: UserMenuContentProps) {
    const cleanup = useMobileNavigation();
    const [statusMessage, setStatusMessage] = useState(user.status_message || '');

    const handleLogout = () => {
        cleanup();
        router.flushAll();
    };

    const setStatus = (status: StatusType, message: string = '') => {
        router.post(route('user.status.update'), {
            status_type: status,
            status_message: message || statusMessage
        }, {
            preserveScroll: true,
            onSuccess: () => {
                cleanup();
            }
        });
    };

    const handleStatusMessageChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setStatusMessage(e.target.value);
    };

    const handleStatusMessageKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            setStatus(user.status_type || 'online', statusMessage);
        }
    };

    return (
        <>
            <DropdownMenuLabel className="p-0 font-normal">
                <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                    <UserInfo user={user} showEmail={true} showStatus={true} />
                </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
                <DropdownMenuSub>
                    <DropdownMenuSubTrigger>
                        <div className="flex items-center">
                            <UserStatus status={user.status_type || 'offline'} className="mr-2" showLabel={true} />
                            <span className="ml-auto">Set status</span>
                        </div>
                    </DropdownMenuSubTrigger>
                    <DropdownMenuSubContent className="w-56">
                        <div className="p-2">
                            <input
                                type="text"
                                placeholder="What's on your mind?"
                                className="mb-2 w-full rounded-md border border-input bg-background px-3 py-1.5 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                value={statusMessage}
                                onChange={handleStatusMessageChange}
                                onKeyDown={handleStatusMessageKeyDown}
                            />
                        </div>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem onClick={() => setStatus('online')}>
                            <UserStatus status="online" className="mr-2" showLabel={true} />
                        </DropdownMenuItem>
                        <DropdownMenuItem onClick={() => setStatus('busy')}>
                            <UserStatus status="busy" className="mr-2" showLabel={true} />
                        </DropdownMenuItem>
                        <DropdownMenuItem onClick={() => setStatus('away')}>
                            <UserStatus status="away" className="mr-2" showLabel={true} />
                        </DropdownMenuItem>
                        <DropdownMenuItem onClick={() => setStatus('offline')}>
                            <UserStatus status="offline" className="mr-2" showLabel={true} />
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem className="justify-between">
                            <span>Auto-update status</span>
                            <Clock className="h-4 w-4" />
                        </DropdownMenuItem>
                    </DropdownMenuSubContent>
                </DropdownMenuSub>
                <DropdownMenuItem asChild>
                    <Link className="block w-full" href={route('profile.edit')} as="button" prefetch onClick={cleanup}>
                        <Settings className="mr-2" />
                        Settings
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuItem asChild>
                <Link className="block w-full" method="post" href={route('logout')} as="button" onClick={handleLogout}>
                    <LogOut className="mr-2" />
                    Log out
                </Link>
            </DropdownMenuItem>
        </>
    );
}
