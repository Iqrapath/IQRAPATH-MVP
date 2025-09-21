import { Head } from '@inertiajs/react';
import { Shield, Eye, Lock, Database, Users, Globe } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import ContentLayout from '@/layouts/content-layout';

interface PrivacyProps {
    content: string;
}

export default function Privacy({ content }: PrivacyProps) {
    // Convert markdown to HTML
    const convertMarkdownToHtml = (markdown: string) => {
        return markdown
            .replace(/^# (.*$)/gim, '<h1 class="text-3xl font-bold text-gray-900 mb-6">$1</h1>')
            .replace(/^## (.*$)/gim, '<h2 class="text-2xl font-semibold text-gray-800 mb-4 mt-8">$1</h2>')
            .replace(/^### (.*$)/gim, '<h3 class="text-xl font-medium text-gray-700 mb-3 mt-6">$1</h3>')
            .replace(/^#### (.*$)/gim, '<h4 class="text-lg font-medium text-gray-700 mb-2 mt-4">$1</h4>')
            .replace(/^\*\* (.*$)/gim, '<li class="font-medium text-gray-700">$1</li>')
            .replace(/^- (.*$)/gim, '<li class="text-gray-600 ml-4">$1</li>')
            .replace(/\*\*(.*?)\*\*/g, '<strong class="font-semibold text-gray-800">$1</strong>')
            .replace(/\*(.*?)\*/g, '<em class="italic text-gray-700">$1</em>')
            .replace(/`(.*?)`/g, '<code class="bg-gray-100 px-2 py-1 rounded text-sm font-mono">$1</code>')
            .replace(/\n\n/g, '</p><p class="mb-4 text-gray-600 leading-relaxed">')
            .replace(/\n/g, '<br />')
            .replace(/^(?!<[h|p|l|s|c])/gm, '<p class="mb-4 text-gray-600 leading-relaxed">')
            .replace(/(<li[^>]*>.*<\/li>)/g, '<ul class="list-disc list-inside mb-4 space-y-2">$1</ul>')
            .replace(/<ul class="list-disc list-inside mb-4 space-y-2"><ul class="list-disc list-inside mb-4 space-y-2">/g, '<ul class="list-disc list-inside mb-4 space-y-2">')
            .replace(/<\/ul><\/ul>/g, '</ul>');
    };

    const htmlContent = convertMarkdownToHtml(content);

    return (
        <ContentLayout>
            <Head title="Privacy Policy - IqraQuest" />
            
            <div className="py-8">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="text-center mb-8">
                        <div className="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                            <Shield className="w-8 h-8 text-blue-600" />
                        </div>
                        <h1 className="text-4xl font-bold text-gray-900 mb-2">Privacy Policy</h1>
                        <p className="text-lg text-gray-600">
                            How we protect and handle your personal information
                        </p>
                    </div>

                    {/* Quick Overview Cards */}
                    <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                        <Card className="text-center">
                            <CardHeader className="pb-2">
                                <Database className="w-8 h-8 text-blue-600 mx-auto mb-2" />
                                <CardTitle className="text-sm">Data Collection</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-xs text-gray-600">What information we collect and why</p>
                            </CardContent>
                        </Card>
                        
                        <Card className="text-center">
                            <CardHeader className="pb-2">
                                <Lock className="w-8 h-8 text-green-600 mx-auto mb-2" />
                                <CardTitle className="text-sm">Data Security</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-xs text-gray-600">How we protect your information</p>
                            </CardContent>
                        </Card>
                        
                        <Card className="text-center">
                            <CardHeader className="pb-2">
                                <Users className="w-8 h-8 text-purple-600 mx-auto mb-2" />
                                <CardTitle className="text-sm">Your Rights</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-xs text-gray-600">Control over your personal data</p>
                            </CardContent>
                        </Card>
                        
                        <Card className="text-center">
                            <CardHeader className="pb-2">
                                <Globe className="w-8 h-8 text-orange-600 mx-auto mb-2" />
                                <CardTitle className="text-sm">Global Compliance</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-xs text-gray-600">GDPR, CCPA, and international standards</p>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Content */}
                    <Card className="shadow-lg">
                        <CardContent className="p-8">
                            <div 
                                className="prose prose-gray max-w-none"
                                dangerouslySetInnerHTML={{ __html: htmlContent }}
                            />
                        </CardContent>
                    </Card>

                    {/* Footer */}
                    <div className="mt-8 text-center">
                        <div className="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                            <h3 className="text-lg font-semibold text-blue-900 mb-2">Privacy Questions?</h3>
                            <p className="text-blue-700 mb-4">
                                Our privacy team is available to address any concerns about your data.
                            </p>
                            <div className="flex flex-col sm:flex-row gap-3 justify-center">
                                <Button asChild>
                                    <a href="mailto:privacy@iqraquest.com">
                                        Contact Privacy Team
                                    </a>
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href="/register">
                                        Return to Registration
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </ContentLayout>
    );
}