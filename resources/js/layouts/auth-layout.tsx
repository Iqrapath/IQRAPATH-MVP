import AuthLayoutTemplate from '@/layouts/auth/auth-split-layout';

export default function AuthLayout({ children, ...props }: { children: React.ReactNode }) {
    return (
        <AuthLayoutTemplate {...props}>
            {children}
        </AuthLayoutTemplate>
    );
}
