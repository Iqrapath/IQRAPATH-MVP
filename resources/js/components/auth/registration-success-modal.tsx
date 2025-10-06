import { Mail, LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import { type User } from '@/types';

import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import AppLogoIcon from '@/components/app-logo-icon';

interface RegistrationSuccessModalProps {
    isOpen: boolean;
    user: User;
    onClose?: () => void;
}

export default function RegistrationSuccessModal({ isOpen, user, onClose }: RegistrationSuccessModalProps) {
    const [resendStatus, setResendStatus] = useState<'idle' | 'success' | 'error'>('idle');
    const { post, processing } = useForm({
        email: user.email || '',
    });

    const handleResendEmail = () => {
        post(route('verification.resend'), {
            onSuccess: () => {
                setResendStatus('success');
            },
            onError: () => {
                setResendStatus('error');
            },
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => {
            if (!open) {
                // Log out the user when modal is closed
                router.post(route('logout'), {}, {
                    onSuccess: () => {
                        if (onClose) {
                            onClose();
                        }
                    }
                });
            }
        }}>
            <DialogHeader>
                <DialogTitle>Thank you for signing up!</DialogTitle>
                <DialogDescription>
                    A message with a confirmation link has been sent to your email address.
                    Kindly open the link to activate your account.
                </DialogDescription>
            </DialogHeader>
            <DialogContent className="sm:max-w-md border-0 p-10">
                <div className="text-center space-y-6">
                    {/* IqraQuest Logo */}
                    <div className="flex items-center justify-center">
                        <div className="flex items-center -mt-6">
                            <AppLogoIcon className="w-auto h-30" />
                        </div>
                    </div>

                    {/* Success Icon with Background */}
                    <div className="relative mx-auto -mt-10 w-24 h-24">
                        {/* Light teal background rectangles */}
                        <div className="absolute -top-0 -left-0 w-10 h-10 bg-teal-100 rounded-lg opacity-60"></div>
                        <div className="absolute -bottom-2 -right-2 w-16 h-16 bg-teal-100 rounded-lg opacity-40"></div>

                        {/* Main circle with check */}
                        <div className="absolute inset-0 flex items-center justify-center">
                            <div className="w-16 h-16 bg-teal-600 rounded-full flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" className="text-white">
                                    <g fill="none" stroke="currentColor" strokeWidth="2">
                                        {/* <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12s4.477 10 10 10s10-4.477 10-10Z" /> */}
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M8 12.75s1.6.912 2.4 2.25c0 0 2.4-5.25 5.6-7" />
                                    </g>
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* Success Message */}
                    <div className="space-y-3">
                        <h2 className="text-2xl font-bold text-gray-900">
                            Thank you for signing up!
                        </h2>
                        <p className="text-gray-600 text-sm leading-relaxed max-w-sm mx-auto">
                            A message with a confirmation link has been sent to your email address.
                            Kindly open the link to activate your account.
                        </p>
                    </div>
                </div>
                <DialogFooter className="flex-1 justify-center items-center gap-2">
                    <Button
                        variant="outline"
                        onClick={handleResendEmail}
                        className="w-full"
                        disabled={processing}
                    >
                        {processing ? 'Sending...' : 'Resend Email'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
