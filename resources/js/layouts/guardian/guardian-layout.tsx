import { type ReactNode } from 'react';
import GuardianHeader from './guardian-header';
import GuardianLeftSidebar from './guardian-left-sidebar';
import GuardianRightSidebar from './guardian-right-sidebar';
import { useState, useEffect } from 'react';

interface GuardianLayoutProps {
    children: ReactNode;
    pageTitle: string;
    showRightSidebar?: boolean;
    rightSidebarContent?: ReactNode;
}

export default function GuardianLayout({ 
    children, 
    pageTitle,
    showRightSidebar = true,
    rightSidebarContent
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
        <div className="flex min-h-screen w-full flex-col">
            <GuardianHeader 
                pageTitle={pageTitle} 
                toggleLeftSidebar={toggleLeftSidebar}
                toggleRightSidebar={toggleRightSidebar}
                isMobile={isMobile}
            />
            <div className="flex flex-1 relative">
                {/* Left Sidebar - Hidden on mobile by default, shown when toggled */}
                <div className={`${isMobile ? 'absolute z-30 h-full' : 'pl-25 pt-2'} ${isMobile && !showLeftSidebar ? 'hidden' : 'block'}`}>
                    <GuardianLeftSidebar 
                        isMobile={isMobile}
                        onClose={closeLeftSidebar}
                    />
                </div>

                {/* Main Content */}
                <main className="flex-1 p-6 overflow-x-auto">
                    {children}
                </main>

                {/* Right Sidebar - Hidden on mobile by default, shown when toggled */}
                <div className={`${isMobile ? 'absolute right-0 z-30 h-full' : 'pr-25'} ${isMobile && !showRightSidebarMobile ? 'hidden' : 'block'}`}>
                    {showRightSidebar && (
                        <GuardianRightSidebar
                            isMobile={isMobile}
                            onClose={closeRightSidebar}
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
    );
} 