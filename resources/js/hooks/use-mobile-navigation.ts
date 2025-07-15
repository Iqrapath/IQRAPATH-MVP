import { useState, useEffect } from 'react';

export function useMobileNavigation() {
    const [isMobile, setIsMobile] = useState(false);
    const [leftSidebarOpen, setLeftSidebarOpen] = useState(false);
    const [rightSidebarOpen, setRightSidebarOpen] = useState(false);

    // Handle responsive behavior
    useEffect(() => {
        const handleResize = () => {
            const mobile = window.innerWidth < 1024;
            setIsMobile(mobile);
            
            // Hide sidebars on mobile by default
            if (mobile) {
                setLeftSidebarOpen(false);
                setRightSidebarOpen(false);
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
        setLeftSidebarOpen(!leftSidebarOpen);
        if (rightSidebarOpen) setRightSidebarOpen(false);
    };

    const toggleRightSidebar = () => {
        setRightSidebarOpen(!rightSidebarOpen);
        if (leftSidebarOpen) setLeftSidebarOpen(false);
    };

    return {
        isMobile,
        leftSidebarOpen,
        rightSidebarOpen,
        toggleLeftSidebar,
        toggleRightSidebar,
    };
}
