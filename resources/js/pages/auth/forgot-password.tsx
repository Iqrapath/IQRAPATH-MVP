// Components
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm<Required<{ email: string }>>({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <AuthLayout>
            <Head title="Reset Password" />
            
            <div className="flex flex-col gap-6 w-full max-w-md mx-auto p-6">
                <div className="text-start mb-2">
                    <h1 className="text-2xl font-bold mb-1">Reset your Password</h1>
                    <p className="text-sm text-muted-foreground">Kindly enter your email address to reset your password</p>
                </div>

                {status && <div className="mb-4 text-sm font-medium text-green-600">{status}</div>}

                <form onSubmit={submit} className="space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="email">Email Address</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            autoComplete="off"
                            value={data.email}
                            autoFocus
                            onChange={(e) => setData('email', e.target.value)}
                            placeholder="zakirsoft.@gmail.c"
                        />

                        <InputError message={errors.email} />
                        <p className="text-xs text-muted-foreground mt-1">
                            Please enter the email address you used when registering your account.
                        </p>
                    </div>

                    <Button 
                        className="w-full bg-teal-600 hover:bg-teal-700 text-white rounded-full cursor-pointer" 
                        disabled={processing}
                    >
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin mr-2" />}
                        Reset Password
                    </Button>
                </form>

                <div className="space-y-3">
                    <p className="text-sm text-muted-foreground text-start">
                        Check your email for password reset link. If you don't receive an email within a few minutes:
                    </p>
                    <ul className="text-sm text-muted-foreground list-disc pl-5 space-y-1">
                        <li>Check your spam or junk folder</li>
                        <li>Verify you've entered the correct email address</li>
                        <li>Make sure you're using the email address you registered with</li>
                    </ul>
                    <div className="text-start text-sm text-muted-foreground pt-2">
                        <span>Or, return to </span>
                        <TextLink href={route('login')} className="text-teal-600">log in</TextLink>
                    </div>
                </div>
            </div>
        </AuthLayout>
    );
}
