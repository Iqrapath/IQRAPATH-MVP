import { Head } from '@inertiajs/react';
import { FileText, Shield, Users, CreditCard, AlertTriangle } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import ContentLayout from '@/layouts/content-layout';

interface TermsProps {
    content: string;
}

export default function Terms({ content }: TermsProps) {
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
            <Head title="Terms & Conditions - IqraQuest" />
            
            <div className="py-8">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="text-center mb-8">
                        <div className="inline-flex items-center justify-center w-16 h-16 bg-teal-100 rounded-full mb-4">
                            <FileText className="w-8 h-8 text-teal-600" />
                        </div>
                        <h1 className="text-4xl font-bold text-gray-900 mb-2">Terms & Conditions</h1>
                        <p className="text-lg text-gray-600">
                            Comprehensive terms governing your use of IqraQuest
                        </p>
                    </div>

                    {/* Quick Overview Cards */}
                    <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                        <Card className="text-center">
                            <CardHeader className="pb-2">
                                <Users className="w-8 h-8 text-blue-600 mx-auto mb-2" />
                                <CardTitle className="text-sm">User Accounts</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-xs text-gray-600">Account creation, security, and management</p>
                            </CardContent>
                        </Card>
                        
                        <Card className="text-center">
                            <CardHeader className="pb-2">
                                <Shield className="w-8 h-8 text-green-600 mx-auto mb-2" />
                                <CardTitle className="text-sm">Teacher Verification</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-xs text-gray-600">Qualification requirements and verification process</p>
                            </CardContent>
                        </Card>
                        
                        <Card className="text-center">
                            <CardHeader className="pb-2">
                                <CreditCard className="w-8 h-8 text-purple-600 mx-auto mb-2" />
                                <CardTitle className="text-sm">Payment Terms</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-xs text-gray-600">Pricing, refunds, and payment processing</p>
                            </CardContent>
                        </Card>
                        
                        <Card className="text-center">
                            <CardHeader className="pb-2">
                                <AlertTriangle className="w-8 h-8 text-orange-600 mx-auto mb-2" />
                                <CardTitle className="text-sm">Liability</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-xs text-gray-600">Limitations and disclaimers</p>
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
                        <div className="bg-teal-50 border border-teal-200 rounded-lg p-6 mb-6">
                            <h3 className="text-lg font-semibold text-teal-900 mb-2">Questions about our Terms?</h3>
                            <p className="text-teal-700 mb-4">
                                Our legal team is here to help clarify any questions you may have.
                            </p>
                            <div className="flex flex-col sm:flex-row gap-3 justify-center">
                                <Button asChild>
                                    <a href="mailto:legal@iqraquest.com">
                                        Contact Legal Team
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