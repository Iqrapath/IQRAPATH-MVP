import { Head, useForm, usePage } from '@inertiajs/react';
import { Eye, EyeOff, LoaderCircle } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { useAuthLoading } from '@/hooks/use-auth-loading';

type LoginForm = {
    login: string;
    password: string;
    remember: boolean;
};

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

export default function Login({ status, canResetPassword }: LoginProps) {
    const { url } = usePage();
    
    // Extract redirect parameter from URL
    const urlParams = new URLSearchParams(url.split('?')[1] || '');
    const redirectUrl = urlParams.get('redirect');

    const { data, setData, post, processing, errors, reset } = useForm<Required<LoginForm> & { redirect?: string }>({
        login: '',
        password: '',
        remember: false,
        redirect: redirectUrl || undefined,
    });

    const [showPassword, setShowPassword] = useState(false);
    const { handleAuthAction } = useAuthLoading();

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        
        // Use our auth loading handler
        handleAuthAction(() => {
            post(route('login'), {
                onFinish: () => reset('password'),
            });
        }, 'Logging in...');
    };

    return (
        <AuthLayout>
            <Head title="Log in" />
            <div className="flex flex-col gap-6 w-full max-w-md mx-auto p-6">
                <div className="text-start mb-4">
                    <h1 className="text-2xl font-bold mb-1">Login your Account</h1>
                    {/* <p className="text-sm text-muted-foreground">Join as a Quran Teacher and connect with students worldwide.</p> */}
                </div>

                <form className="flex flex-col gap-6" onSubmit={submit}>
                    <div className="grid gap-5">
                        <div className="grid gap-2">
                            <Label htmlFor="login">Email Address or Phone Number</Label>
                            <Input
                                id="login"
                                type="text"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="email"
                                value={data.login}
                                onChange={(e) => setData('login', e.target.value)}
                                placeholder="zakirsoft@gmail.com"
                            />
                            <InputError message={errors.login} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">Password</Label>
                            <div className="relative">
                                <Input
                                    id="password"
                                    type={showPassword ? "text" : "password"}
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    value={data.password}
                                    className={errors.password ? "border-red-500" : ""}
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="********"
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
                            {errors.password && (
                                <div className="flex items-center gap-1 text-red-500 text-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-red-500">
                                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                                        <line x1="12" y1="9" x2="12" y2="13"></line>
                                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                    </svg>
                                    WRONG PASSWORD
                                </div>
                            )}
                        </div>

                        <div className="flex items-center justify-between">
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    checked={data.remember}
                                    onCheckedChange={(checked) => setData('remember', !!checked)}
                                    tabIndex={3}
                                />
                                <Label htmlFor="remember" className="text-sm">Remember me</Label>
                            </div>
                            {canResetPassword && (
                                <TextLink href={route('password.request')} className="text-sm text-red-500" tabIndex={5}>
                                    Forgot Password
                                </TextLink>
                            )}
                        </div>

                        <Button 
                            type="submit" 
                            className="w-full bg-teal-600 hover:bg-teal-700 text-white py-2 rounded-full cursor-pointer" 
                            tabIndex={4} 
                            disabled={processing}
                        >
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin mr-2" />}
                            Login your Account
                        </Button>
                    </div>
                </form>

                <div className="relative flex items-center justify-center">
                    <div className="border-t border-gray-200 w-full"></div>
                    <span className="bg-white px-2 text-sm text-gray-500 absolute">Or Login With</span>
                </div>

                <div className="grid gap-3">
                    <Button 
                        type="button" 
                        variant="outline" 
                        className="w-full flex items-center justify-center gap-2 rounded-full cursor-pointer"
                        onClick={() => window.location.href = route('auth.google')}
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" preserveAspectRatio="xMidYMid" viewBox="0 0 256 262">
                            <path fill="#4285F4" d="M255.878 133.451c0-10.734-.871-18.567-2.756-26.69H130.55v48.448h71.947c-1.45 12.04-9.283 30.172-26.69 42.356l-.244 1.622 38.755 30.023 2.685.268c24.659-22.774 38.875-56.282 38.875-96.027"></path>
                            <path fill="#34A853" d="M130.55 261.1c35.248 0 64.839-11.605 86.453-31.622l-41.196-31.913c-11.024 7.688-25.82 13.055-45.257 13.055-34.523 0-63.824-22.773-74.269-54.25l-1.531.13-40.298 31.187-.527 1.465C35.393 231.798 79.49 261.1 130.55 261.1"></path>
                            <path fill="#FBBC05" d="M56.281 156.37c-2.756-8.123-4.351-16.827-4.351-25.82 0-8.994 1.595-17.697 4.206-25.82l-.073-1.73L15.26 71.312l-1.335.635C5.077 89.644 0 109.517 0 130.55s5.077 40.905 13.925 58.602l42.356-32.782"></path>
                            <path fill="#EB4335" d="M130.55 50.479c24.514 0 41.05 10.589 50.479 19.438l36.844-35.974C195.245 12.91 165.798 0 130.55 0 79.49 0 35.393 29.301 13.925 71.947l42.211 32.783c10.59-31.477 39.891-54.251 74.414-54.251"></path>
                        </svg>
                        Continue with Google
                    </Button>
                    <Button 
                        type="button" 
                        variant="outline" 
                        className="w-full flex items-center justify-center gap-2 rounded-full cursor-pointer"
                        onClick={() => window.location.href = route('auth.facebook')}
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#1877F2" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-5.8-4.703-10.503-10.503-10.503S2.994 6.273 2.994 12.073c0 5.242 3.84 9.598 8.858 10.384v-7.345h-2.668v-3.04h2.668V9.75c0-2.633 1.568-4.085 3.966-4.085 1.15 0 2.35.205 2.35.205v2.584h-1.323c-1.304 0-1.71.81-1.71 1.64v1.97h2.912l-.465 3.04h-2.447v7.345c5.018-.786 8.858-5.142 8.858-10.384"></path>
                        </svg>
                        Continue with Facebook
                    </Button>
                </div>

                <div className="text-center text-sm text-muted-foreground space-y-3">
                    <p>Don't have an account?</p>
                    <div className="flex flex-col sm:flex-row gap-2 justify-center">
                        <TextLink 
                            href={route('register.student-guardian')} 
                            tabIndex={6} 
                            className="text-teal-600 hover:text-teal-700 font-medium"
                        >
                            Join as Student/Guardian
                        </TextLink>
                        <span className="hidden sm:inline text-gray-400">•</span>
                        <TextLink 
                            href={route('register.teacher')} 
                            tabIndex={7} 
                            className="text-teal-600 hover:text-teal-700 font-medium"
                        >
                            Become a Teacher
                        </TextLink>
                    </div>
                </div>
            </div>

            {status && <div className="mb-4 text-center text-sm font-medium text-green-600">{status}</div>}
        </AuthLayout>
    );
}
