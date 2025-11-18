import { cn } from '@/lib/utils';
import { Link, router, usePage } from '@inertiajs/react';
import {
    Bell,
    Settings,
    LayoutDashboard,
    LucideIcon,
    X,
    WalletIcon,
    UserIcon
} from 'lucide-react';
import React from 'react';
import { Button } from '@/components/ui/button';
import LogoutButton from '@/components/logout-button';
import { toast } from 'sonner';
import { TeacherIcon } from '@/components/icons/teacher-icon';
import MessageUserIcon from '@/components/icons/message-user-icon';
import ReviewIcon from '@/components/icons/review-icon';
import LogoutIcon from '@/components/icons/logout-icon';

interface StudentLeftSidebarProps extends React.HTMLAttributes<HTMLDivElement> {
    isMobile?: boolean;
    onClose?: () => void;
}

type IconType = LucideIcon | any; // Allow any type for icons to support custom icons

interface NavItem {
    title: string;
    href: string;
    icon: IconType;
    iconType?: 'lucide' | 'custom';
    divider?: boolean;
    onClick?: (e: React.MouseEvent) => void;
    isComingSoon?: boolean;
}

// Feedback & Support Icon
const FeedbackSupportIcon = ({ size = 20 }) => (
    <svg
        fill="none"
        height={size}
        viewBox="0 0 20 20"
        width={size}
        xmlns="http://www.w3.org/2000/svg"
    >
        <path d="M6.53039 4.00821C7.22083 2.80808 8.51605 2 10 2C12.2091 2 14 3.79086 14 6C14 8.20914 12.2091 10 10 10C8.40521 10 7.02841 9.0667 6.38598 7.71649C6.13814 7.89746 5.8327 8.00427 5.50232 8.00427H5.0022V8.25891C5.0022 8.44889 5.07392 8.62212 5.19176 8.75301C5.32913 8.59883 5.52931 8.50171 5.7522 8.50171C6.16641 8.50171 6.5022 8.83711 6.5022 9.25085C6.5022 9.6646 6.16641 10 5.7522 10C5.7278 10 5.70368 9.99884 5.67989 9.99656C4.74788 9.96442 4.0022 9.19875 4.0022 8.25891L4.00232 4.50744C4.00232 4.2313 4.22618 4.00744 4.50232 4.00744L6.50232 4.00744C6.51174 4.00744 6.5211 4.0077 6.53039 4.00821ZM7.00232 6.11906C7.06483 7.72073 8.38302 9 10 9C11.6569 9 13 7.65685 13 6C13 4.34315 11.6569 3 10 3C8.38302 3 7.06483 4.27927 7.00232 5.88094V6.11906ZM6.00232 5.86248V5.00744L5.00232 5.00744L5.00232 7.00427H5.50232C5.77846 7.00427 6.00232 6.78042 6.00232 6.50427V6.13752C6.00078 6.09187 6 6.04603 6 6C6 5.95397 6.00078 5.90813 6.00232 5.86248Z" fill="currentColor" />
        <path d="M3 13C3 11.8869 3.90315 11 5.00873 11L15 11C16.1045 11 17 11.8956 17 13C17 14.6912 16.1672 15.9663 14.865 16.7966C13.583 17.614 11.8547 18 10 18C8.14526 18 6.41697 17.614 5.13499 16.7966C3.83281 15.9663 3 14.6912 3 13ZM5.00873 12C4.44786 12 4 12.4467 4 13C4 14.3088 4.62226 15.2837 5.67262 15.9534C6.74318 16.636 8.26489 17 10 17C11.7351 17 13.2568 16.636 14.3274 15.9534C15.3777 15.2837 16 14.3088 16 13C16 12.4478 15.5522 12 15 12L5.00873 12Z" fill="currentColor" />
    </svg>
);

// Component to render different types of icons
const IconRenderer = ({ icon, size = 20, type = 'lucide' }: { icon: IconType; size?: number; type?: 'lucide' | 'custom' }) => {
    if (type === 'custom') {
        return icon({ size });
    }

    // For Lucide icons
    const Icon = icon;
    return <Icon size={size} />;
};

