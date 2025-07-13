import AppLogoIcon from '@/components/app-logo-icon';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Bell, MessageSquare, ChevronDown } from 'lucide-react';

interface AdminHeaderProps {
    pageTitle: string;
}

export default function AdminHeader({ pageTitle }: AdminHeaderProps) {
    const page = usePage<SharedData>();
    const { auth } = page.props;
    const getInitials = useInitials();

    return (
        <header className="bg-gradient-to-r from-[#FFF7E4]/30 to-[#FFF7E4]/30 border-b border-gray-200 h-16 flex items-center px-6">
            {/* Logo at left with spacing */}
            <div className="flex items-center w-64 pl-30">
                <Link href="/dashboard" className="flex items-center">
                    <AppLogoIcon className="h-8 w-8 fill-current text-primary" />
                    <span className="ml-2 text-xl font-semibold">IqraPath</span>
                </Link>
            </div>

            {/* Page title in center */}
            <div className="flex-1 flex justify-center">
                <h1 className="text-lg font-medium">{pageTitle}</h1>
            </div>

            {/* User profile at right with spacing */}
            <div className="flex items-center space-x-4 w-64 justify-end pr-20">
                {/* Notifications */}
                <Button variant="ghost" size="icon" className="relative">
                    <Bell className="h-5 w-5" />
                    <span className="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] text-white">
                        3
                    </span>
                </Button>

                {/* Messages */}
                <Button variant="ghost" size="icon" className="relative">
                    <MessageSquare className="h-5 w-5" />
                </Button>

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
                            <div className="flex flex-col items-start text-sm">
                                <span className="font-medium">{auth.user.name}</span>
                                <div className="flex items-center">
                                    <span className="text-xs text-muted-foreground">
                                        {auth.user.role}
                                    </span>
                                    <ChevronDown className="h-4 w-4 text-muted-foreground" />
                                </div>
                            </div>
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