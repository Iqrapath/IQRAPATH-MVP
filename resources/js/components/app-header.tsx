import { Breadcrumbs } from '@/components/breadcrumbs';
import { Icon } from '@/components/icon';
import { UserStatus } from '@/components/user-status';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type SharedData, type StatusType } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, Menu, PanelRight } from 'lucide-react';
import AppLogoIcon from './app-logo-icon';
import { NotificationDropdown } from '@/components/notification/notification-dropdown';
import { MessageDropdown } from '@/components/message/message-dropdown';
import { useEffect, useState } from 'react';

interface AppHeaderProps {
    breadcrumbs?: BreadcrumbItem[];
    pageTitle?: string;
    toggleLeftSidebar?: () => void;
    toggleRightSidebar?: () => void;
    isMobile?: boolean;
}

export function AppHeader({
    breadcrumbs = [],
    pageTitle = "Dashboard",
    toggleLeftSidebar,
    toggleRightSidebar,
    isMobile = false
}: AppHeaderProps) {
    const page = usePage<SharedData>();
    const { auth } = page.props;
    const getInitials = useInitials();
    const status: StatusType = (auth.user.status_type as StatusType) || 'offline';
    const [isSmallScreen, setIsSmallScreen] = useState(false);

    useEffect(() => {
        const checkScreenSize = () => {
            setIsSmallScreen(window.innerWidth < 768);
        };

        // Initial check
        checkScreenSize();

        // Add event listener
        window.addEventListener('resize', checkScreenSize);

        // Cleanup
        return () => window.removeEventListener('resize', checkScreenSize);
    }, []);

    return (
        <>
            <header className="bg-gradient-to-r from-[#FFF7E4]/30 to-[#FFF7E4]/30 border-b border-gray-200 h-16 flex items-center px-4 md:px-6">
                {/* Mobile menu button */}
                {(isMobile || isSmallScreen) && (
                    <Button variant="ghost" size="icon" className="mr-2" onClick={toggleLeftSidebar}>
                        <Menu className="h-5 w-5" />
                    </Button>
                )}

                {/* Logo at left with spacing */}
                <div className={`flex items-center ${isMobile ? 'w-auto' : 'w-64 pl-30'}`}>
                    <Link href="/dashboard" className="flex items-center">
                        <AppLogoIcon className={`${isMobile ? 'w-24' : 'w-32'} h-auto`} />
                    </Link>
                </div>

                {/* Page title in center - hidden on very small screens */}
                <div className="hidden sm:flex flex-1 justify-center">
                    <h1 className="text-base md:text-lg font-medium truncate max-w-[200px] md:max-w-none">{pageTitle}</h1>
                </div>

                {/* User profile at right with spacing */}
                <div className="flex items-center space-x-1 md:space-x-2 ml-auto md:w-auto md:justify-end md:pr-4 lg:pr-20">
                    {/* Mobile right sidebar toggle */}
                    {(isMobile || isSmallScreen) && (
                        <Button variant="ghost" size="icon" onClick={toggleRightSidebar}>
                            <PanelRight className="h-5 w-5" />
                        </Button>
                    )}

                    {/* Messages - hidden on very small screens */}
                    <div className="hidden sm:block">
                        <MessageDropdown iconSize={32} />
                    </div>

                    {/* Notifications */}
                    <NotificationDropdown iconSize={24} />

                    {/* User Profile */}
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" className="flex items-center space-x-2 px-2">
                                <Avatar className="h-8 w-8">
                                    {auth.user.avatar && (
                                        <AvatarImage src={auth.user.avatar} alt={auth.user.name} />
                                    )}
                                    <AvatarFallback className="bg-primary text-white">
                                        {getInitials(auth.user.name)}
                                    </AvatarFallback>
                                </Avatar>
                                {!isMobile && (
                                    <div className="flex flex-col items-start text-sm">
                                        <span className="font-medium">{auth.user.name}</span>
                                        <span className="text-xs text-muted-foreground">{auth.user.role ? auth.user.role.charAt(0).toUpperCase() + auth.user.role.slice(1) : 'User'}</span>
                                    </div>
                                )}
                                {!isMobile && <ChevronDown className="h-4 w-4 text-muted-foreground" />}
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-56">
                            <UserMenuContent user={auth.user} />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </header>
            {breadcrumbs.length > 1 && (
                <div className="flex w-full border-b border-sidebar-border/70">
                    <div className="mx-auto flex h-12 w-full items-center justify-start px-4 text-neutral-500 md:max-w-7xl">
                        <Breadcrumbs breadcrumbs={breadcrumbs} />
                    </div>
                </div>
            )}
        </>
    );
}
