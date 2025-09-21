/**
 * 🎨 FIGMA REFERENCE
 * URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAQUEST?node-id=1-2&p=f&t=fS4tJbWVmMTZgUnA-0
 * Export: .cursor/design-references/guardian/guardian-header-layout.png
 * 
 * EXACT SPECS FROM FIGMA:
 * - Wallet balance display in center with "Earnings:" label
 * - Background: gradient from #FFF7E4/30
 * - Balance container: bg-gradient with backdrop-blur-sm, rounded-full
 * - Typography: text-sm for label, text-lg font-semibold for amount
 * - Currency: Nigerian Naira (₦) symbol
 */
import AppLogoIcon from '@/components/app-logo-icon';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, Menu, PanelRight } from 'lucide-react';
import { NotificationDropdown } from '@/components/notification/notification-dropdown';
import { MessageDropdown } from '@/components/message/message-dropdown';

interface GuardianHeaderProps {
    pageTitle?: string; // Made optional since we're not using it for display anymore
    toggleLeftSidebar?: () => void;
    toggleRightSidebar?: () => void;
    isMobile?: boolean;
}

export default function GuardianHeader({ 
    pageTitle, 
    toggleLeftSidebar, 
    toggleRightSidebar, 
    isMobile = false 
}: GuardianHeaderProps) {
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
            
            {/* Wallet balance in center */}
            <div className="flex-1 flex justify-center">
                <div className="flex items-center space-x-2 bg-gradient-to-r from-[#FFF7E4]/0 to-[#FFF7E4]/100 backdrop-blur-sm rounded-full px-4 py-2">
                    <span className="text-sm font-medium text-gray-600">Earnings:</span>
                    <span className="text-lg font-semibold text-gray-900">
                        ₦{auth.user.wallet_balance?.toLocaleString() || '0.00'}
                    </span>
                </div>
            </div>
            
            {/* User profile at right with spacing */}
            <div className={`flex items-center space-x-2 ${isMobile ? 'w-auto' : 'w-64 justify-end pr-20'}`}>
                {/* Mobile right sidebar toggle */}
                {isMobile && (
                    <Button variant="ghost" size="icon" onClick={toggleRightSidebar}>
                        <PanelRight className="h-5 w-5" />
                    </Button>
                )}

                {/* Messages */}
                <MessageDropdown iconSize={isMobile ? 40 : 40} />

                {/* Notifications */}
                <NotificationDropdown iconSize={isMobile ? 24 : 32} />

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
                                    <span className="text-xs text-muted-foreground">Guardian</span>
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