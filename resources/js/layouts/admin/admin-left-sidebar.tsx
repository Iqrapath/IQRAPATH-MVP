import { cn } from '@/lib/utils';
import { Link, router, usePage } from '@inertiajs/react';
import {
    BookOpen,
    Calendar,
    CreditCard,
    LogOut,
    Star,
    Bell,
    Settings,
    Users,
    CheckCircle,
    LayoutDashboard,
    FileText,
    LifeBuoy,
    Cog,
    LucideIcon,
    BookOpenCheck,
    UserCheck,
    Briefcase,
    MessageSquare,
    X
} from 'lucide-react';
import React from 'react';
import { Button } from '@/components/ui/button';
import LogoutButton from '@/components/logout-button';
import { toast } from 'sonner';

interface AdminLeftSidebarProps extends React.HTMLAttributes<HTMLDivElement> {
    isMobile?: boolean;
    isOpen?: boolean;
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

// Parent Management Icon
const ParentManagementIcon = ({ size = 20 }) => (
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="currentColor"
        width={size}
        height={size}
    >
        <path d="M7 9C8.38071 9 9.5 7.88071 9.5 6.5C9.5 5.11929 8.38071 4 7 4C5.61929 4 4.5 5.11929 4.5 6.5C4.5 7.88071 5.61929 9 7 9ZM7 11C4.51472 11 2.5 8.98528 2.5 6.5C2.5 4.01472 4.51472 2 7 2C9.48528 2 11.5 4.01472 11.5 6.5C11.5 8.98528 9.48528 11 7 11ZM17.5 13C18.6046 13 19.5 12.1046 19.5 11C19.5 9.89543 18.6046 9 17.5 9C16.3954 9 15.5 9.89543 15.5 11C15.5 12.1046 16.3954 13 17.5 13ZM17.5 15C15.2909 15 13.5 13.2091 13.5 11C13.5 8.79086 15.2909 7 17.5 7C19.7091 7 21.5 8.79086 21.5 11C21.5 13.2091 19.7091 15 17.5 15ZM20 21V20.5C20 19.1193 18.8807 18 17.5 18C16.1193 18 15 19.1193 15 20.5V21H13V20.5C13 18.0147 15.0147 16 17.5 16C19.9853 16 22 18.0147 22 20.5V21H20ZM10 21V17C10 15.3431 8.65685 14 7 14C5.34315 14 4 15.3431 4 17V21H2V17C2 14.2386 4.23858 12 7 12C9.76142 12 12 14.2386 12 17V21H10Z"></path>
    </svg>
);

// Subscription Plan Icon
const SubscriptionPlanIcon = ({ size = 20 }) => (
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="currentColor"
        width={size}
        height={size}
    >
        <path d="M20,8H4V6H20ZM18,2H6V4H18Z"/>
        <path d="M10.31183,21.1l-3.3-3.3,1.4-1.4,1.9,1.9,5.3-5.3,1.4,1.4Z"/>
        <path d="M22,10H2a2,2,0,0,0-2,2V22a2,2,0,0,0,2,2H22a2,2,0,0,0,2-2V12A2,2,0,0,0,22,10Zm0,12H2V12H22Z"/>
    </svg>
);

// CMS Icon
const CMSIcon = ({ size = 20 }) => (
    <svg
        role="img"
        viewBox="0 0 24 24"
        xmlns="http://www.w3.org/2000/svg"
        fill="currentColor"
        width={size}
        height={size}
    >
        <path d="M11.068 0 22.08 6.625v12.573L13.787 24V11.427L2.769 4.808 11.068 0ZM1.92 18.302l8.31-4.812v9.812l-8.31-5Z"/>
    </svg>
);

// Feedback & Support Icon
const FeedbackSupportIcon = ({ size = 20 }) => (
    <svg
        fill="none"
        height={size}
        viewBox="0 0 20 20"
        width={size}
        xmlns="http://www.w3.org/2000/svg"
    >
        <path d="M6.53039 4.00821C7.22083 2.80808 8.51605 2 10 2C12.2091 2 14 3.79086 14 6C14 8.20914 12.2091 10 10 10C8.40521 10 7.02841 9.0667 6.38598 7.71649C6.13814 7.89746 5.8327 8.00427 5.50232 8.00427H5.0022V8.25891C5.0022 8.44889 5.07392 8.62212 5.19176 8.75301C5.32913 8.59883 5.52931 8.50171 5.7522 8.50171C6.16641 8.50171 6.5022 8.83711 6.5022 9.25085C6.5022 9.6646 6.16641 10 5.7522 10C5.7278 10 5.70368 9.99884 5.67989 9.99656C4.74788 9.96442 4.0022 9.19875 4.0022 8.25891L4.00232 4.50744C4.00232 4.2313 4.22618 4.00744 4.50232 4.00744L6.50232 4.00744C6.51174 4.00744 6.5211 4.0077 6.53039 4.00821ZM7.00232 6.11906C7.06483 7.72073 8.38302 9 10 9C11.6569 9 13 7.65685 13 6C13 4.34315 11.6569 3 10 3C8.38302 3 7.06483 4.27927 7.00232 5.88094V6.11906ZM6.00232 5.86248V5.00744L5.00232 5.00744L5.00232 7.00427H5.50232C5.77846 7.00427 6.00232 6.78042 6.00232 6.50427V6.13752C6.00078 6.09187 6 6.04603 6 6C6 5.95397 6.00078 5.90813 6.00232 5.86248Z" fill="currentColor"/>
        <path d="M3 13C3 11.8869 3.90315 11 5.00873 11L15 11C16.1045 11 17 11.8956 17 13C17 14.6912 16.1672 15.9663 14.865 16.7966C13.583 17.614 11.8547 18 10 18C8.14526 18 6.41697 17.614 5.13499 16.7966C3.83281 15.9663 3 14.6912 3 13ZM5.00873 12C4.44786 12 4 12.4467 4 13C4 14.3088 4.62226 15.2837 5.67262 15.9534C6.74318 16.636 8.26489 17 10 17C11.7351 17 13.2568 16.636 14.3274 15.9534C15.3777 15.2837 16 14.3088 16 13C16 12.4478 15.5522 12 15 12L5.00873 12Z" fill="currentColor"/>
    </svg>
);

// Teacher Management Icon
const TeacherIcon = ({ size = 20 }) => (
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="currentColor"
        width={size}
        height={size}
    >
        <path d="M12 2L2 7L12 12L22 7L12 2ZM12 15L4 10.9V16.7L12 21.7L20 16.7V10.9L12 15Z"/>
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

export default function AdminLeftSidebar({ className, isMobile = false, isOpen = true, ...props }: AdminLeftSidebarProps) {
    const { url } = usePage();
    const currentPath = url;

    // If mobile and not open, don't render
    if (isMobile && !isOpen) {
        return null;
    }

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
            href: '/admin/dashboard',
            icon: LayoutDashboard,
        },
        {
            title: 'Teacher Management',
            href: '/admin/teachers',
            icon: TeacherIcon,
            iconType: 'custom',
        },
        {
            title: 'Student Management',
            href: '/admin/students',
            icon: ParentManagementIcon,
            iconType: 'custom',
        },
        {
            title: 'Booking Management',
            href: '/admin/bookings',
            icon: Calendar,
        },
        {
            title: 'Verification Requests',
            href: '/admin/verification',
            icon: CheckCircle,
        },
        {
            title: 'Subscription Plan',
            href: '/admin/subscription-plans',
            icon: SubscriptionPlanIcon,
            iconType: 'custom',
        },
        {
            title: 'Guardian Management',
            href: '/admin/guardians',
            icon: Users,
            isComingSoon: true,
            onClick: (e) => {
                e.preventDefault();
                handleMissingPage('Guardian Management');
            },
        },
        {
            title: 'Payment Management',
            href: '/admin/payments',
            icon: CreditCard,
            divider: true,
            isComingSoon: true,
            onClick: (e) => {
                e.preventDefault();
                handleMissingPage('Payment Management');
            },
        },
        {
            title: 'CMS',
            href: '/admin/cms',
            icon: CMSIcon,
            iconType: 'custom',
            isComingSoon: true,
            onClick: (e) => {
                e.preventDefault();
                handleMissingPage('CMS');
            },
        },
        {
            title: 'Admin Controls',
            href: '/admin/controls',
            icon: Cog,
            isComingSoon: true,
            onClick: (e) => {
                e.preventDefault();
                handleMissingPage('Admin Controls');
            },
        },
        {
            title: 'Referral System',
            href: '/admin/referrals',
            icon: Star,
            divider: true,
            isComingSoon: true,
            onClick: (e) => {
                e.preventDefault();
                handleMissingPage('Referrals');
            },
        },
        {
            title: 'Settings & Security',
            href: '/admin/settings',
            icon: Settings,
            isComingSoon: true,
            onClick: (e) => {
                e.preventDefault();
                handleMissingPage('Settings');
            },
        },
        {
            title: 'Notification System',
            href: '/admin/notifications',
            icon: Bell,
        },
        {
            title: 'Feedback & Support',
            href: '/admin/feedback',
            icon: FeedbackSupportIcon,
            iconType: 'custom',
            isComingSoon: true,
            onClick: (e) => {
                e.preventDefault();
                handleMissingPage('Feedback & support');
            },
        },
        // Logout item removed from here
    ];