export default function StudentLeftSidebar({ className, isMobile = false, onClose, ...props }: StudentLeftSidebarProps) {
    const { url } = usePage();
    const currentPath = url;

    // Function to handle missing pages
    const handleMissingPage = (pageName: string) => {
        toast.info(`${pageName} is coming soon!`, {
            description: "This feature is currently under development and will be available soon.",
            duration: 4000,
        });
    };

    const navItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: '/student/dashboard',
            icon: LayoutDashboard,
        },
        {
            title: 'Browse Teachers',
            href: '/student/browse-teachers',
            icon: TeacherIcon,
        },
        {
            title: 'My Bookings',
            href: '/student/my-bookings',
            icon: MessageUserIcon,
        },
        // {
        //     title: 'Memorization Plans',
        //     href: '/student/memorization-plans',
        //     icon: MessageUserIcon,
        // },
        {
            title: 'Payment',
            href: '/student/wallet',
            icon: WalletIcon,
        },
        {
            title: 'Messages',
            href: '#',
            icon: MessageUserIcon,
            isComingSoon: true,
            onClick: (e) => {
                e.preventDefault();
                handleMissingPage('Messages');
            },
        },
        {
            title: 'Profile',
            href: '#',
            icon: UserIcon,
            isComingSoon: true,
            onClick: (e) => {
                e.preventDefault();
                handleMissingPage('Profile');
            },
        },
        {
            title: 'Rating & Feedback',
            href: '#',
            icon: ReviewIcon,
            isComingSoon: true,
            onClick: (e) => {
                e.preventDefault();
                handleMissingPage('Rating & Feedback');
            },
        },
        {
            title: 'Settings',
            href: '#',
            icon: Settings,
            isComingSoon: true,
            onClick: (e) => {
                e.preventDefault();
                handleMissingPage('Settings');
            },
        },
        {
            title: 'Notification',
            href: '/student/notifications',
            icon: Bell,
        },
    ];

    // Check if a navigation item is active
    const isActive = (path: string) => {
        // Don't consider logout link for active state
        if (path === '#') return false;

        // Exact match
        if (currentPath === path) return true;

        // Match path pattern for nested routes
        if (path !== '/student/dashboard' && currentPath.startsWith(path)) return true;

        return false;
    };

    return (
        <>
            <style dangerouslySetInnerHTML={{
                __html: `
                    .scrollbar-hide::-webkit-scrollbar {
                        display: none;
                    }
                `
            }} />
            <div
                className={cn(
                    "flex flex-col bg-teal-600 text-white rounded-xl overflow-y-auto",
                    "w-60 h-full",
                    "scrollbar-hide", // Hide scrollbar
                    isMobile && "shadow-xl",
                    className
                )}
                style={{
                    scrollbarWidth: 'none', /* Firefox */
                    msOverflowStyle: 'none', /* IE and Edge */
                }}
                {...props}
            >
                {isMobile && (
                    <div className="flex justify-between items-center p-4 border-b border-teal-500 flex-shrink-0">
                        <p className="text-xs uppercase tracking-wider text-white/80 font-medium">MAIN</p>
                        <Button variant="ghost" size="sm" className="text-white p-1 h-auto" onClick={onClose}>
                            <X className="h-4 w-4" />
                        </Button>
                    </div>
                )}

                <div className="p-4 space-y-6">
                    {!isMobile && (
                        <p className="text-xs uppercase tracking-wider text-white/80 font-medium px-3 mb-1.5">
                            MAIN
                        </p>
                    )}
                    <nav className="space-y-1">
                        {navItems.map((item, index) => (
                            <React.Fragment key={`nav-item-${index}`}>
                                <Link
                                    href={item.href}
                                    onClick={(e) => {
                                        if (item.onClick) {
                                            item.onClick(e);
                                        }
                                        if (isMobile && onClose) {
                                            onClose();
                                        }
                                    }}
                                    className={cn(
                                        'flex items-center px-3 py-1.5 text-sm font-medium rounded-md transition-colors mx-1',
                                        isActive(item.href)
                                            ? 'bg-[#F3E5C3]/50 text-white'
                                            : 'text-white/90 hover:bg-[rgba(255,255,255,0.08)] hover:text-white'
                                    )}
                                >
                                    <span className="mr-2.5 h-5 w-5 flex-shrink-0 inline-flex items-center justify-center">
                                        <IconRenderer icon={item.icon} size={20} type={item.iconType} />
                                    </span>
                                    <span className="text-sm">{item.title}</span>
                                </Link>
                                {item.divider && (
                                    <div className="h-px bg-white/20 my-1 mx-3"></div>
                                )}
                            </React.Fragment>
                        ))}

                        {/* Logout Button - Part of the navigation list */}
                        <div className="mx-1">
                            <LogoutButton
                                className="flex items-center w-full px-3 py-1.5 text-sm font-medium rounded-md transition-colors text-white/90 hover:bg-[rgba(255,255,255,0.08)] hover:text-white justify-start cursor-pointer"
                                variant="ghost"
                                size="default"
                            />
                        </div>
                    </nav>

                    {/* Promotional Card */}
                    <div className="mt-6 p-4 bg-gradient-to-t from-[#F3E5C3] to-[#F3E5C3]/0 rounded-xl">
                        <div className="flex flex-col items-center text-center space-y-3">
                            <img
                                src="/assets/images/quran.png"
                                alt="Quran on Rehal"
                                className="w-12 h-12 object-contain"
                            />
                            <div className="space-y-2">
                                <h3 className="text-sm font-bold leading-tight bg-gradient-to-r from-[#F3E5C3] to-[#FFFFFF] bg-clip-text text-transparent">
                                    Want your kids to be an Hafiz in 6months ?
                                </h3>
                                <p className="text-xs text-[#FFFFFF] leading-relaxed">
                                    Full Quran, Half Quran, or Juz' Amma - Tailored Learning for Every Student.
                                </p>
                            </div>
                            <Button
                                className="bg-teal-600 text-white hover:bg-teal-700 font-medium py-2 px-3 rounded-full text-xs"
                                // onClick={() => router.visit('/student/memorization-plans')}
                                onClick={(e) => {
                                    e.preventDefault();
                                    handleMissingPage('Memorization Plans');
                                }}
                            >
                                Enroll Now!
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
} 