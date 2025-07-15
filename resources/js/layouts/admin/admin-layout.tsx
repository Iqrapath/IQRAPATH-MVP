import { type ReactNode } from 'react';
import AdminHeader from './admin-header';
import AdminLeftSidebar from './admin-left-sidebar';
import AdminRightSidebar from './admin-right-sidebar';
import { useState, useEffect } from 'react';

interface AdminLayoutProps {
    children: ReactNode;
    pageTitle: string;
    showRightSidebar?: boolean;
    rightSidebarContent?: ReactNode;
}

export default function AdminLayout({ 
    children, 
    pageTitle,
    showRightSidebar = true,
    rightSidebarContent
}: AdminLayoutProps) {
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
        <div className="flex flex-col h-screen w-full overflow-hidden bg-gray-50">
            <AdminHeader 
                pageTitle={pageTitle} 
                toggleLeftSidebar={toggleLeftSidebar}
                toggleRightSidebar={toggleRightSidebar}
                isMobile={isMobile}
            />
            <div className="flex flex-1 relative overflow-hidden">
                {/* Left Sidebar - Hidden on mobile by default, shown when toggled */}
                <div className={`${isMobile ? 'absolute z-30 h-full' : 'pl-25 pt-2'} ${isMobile && !showLeftSidebar ? 'hidden' : 'block'}`}>
                    <AdminLeftSidebar 
                        isMobile={isMobile}
                        // onClose={closeLeftSidebar}
                        // className="h-full overflow-y-auto"
                    />
                </div>

                {/* Main Content */}
                <main className="flex-1 p-6 overflow-y-auto">
                    {children}
                </main>

                {/* Right Sidebar - Hidden on mobile by default, shown when toggled */}
                <div className={`${isMobile ? 'absolute right-0 z-30 h-full' : 'pr-25'} ${isMobile && !showRightSidebarMobile ? 'hidden' : 'block'}`}>
                    {showRightSidebar && (
                        <AdminRightSidebar
                            isMobile={isMobile}
                            onClose={closeRightSidebar}
                            className="h-full overflow-y-auto"
                        >
                            {rightSidebarContent}
                        </AdminRightSidebar>
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