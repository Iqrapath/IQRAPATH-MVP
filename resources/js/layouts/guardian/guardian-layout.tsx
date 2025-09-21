import { type ReactNode } from 'react';
import GuardianHeader from './guardian-header';
import { useState, useEffect } from 'react';
import GuardianLeftSidebar from './guardian-left-sidebar';
import GuardianRightSidebar from './guardian-right-sidebar';
import { AppLoading } from '@/components/app-loading';

interface Notification {
    id: string;
    sender: string;
    message: string;
    timestamp: string;
    avatar?: string | null;
    type: string;
    is_read: boolean;
}

interface GuardianLayoutProps {
    children: ReactNode;
    pageTitle: string;
    showRightSidebar?: boolean;
    rightSidebarContent?: ReactNode;
    notifications?: Notification[];
}

export default function GuardianLayout({ 
    children, 
    pageTitle,
    showRightSidebar = true,
    rightSidebarContent,
    notifications = []
}: GuardianLayoutProps) {
    const [isMobile, setIsMobile] = useState(false);
    const [showLeftSidebar, setShowLeftSidebar] = useState(false);
    const [showRightSidebarMobile, setShowRightSidebarMobile] = useState(false);

    // Handle responsive behavior
    useEffect(() => {
        const handleResize = () => {
            const mobile = window.innerWidth < 1024;
            setIsMobile(mobile);
            // Hide sidebars on mobile by default
            if (mobile) {
                setShowLeftSidebar(false);
                setShowRightSidebarMobile(false);
            }
        };
        
        // Set initial state
        handleResize();
        
        // Add event listener
        window.addEventListener('resize', handleResize);
        
        // Cleanup
        return () => {
            window.removeEventListener('resize', handleResize);
        };
    }, []);

    // Toggle sidebar visibility on mobile
    const toggleLeftSidebar = () => {
        setShowLeftSidebar(!showLeftSidebar);
        if (showRightSidebarMobile) setShowRightSidebarMobile(false);
    };

    const toggleRightSidebar = () => {
        setShowRightSidebarMobile(!showRightSidebarMobile);
        if (showLeftSidebar) setShowLeftSidebar(false);
    };

    const closeLeftSidebar = () => {
        setShowLeftSidebar(false);
    };

    const closeRightSidebar = () => {
        setShowRightSidebarMobile(false);
    };

    return (
        <>
            <AppLoading />
            <div className="flex flex-col h-screen w-full overflow-hidden bg-gray-50">
                <GuardianHeader 
                    pageTitle={pageTitle} 
                    toggleLeftSidebar={toggleLeftSidebar}
                    toggleRightSidebar={toggleRightSidebar}
                    isMobile={isMobile}
                />
                <div className="flex flex-1 relative overflow-hidden">
                    {/* Left Sidebar - Hidden on mobile by default, shown when toggled */}
                    <div className={`${isMobile ? 'absolute z-30 h-full' : 'pl-25 pt-2'} ${isMobile && !showLeftSidebar ? 'hidden' : 'block'}`}>
                        <GuardianLeftSidebar 
                            isMobile={isMobile}
                            // onClose={closeLeftSidebar}
                            // className="h-full overflow-y-auto"
                        />
                    </div>

                    {/* Main Content */}
                    <main className="flex-1 p-6 overflow-y-auto scrollbar-hide scrollbar-thin scrollbar-thumb-teal-600"
                    style={{
                        scrollbarWidth: 'none', /* Firefox */
                        msOverflowStyle: 'none', /* IE and Edge */
                    }}>
                        {children}
                    </main>

                    {/* Right Sidebar - Hidden on mobile by default, shown when toggled */}
                    <div className={`${isMobile ? 'absolute right-0 z-30 h-full' : 'pr-25'} ${isMobile && !showRightSidebarMobile ? 'hidden' : 'block'}`}>
                        {showRightSidebar && (
                            <GuardianRightSidebar
                                isMobile={isMobile}
                                onClose={closeRightSidebar}
                                className="h-full overflow-y-auto scrollbar-hide"
                                notifications={notifications}
                            >
                                {rightSidebarContent}
                            </GuardianRightSidebar>
                        )}
                    </div>
                </div>

                {/* Mobile overlay when sidebar is open */}
                {isMobile && (showLeftSidebar || showRightSidebarMobile) && (
                    <div 
                        className="fixed inset-0 bg-white/10 backdrop-blur-sm z-20"
                        onClick={() => {
                            setShowLeftSidebar(false);
                            setShowRightSidebarMobile(false);
                        }}
                    />
                )}
            </div>
        </>
    );
} 