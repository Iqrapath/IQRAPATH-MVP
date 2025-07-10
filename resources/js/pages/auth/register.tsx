import { Head, useForm } from '@inertiajs/react';
import { Eye, EyeOff, LoaderCircle } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AuthLayout from '@/layouts/auth-layout';

type RegisterForm = {
    name: string;
    email: string;
    phone: string;
    password: string;
    password_confirmation: string;
    terms: boolean;
};

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm<Required<RegisterForm>>({
        name: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
        terms: false,
    });

    const [showPassword, setShowPassword] = useState(false);
    const [showConfirmPassword, setShowConfirmPassword] = useState(false);
    const [registrationType, setRegistrationType] = useState<'email' | 'phone'>('email');

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout>
            <Head title="Register" />
            <div className="flex flex-col gap-6 w-full max-w-md mx-auto p-6">
                <div className="text-start">
                    <h1 className="text-2xl font-bold mb-1">Create an Account</h1>
                    <p className="text-sm text-muted-foreground">Join as a Quran Teacher and connect with students worldwide.</p>
                </div>

                <form className="flex flex-col gap-6" onSubmit={submit}>
                    <div className="grid gap-5">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                type="text"
                                required
                                autoFocus
                                tabIndex={1}
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                disabled={processing}
                                placeholder="zakirsoft"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <Tabs 
                            defaultValue="email" 
                            className="w-full" 
                            value={registrationType}
                            onValueChange={(value) => setRegistrationType(value as 'email' | 'phone')}
                        >
                            <TabsList className="grid w-full grid-cols-2 mb-2">
                                <TabsTrigger value="email">Email</TabsTrigger>
                                <TabsTrigger value="phone">Phone</TabsTrigger>
                            </TabsList>
                            <TabsContent value="email">
                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email Address</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        required={registrationType === 'email'}
                                        tabIndex={2}
                                        autoComplete="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        disabled={processing}
                                        placeholder="zakirsoft@gmail.com"
                                    />
                                    <InputError message={errors.email} />
                                </div>
                            </TabsContent>
                            <TabsContent value="phone">
                                <div className="grid gap-2">
                                    <Label htmlFor="phone">Phone Number</Label>
                                    <Input
                                        id="phone"
                                        type="tel"
                                        required={registrationType === 'phone'}
                                        tabIndex={2}
                                        autoComplete="tel"
                                        value={data.phone}
                                        onChange={(e) => setData('phone', e.target.value)}
                                        disabled={processing}
                                        placeholder="+1234567890"
                                    />
                                    <InputError message={errors.phone} />
                                </div>
                            </TabsContent>
                        </Tabs>

                        <div className="grid gap-2">
                            <Label htmlFor="password">Password</Label>
                            <div className="relative">
                                <Input
                                    id="password"
                                    type={showPassword ? "text" : "password"}
                                    required
                                    tabIndex={3}
                                    autoComplete="new-password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    disabled={processing}
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
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">Confirm Password</Label>
                            <div className="relative">
                                <Input
                                    id="password_confirmation"
                                    type={showConfirmPassword ? "text" : "password"}
                                    required
                                    tabIndex={4}
                                    autoComplete="new-password"
                                    value={data.password_confirmation}
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                    disabled={processing}
                                    placeholder="********"
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

                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="terms"
                                checked={data.terms}
                                onCheckedChange={(checked) => setData('terms', checked as boolean)}
                            />
                            <label
                                htmlFor="terms"
                                className="text-sm leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                            >
                                By signing up, you agree to Iqtranet <TextLink href="#" className="text-red-500">Terms & Condition</TextLink>, <TextLink href="#" className="text-red-500">Privacy</TextLink> and <TextLink href="#" className="text-red-500">Policy</TextLink>.
                            </label>
                        </div>

                        <Button
                            type="submit"
                            className="w-full bg-teal-600 hover:bg-teal-700 text-white py-6 rounded-full cursor-pointer"
                            tabIndex={5}
                            disabled={processing}
                        >
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin mr-2" />}
                            Create an Account
                        </Button>
                    </div>
                </form>

                <div className="relative flex items-center justify-center">
                    <div className="border-t border-gray-200 w-full"></div>
                    <span className="bg-white px-2 text-sm text-gray-500 absolute">Or Sign Up With</span>
                </div>

                <div className="grid gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        className="w-full flex items-center justify-center rounded-full gap-2 border-gray-200 cursor-pointer"
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
                        className="w-full flex items-center justify-center rounded-full gap-2 cursor-pointer"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#1877F2" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-5.8-4.703-10.503-10.503-10.503S2.994 6.273 2.994 12.073c0 5.242 3.84 9.598 8.858 10.384v-7.345h-2.668v-3.04h2.668V9.75c0-2.633 1.568-4.085 3.966-4.085 1.15 0 2.35.205 2.35.205v2.584h-1.323c-1.304 0-1.71.81-1.71 1.64v1.97h2.912l-.465 3.04h-2.447v7.345c5.018-.786 8.858-5.142 8.858-10.384"></path>
                        </svg>
                        Continue with Facebook
                    </Button>
                </div>

                <div className="text-start text-sm text-muted-foreground">
                    Already have an account?{' '}
                    <TextLink href={route('login')} tabIndex={6} className="text-primary">
                        Login
                    </TextLink>
                </div>
            </div>
        </AuthLayout>
    );
}
