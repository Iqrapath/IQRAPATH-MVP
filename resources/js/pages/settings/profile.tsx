import { type BreadcrumbItem, type SharedData } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useState, useEffect } from 'react';

import DeleteUser from '@/components/delete-user';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import OAuthProviders from '@/components/settings/oauth-providers';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/hooks/use-initials';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { Camera, X, CheckCircle, XCircle } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { UserStatus } from '@/components/user-status';
import { type StatusType } from '@/types';
import { toast } from 'sonner';
import { Alert, AlertDescription } from '@/components/ui/alert';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: '/settings/profile',
    },
];

type ProfileForm = {
    name: string;
    email: string;
    phone: string | null;
    location: string | null;
    avatar: File | null | string;
    status_type: StatusType;
    status_message: string | null;
};

export default function Profile({ mustVerifyEmail, status }: { mustVerifyEmail: boolean; status?: string }) {
    const { auth, flash } = usePage<SharedData>().props;
    const getInitials = useInitials();
    const [avatarPreview, setAvatarPreview] = useState<string | null>(auth.user.avatar || null);
    const [showFlashAlert, setShowFlashAlert] = useState(false);

    // Handle flash messages for OAuth operations
    useEffect(() => {
        // Check for OAuth messages in query parameters
        const params = new URLSearchParams(window.location.search);
        const oauthSuccess = params.get('oauth_success');
        const oauthError = params.get('oauth_error');
        
        if (oauthSuccess || oauthError || flash?.success || flash?.error) {
            setShowFlashAlert(true);
            
            const message = oauthError || flash?.error || oauthSuccess || flash?.success;
            const isError = !!(oauthError || flash?.error);
            
            // Delay toast to ensure Toaster is mounted
            const toastTimer = setTimeout(() => {
                if (isError) {
                    toast.error(message, { 
                        duration: 5000,
                        position: 'top-center',
                    });
                } else {
                    toast.success(message, { 
                        duration: 5000,
                        position: 'top-center',
                    });
                }
            }, 100);
            
            // Clean up URL if OAuth params exist
            if (oauthSuccess || oauthError) {
                window.history.replaceState({}, '', window.location.pathname);
            }
            
            // Auto-hide alert banner after 10 seconds
            const hideTimer = setTimeout(() => setShowFlashAlert(false), 5000);
            
            return () => {
                clearTimeout(toastTimer);
                clearTimeout(hideTimer);
            };
        }
    }, [flash]);

    const { data, setData, post, errors, processing, recentlySuccessful } = useForm<ProfileForm>({
        name: auth.user.name,
        email: auth.user.email || '',
        phone: auth.user.phone || '',
        location: auth.user.location || '',
        avatar: auth.user.avatar || null,
        status_type: (auth.user.status_type as StatusType) || 'online',
        status_message: auth.user.status_message || '',
    });

    const handleAvatarChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setData('avatar', file);
            const reader = new FileReader();
            reader.onload = (e) => {
                setAvatarPreview(e.target?.result as string);
            };
            reader.readAsDataURL(file);
        }
    };

    const removeAvatar = () => {
        setData('avatar', null);
        setAvatarPreview(null);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('profile.update'), {
            preserveScroll: true,
            forceFormData: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profile settings" />

            <SettingsLayout>
                <div className="space-y-6">
                    {showFlashAlert && (
                        <>
                            {(() => {
                                const params = new URLSearchParams(window.location.search);
                                const oauthError = params.get('oauth_error');
                                const oauthSuccess = params.get('oauth_success');
                                const errorMsg = oauthError || flash?.error;
                                const successMsg = oauthSuccess || flash?.success;
                                
                                if (errorMsg) {
                                    return (
                                        <Alert variant="destructive" className="mb-4">
                                            <XCircle className="h-4 w-4" />
                                            <AlertDescription>{errorMsg}</AlertDescription>
                                        </Alert>
                                    );
                                }
                                
                                if (successMsg) {
                                    return (
                                        <Alert className="mb-4 border-green-500 bg-green-50 text-green-900">
                                            <CheckCircle className="h-4 w-4 text-green-600" />
                                            <AlertDescription>{successMsg}</AlertDescription>
                                        </Alert>
                                    );
                                }
                                
                                return null;
                            })()}
                        </>
                    )}
                    
                    <HeadingSmall title="Profile information" description="Update your account information" />

                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-4">
                            <Label>Profile Picture</Label>
                            <div className="flex items-center gap-4">
                                <div className="relative">
                                    <Avatar className="h-24 w-24 overflow-hidden rounded-full">
                                        {avatarPreview ? (
                                            <AvatarImage src={avatarPreview} alt={data.name} className="object-cover" />
                                        ) : (
                                            <AvatarFallback className="text-xl">
                                                {getInitials(data.name)}
                                            </AvatarFallback>
                                        )}
                                    </Avatar>
                                    <div className="absolute bottom-0 right-0 flex gap-1">
                                        <label htmlFor="avatar-upload" className="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-primary text-white shadow-md hover:bg-primary/90">
                                            <Camera size={16} />
                                            <input
                                                id="avatar-upload"
                                                type="file"
                                                className="hidden"
                                                onChange={handleAvatarChange}
                                                accept="image/*"
                                            />
                                        </label>
                                        {avatarPreview && (
                                            <button
                                                type="button"
                                                onClick={removeAvatar}
                                                className="flex h-8 w-8 items-center justify-center rounded-full bg-destructive text-white shadow-md hover:bg-destructive/90"
                                            >
                                                <X size={16} />
                                            </button>
                                        )}
                                    </div>
                                </div>
                                <div className="text-sm text-muted-foreground">
                                    <p>Upload a profile picture (recommended size: 256x256px)</p>
                                    <p>JPG, PNG or GIF, max 2MB</p>
                                </div>
                            </div>
                            <InputError className="mt-2" message={errors.avatar} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                className="mt-1 block w-full"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                                autoComplete="name"
                                placeholder="Full name"
                            />
                            <InputError className="mt-2" message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email address</Label>
                            <Input
                                id="email"
                                type="email"
                                className="mt-1 block w-full"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                autoComplete="username"
                                placeholder="Email address"
                            />
                            <InputError className="mt-2" message={errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="phone">Phone Number</Label>
                            <Input
                                id="phone"
                                type="tel"
                                className="mt-1 block w-full"
                                value={data.phone || ''}
                                onChange={(e) => setData('phone', e.target.value)}
                                autoComplete="tel"
                                placeholder="Phone number"
                            />
                            <InputError className="mt-2" message={errors.phone} />
                            <p className="text-xs text-muted-foreground">Either email or phone number is required.</p>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="location">Location</Label>
                            <Input
                                id="location"
                                className="mt-1 block w-full"
                                value={data.location || ''}
                                onChange={(e) => setData('location', e.target.value)}
                                placeholder="City, Country"
                            />
                            <InputError className="mt-2" message={errors.location} />
                        </div>

                        <div className="grid gap-6 pt-4">
                            <HeadingSmall title="Status settings" description="Set your availability and status message" />
                            
                            <div className="grid gap-2">
                                <Label htmlFor="status_type">Availability</Label>
                                <div className="flex items-center gap-3">
                                    <Select
                                        value={data.status_type}
                                        onValueChange={(value) => setData('status_type', value as StatusType)}
                                    >
                                        <SelectTrigger className="w-[180px]">
                                            <SelectValue placeholder="Select status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="online">
                                                <div className="flex items-center">
                                                    <UserStatus status="online" className="mr-2" showLabel={true} />
                                                </div>
                                            </SelectItem>
                                            <SelectItem value="busy">
                                                <div className="flex items-center">
                                                    <UserStatus status="busy" className="mr-2" showLabel={true} />
                                                </div>
                                            </SelectItem>
                                            <SelectItem value="away">
                                                <div className="flex items-center">
                                                    <UserStatus status="away" className="mr-2" showLabel={true} />
                                                </div>
                                            </SelectItem>
                                            <SelectItem value="offline">
                                                <div className="flex items-center">
                                                    <UserStatus status="offline" className="mr-2" showLabel={true} />
                                                </div>
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <UserStatus status={data.status_type} showLabel={true} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="status_message">Status message</Label>
                                <Input
                                    id="status_message"
                                    className="mt-1 block w-full"
                                    value={data.status_message || ''}
                                    onChange={(e) => setData('status_message', e.target.value)}
                                    placeholder="What's on your mind?"
                                    maxLength={255}
                                />
                                <p className="text-xs text-muted-foreground">
                                    {(data.status_message?.length || 0)}/255 characters
                                </p>
                            </div>
                        </div>

                        {mustVerifyEmail && auth.user.email_verified_at === null && (
                            <div>
                                <p className="-mt-4 text-sm text-muted-foreground">
                                    Your email address is unverified.{' '}
                                    <Link
                                        href={route('verification.send')}
                                        method="post"
                                        as="button"
                                        className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                    >
                                        Click here to resend the verification email.
                                    </Link>
                                </p>

                                {status === 'verification-link-sent' && (
                                    <div className="mt-2 text-sm font-medium text-green-600">
                                        A new verification link has been sent to your email address.
                                    </div>
                                )}
                            </div>
                        )}

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>Save</Button>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm text-neutral-600">Saved</p>
                            </Transition>
                        </div>
                    </form>
                </div>

                <OAuthProviders user={auth.user} />

                <DeleteUser />
            </SettingsLayout>
        </AppLayout>
    );
}
