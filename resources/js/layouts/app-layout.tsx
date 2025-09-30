import AppLayoutTemplate from '@/layouts/app/app-header-layout';
import { type BreadcrumbItem } from '@/types';
import { type ReactNode } from 'react';

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
    pageTitle?: string;
}

export default ({ children, breadcrumbs, pageTitle, ...props }: AppLayoutProps) => (
    <AppLayoutTemplate breadcrumbs={breadcrumbs} pageTitle={pageTitle} {...props} scrollbar={true}>
        {children}
    </AppLayoutTemplate>
);
