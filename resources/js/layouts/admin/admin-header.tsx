import AppLogoIcon from '@/components/app-logo-icon';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, Menu, PanelRight } from 'lucide-react';
import BellNotificationIcon from '@/components/icons/bell-notification-icon';
import MessageIcon from '@/components/icons/message-icon';

interface AdminHeaderProps {
    pageTitle: string;
    toggleLeftSidebar?: () => void;
    toggleRightSidebar?: () => void;
    isMobile?: boolean;
}

export default function AdminHeader({ 
    pageTitle, 
    toggleLeftSidebar, 
    toggleRightSidebar, 
    isMobile = false 
}: AdminHeaderProps) {
    const page = usePage<SharedData>();
    const { auth } = page.props;
    const getInitials = useInitials();

    return (
        <header className="bg-gradient-to-r from-[#FFF7E4]/30 to-[#FFF7E4]/30 border-b border-gray-200 h-16 flex items-center px-6">
            {/* Mobile menu button */}
            {isMobile && (
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
            
            {/* Page title in center */}
            <div className="flex-1 flex justify-center">
                <h1 className={`${isMobile ? 'text-base' : 'text-lg'} font-medium`}>{pageTitle}</h1>
            </div>
            
            {/* User profile at right with spacing */}
            <div className={`flex items-center space-x-6 ${isMobile ? 'w-auto' : 'w-64 justify-end pr-20'}`}>
                {/* Mobile right sidebar toggle */}
                {isMobile && (
                    <Button variant="ghost" size="icon" onClick={toggleRightSidebar}>
                        <PanelRight className="h-5 w-5" />
                    </Button>
                )}
                
                {/* Notifications */}
                <Button variant="ghost" size="icon" className="relative">
                    <BellNotificationIcon style={{ width: '32px', height: '32px' }} />
                    <span className="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] text-white">
                        3
                    </span>
                </Button>

                {/* Messages - hide on smallest screens */}
                {!isMobile && (
                    <Button variant="ghost" size="icon" className="relative">
                        <MessageIcon style={{ width: '40px', height: '40px' }} />
                    </Button>
                )}

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
                                    <span className="text-xs text-muted-foreground">Admin</span>
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
    );
} 