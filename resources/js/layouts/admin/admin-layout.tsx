import { type ReactNode } from 'react';
import AdminHeader from './admin-header';
import AdminLeftSidebar from './admin-left-sidebar';
import AdminRightSidebar from './admin-right-sidebar';

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
    return (
        <div className="flex min-h-screen w-full flex-col">
            <AdminHeader pageTitle={pageTitle} />
            <div className="flex flex-1">
                <div className="pl-25 pt-2">
                    <AdminLeftSidebar />
                </div>
                <main className="flex-1 p-6">
                    {children}
                </main>
                <div className="pr-25">
                    {showRightSidebar && (
                        <AdminRightSidebar>
                            {rightSidebarContent}
                        </AdminRightSidebar>
                    )}
                </div>
            </div>
        </div>
    );
} 