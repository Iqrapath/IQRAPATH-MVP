import { Head, useForm } from '@inertiajs/react';
import { Eye, EyeOff, LoaderCircle } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

interface ResetPasswordProps {
    token: string;
    email: string;
}

type ResetPasswordForm = {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
};

export default function ResetPassword({ token, email }: ResetPasswordProps) {
    const { data, setData, post, processing, errors, reset } = useForm<Required<ResetPasswordForm>>({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

    const [showPassword, setShowPassword] = useState(false);
    const [showConfirmPassword, setShowConfirmPassword] = useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout>
            <Head title="Reset Password" />
            
            <div className="flex flex-col gap-6 w-full max-w-md mx-auto p-6">
                <div className="text-start mb-2">
                    <h1 className="text-2xl font-bold mb-1">Reset your Password</h1>
                    <p className="text-sm text-muted-foreground">Please create a new password for your account</p>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <div className="grid gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="email">Email Address</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                autoComplete="email"
                                value={data.email}
                                readOnly
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder="zakirsoft.@gmail.c"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">Password</Label>
                            <div className="relative">
                                <Input
                                    id="password"
                                    type={showPassword ? "text" : "password"}
                                    name="password"
                                    autoComplete="new-password"
                                    value={data.password}
                                    autoFocus
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="••••••••"
                                />
                                <button 
                                    type="button"
                                    className="absolute right-3 top-1/2 transform -translate-y-1/2"
                                    onClick={() => setShowPassword(!showPassword)}
                                >
                                    {showPassword ? 
                                        <EyeOff className="h-5 w-5 text-gray-500" /> : 
                                        <Eye className="h-5 w-5 text-gray-500" />
                                    }
                                </button>
                            </div>
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">Confirm Password</Label>
                            <div className="relative">
                                <Input
                                    id="password_confirmation"
                                    type={showConfirmPassword ? "text" : "password"}
                                    name="password_confirmation"
                                    autoComplete="new-password"
                                    value={data.password_confirmation}
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                    placeholder="••••••••"
                                />
                                <button 
                                    type="button"
                                    className="absolute right-3 top-1/2 transform -translate-y-1/2"
                                    onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                                >
                                    {showConfirmPassword ? 
                                        <EyeOff className="h-5 w-5 text-gray-500" /> : 
                                        <Eye className="h-5 w-5 text-gray-500" />
                                    }
                                </button>
                            </div>
                            <InputError message={errors.password_confirmation} />
                        </div>
                    </div>

                    <Button 
                        type="submit" 
                        className="w-full bg-teal-600 hover:bg-teal-700 text-white rounded-full cursor-pointer" 
                        disabled={processing}
                    >
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin mr-2" />}
                        Reset Password
                    </Button>
                </form>
            </div>
        </AuthLayout>
    );
}
