import { type ReactNode } from 'react';
import TeacherHeader from './teacher-header';
import TeacherLeftSidebar from './teacher-left-sidebar';
import TeacherRightSidebar from './teacher-right-sidebar';

interface TeacherLayoutProps {
    children: ReactNode;
    pageTitle: string;
    showRightSidebar?: boolean;
    rightSidebarContent?: ReactNode;
}

export default function TeacherLayout({ 
    children, 
    pageTitle,
    showRightSidebar = true,
    rightSidebarContent
}: TeacherLayoutProps) {
    return (
        <div className="flex min-h-screen w-full flex-col">
            <TeacherHeader pageTitle={pageTitle} />
            <div className="flex flex-1">
                <div className="pl-25 pt-2">
                    <TeacherLeftSidebar />
                </div>
                <main className="flex-1 p-6">
                    {children}
                </main>
                <div className="pr-25">
                    {showRightSidebar && (
                        <TeacherRightSidebar>
                            {rightSidebarContent}
                        </TeacherRightSidebar>
                    )}
                </div>
            </div>
        </div>
    );
} 