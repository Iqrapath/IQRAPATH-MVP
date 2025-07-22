import { AppContent } from '@/components/app-content';
import { AppHeader } from '@/components/app-header';
import { AppShell } from '@/components/app-shell';
import { type BreadcrumbItem, type PageProps } from '@/types';
import type { PropsWithChildren } from 'react';
import { AppLoading } from '@/components/app-loading';
import { usePage } from '@inertiajs/react';

interface AppHeaderLayoutProps {
    title?: string;
    children: React.ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

export default function AppHeaderLayout({ title = 'App', children, breadcrumbs = [] }: AppHeaderLayoutProps) {
    // Using optional chaining to safely access auth.user
    const { auth } = usePage<PageProps>().props;

    return (
        <div className="flex min-h-screen flex-col">
            <AppLoading />
            <AppShell>
                <AppHeader 
                    breadcrumbs={breadcrumbs} 
                    pageTitle={title} 
                />
                <AppContent>{children}</AppContent>
            </AppShell>
        </div>
    );
}
