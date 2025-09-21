import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import AppLogoIcon from '@/components/app-logo-icon';

interface ContentLayoutProps {
    children: React.ReactNode;
    title?: string;
    description?: string;
}

export default function ContentLayout({ children, title, description }: ContentLayoutProps) {
    return (
        <div className="min-h-screen bg-gray-50">
            {/* Header */}
            <div className="bg-gradient-to-r from-[#FFF7E4]/30 to-[#FFF7E4]/30 border-b border-gray-200">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between h-16">
                        {/* Logo */}
                        <Link href={route('home')} className="flex items-center">
                            <AppLogoIcon className="h-30 w-auto fill-current text-teal-600" />
                        </Link>
                        
                        {/* Navigation */}
                        <div className="flex items-center space-x-4">
                            <Link href="/register">
                                <Button variant="ghost" size="sm">
                                    <ArrowLeft className="w-4 h-4 mr-2" />
                                    Back to Registration
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            {/* Main Content */}
            <div className="flex-1">
                {children}
            </div>

            {/* Footer */}
            <footer className="bg-gradient-to-r from-[#FFF7E4]/30 to-[#FFF7E4]/30 border-t border-gray-200 mt-16">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div className="text-center">
                        <Link href={route('home')} className="flex items-center justify-center mb-4">
                            <AppLogoIcon className="h-30 w-auto fill-current text-teal-600" />
                        </Link>
                        <p className="text-sm text-gray-500">
                            © {new Date().getFullYear()} IqraQuest. All rights reserved.
                        </p>
                        <div className="mt-4 flex justify-center space-x-6">
                            <Link href="/terms" className="text-sm text-gray-500 hover:text-gray-700">
                                Terms & Conditions
                            </Link>
                            <Link href="/privacy" className="text-sm text-gray-500 hover:text-gray-700">
                                Privacy Policy
                            </Link>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
}
