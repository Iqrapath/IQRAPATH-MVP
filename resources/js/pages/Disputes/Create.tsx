import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import AppLayout from '@/layouts/app-layout';
import { PageProps, User } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertCircle, FileText, Users, MessageSquare } from 'lucide-react';

interface Props extends PageProps {
    users: User[];
}

export default function CreateDispute({ auth, users }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        against: '',
        subject: '',
        description: '',
        category: 'teacher_rejection',
        evidence: null as File | null,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('disputes.store'), {
            onSuccess: () => {
                reset();
            },
        });
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0] || null;
        setData('evidence', file);
    };

    return (
        <AppLayout
            pageTitle="File a Dispute"
        >
            <Head title="File a Dispute" />

            <div className="py-12">
                <div className="max-w-2xl mx-auto sm:px-6 lg:px-8">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileText className="h-5 w-5" />
                                Create New Dispute
                            </CardTitle>
                            <CardDescription>
                                File a dispute if you believe there has been an error in your teacher application review or if you need to appeal a decision.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-6">
                                {/* Category Selection */}
                                <div className="space-y-2">
                                    <Label htmlFor="category">Dispute Category</Label>
                                    <Select
                                        value={data.category}
                                        onValueChange={(value) => setData('category', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select a category" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="teacher_rejection">Teacher Application Rejection</SelectItem>
                                            <SelectItem value="payment_issue">Payment Issue</SelectItem>
                                            <SelectItem value="service_quality">Service Quality</SelectItem>
                                            <SelectItem value="account_suspension">Account Suspension</SelectItem>
                                            <SelectItem value="other">Other</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.category && (
                                        <p className="text-sm text-red-600">{errors.category}</p>
                                    )}
                                </div>

                                {/* Subject */}
                                <div className="space-y-2">
                                    <Label htmlFor="subject">Subject *</Label>
                                    <Input
                                        id="subject"
                                        type="text"
                                        value={data.subject}
                                        onChange={(e) => setData('subject', e.target.value)}
                                        placeholder="Brief description of your dispute"
                                        className="w-full"
                                    />
                                    {errors.subject && (
                                        <p className="text-sm text-red-600">{errors.subject}</p>
                                    )}
                                </div>

                                {/* Description */}
                                <div className="space-y-2">
                                    <Label htmlFor="description">Detailed Description *</Label>
                                    <Textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        placeholder="Please provide a detailed explanation of your dispute, including any relevant information that might help resolve the issue."
                                        className="min-h-[120px] resize-none"
                                    />
                                    {errors.description && (
                                        <p className="text-sm text-red-600">{errors.description}</p>
                                    )}
                                </div>

                                {/* Evidence Upload */}
                                <div className="space-y-2">
                                    <Label htmlFor="evidence">Supporting Evidence (Optional)</Label>
                                    <Input
                                        id="evidence"
                                        type="file"
                                        onChange={handleFileChange}
                                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                        className="w-full"
                                    />
                                    <p className="text-xs text-gray-500">
                                        Upload documents, screenshots, or other evidence that supports your dispute. 
                                        Accepted formats: PDF, DOC, DOCX, JPG, JPEG, PNG (Max 10MB)
                                    </p>
                                    {errors.evidence && (
                                        <p className="text-sm text-red-600">{errors.evidence}</p>
                                    )}
                                </div>

                                {/* Information Alert */}
                                <Alert>
                                    <AlertCircle className="h-4 w-4" />
                                    <AlertDescription>
                                        <strong>Important:</strong> Please provide as much detail as possible in your dispute. 
                                        Our support team will review your case and respond within 24-48 hours. 
                                        False or misleading information may result in account restrictions.
                                    </AlertDescription>
                                </Alert>

                                {/* Submit Buttons */}
                                <div className="flex items-center justify-end space-x-3 pt-4">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => window.history.back()}
                                        disabled={processing}
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="bg-teal-600 hover:bg-teal-700"
                                    >
                                        {processing ? 'Submitting...' : 'Submit Dispute'}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    {/* Help Section */}
                    <Card className="mt-6">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Users className="h-5 w-5" />
                                Need Help?
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3 text-sm text-gray-600">
                                <p>
                                    <strong>For Teacher Application Rejections:</strong> If your teacher application was rejected, 
                                    please include the rejection reason you received and explain why you believe the decision should be reconsidered.
                                </p>
                                <p>
                                    <strong>For Payment Issues:</strong> Include transaction details, payment method, and any error messages you encountered.
                                </p>
                                <p>
                                    <strong>For Service Quality:</strong> Describe the specific issues you experienced and how they affected your learning or teaching experience.
                                </p>
                                <p>
                                    <strong>Contact Support:</strong> If you need immediate assistance, you can also contact our support team at{' '}
                                    <a href="mailto:support@iqraquest.com" className="text-teal-600 hover:underline">
                                        support@iqraquest.com
                                    </a>
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