    // Check if a navigation item is active
    const isActive = (path: string) => {
        // Exact match
        if (currentPath === path) return true;

        // Match path pattern for nested routes
        if (path !== '/dashboard' && currentPath.startsWith(path)) return true;

        return false;
    };

    return (
        <div
            className={cn(
                "flex flex-col pb-4 bg-teal-600 text-white rounded-xl overflow-hidden relative",
                "w-60",
                isMobile && "shadow-xl",
                className
            )}
            {...props}
        >
            {isMobile && (
                <div className="flex justify-between items-center p-4 border-b border-teal-500">
                    <p className="text-xs uppercase tracking-wider text-white/80 font-medium">MAIN</p>
                    <Button variant="ghost" size="sm" className="text-white p-1 h-auto">
                        <X className="h-4 w-4" />
                    </Button>
                </div>
            )}
            <div className="flex-1 overflow-y-auto">
                <div className={`${isMobile ? '' : 'pt-5'} px-1`}>
                    {!isMobile && (
                        <p className="text-xs uppercase tracking-wider text-white/80 font-medium px-3 mb-1.5">
                            MAIN
                        </p>
                    )}
                    <nav className="space-y-1">
                        {navItems.map((item, index) => (
                            <React.Fragment key={item.href || index}>
                                <Link
                                    href={item.href}
                                    onClick={(e) => {
                                        if (item.onClick) {
                                            item.onClick(e);
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
                        
                        {/* Logout Button - Added separately for better control */}
                        <div className="mx-1 mt-2">
                            <LogoutButton
                                className="flex items-center w-full px-3 py-1.5 text-sm font-medium rounded-md transition-colors text-white/90 hover:bg-[rgba(255,255,255,0.08)] hover:text-white justify-start"
                                variant="ghost"
                            />
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    );
} 