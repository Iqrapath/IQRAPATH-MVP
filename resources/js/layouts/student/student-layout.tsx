import { type ReactNode } from 'react';
import StudentHeader from './student-header';
import StudentLeftSidebar from './student-left-sidebar';
import StudentRightSidebar from './student-right-sidebar';

interface StudentLayoutProps {
    children: ReactNode;
    pageTitle: string;
    showRightSidebar?: boolean;
    rightSidebarContent?: ReactNode;
}

export default function StudentLayout({ 
    children, 
    pageTitle,
    showRightSidebar = true,
    rightSidebarContent
}: StudentLayoutProps) {
    return (
        <div className="flex min-h-screen w-full flex-col">
            <StudentHeader pageTitle={pageTitle} />
            <div className="flex flex-1">
                <div className="pl-25 pt-2">
                    <StudentLeftSidebar />
                </div>
                <main className="flex-1 p-6">
                    {children}
                </main>
                <div className="pr-25">
                    {showRightSidebar && (
                        <StudentRightSidebar>
                            {rightSidebarContent}
                        </StudentRightSidebar>
                    )}
                </div>
            </div>
        </div>
    );
} 