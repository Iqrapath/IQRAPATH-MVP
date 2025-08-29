// import { AppContent } from '@/components/app-content';
// import { AppHeader } from '@/components/app-header';
// import { AppShell } from '@/components/app-shell';
// import { type BreadcrumbItem, type PageProps } from '@/types';
// import type { PropsWithChildren } from 'react';
// import { AppLoading } from '@/components/app-loading';
// import { usePage } from '@inertiajs/react';

// interface AppHeaderLayoutProps {
//     title?: string;
//     children: React.ReactNode;
//     breadcrumbs?: BreadcrumbItem[];
// }

// export default function AppHeaderLayout({ title = 'App', children, breadcrumbs = [] }: AppHeaderLayoutProps) {
//     // Using optional chaining to safely access auth.user
//     const { auth } = usePage<PageProps>().props;

//     return (
//         <div className="flex min-h-screen flex-col">
//             <AppLoading />
//             <AppShell>
//                 <div className="sticky top-0 z-10">
//                     <AppHeader
//                         breadcrumbs={breadcrumbs}
//                         pageTitle={title}
//                     />
//                 </div>
//                 <AppContent>{children}</AppContent>
//             </AppShell>
//         </div>
//     );
// }

import { type ReactNode } from 'react';
import { AppContent } from '@/components/app-content';
import { AppHeader } from '@/components/app-header';
import { AppShell } from '@/components/app-shell';
import { type BreadcrumbItem, type PageProps } from '@/types';
import { useState, useEffect } from 'react';
import { AppLoading } from '@/components/app-loading';

interface AppHeaderLayoutProps {
    children: React.ReactNode;
    breadcrumbs?: BreadcrumbItem[];
    pageTitle?: string;
    
}

export default function AppHeaderLayout({ 
    children, 
    pageTitle,
}: AppHeaderLayoutProps) {
    const [isMobile, setIsMobile] = useState(false);

    // Handle responsive behavior
    useEffect(() => {
        const handleResize = () => {
            const mobile = window.innerWidth < 1024;
            setIsMobile(mobile);
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



    return (
        <>
            <AppLoading />
            <div className="flex flex-col h-screen w-full overflow-hidden bg-gray-50">
                <AppHeader 
                    pageTitle={pageTitle} 
                    isMobile={isMobile}
                />
                <div className="flex flex-1 relative overflow-hidden">

                    {/* Main Content */}
                    <main className="flex-1 p-6 overflow-y-auto">
                        {children}
                    </main>

                </div>
            </div>
        </>
    );
} 