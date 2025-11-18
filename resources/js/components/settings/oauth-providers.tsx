import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { router } from '@inertiajs/react';
import { AlertCircle, Check, Link as LinkIcon, Unlink } from 'lucide-react';
import { type User } from '@/types';

interface OAuthProvidersProps {
    user: User;
}

export default function OAuthProviders({ user }: OAuthProvidersProps) {
    const hasPassword = true; // Assume user has password unless we add a field to track this
    const linkedProvider = user.provider;
    
    const handleLinkProvider = (provider: 'google' | 'facebook') => {
        // Redirect to OAuth provider with return URL
        window.location.href = route('auth.' + provider, { 
            role: 'any',
            return_url: window.location.pathname 
        });
    };
    
    const handleUnlinkProvider = (provider: string) => {
        if (!hasPassword) {
            alert('You cannot unlink your only authentication method. Please set a password first.');
            return;
        }
        
        if (confirm(`Are you sure you want to unlink your ${provider} account?`)) {
            router.post(route('settings.oauth.unlink'), { provider });
        }
    };
    
    return (
        <Card>
            <CardHeader>
                <CardTitle>Connected Accounts</CardTitle>
                <CardDescription>
                    Link your social media accounts for quick sign-in
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {!hasPassword && linkedProvider && (
                    <Alert>
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>
                            This is your only sign-in method. Set a password before unlinking.
                        </AlertDescription>
                    </Alert>
                )}
                
                {/* Google */}
                <div className="flex items-center justify-between p-4 border rounded-lg">
                    <div className="flex items-center space-x-4">
                        <div className="w-10 h-10 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 256 262">
                                <path fill="#4285F4" d="M255.878 133.451c0-10.734-.871-18.567-2.756-26.69H130.55v48.448h71.947c-1.45 12.04-9.283 30.172-26.69 42.356l-.244 1.622 38.755 30.023 2.685.268c24.659-22.774 38.875-56.282 38.875-96.027"></path>
                                <path fill="#34A853" d="M130.55 261.1c35.248 0 64.839-11.605 86.453-31.622l-41.196-31.913c-11.024 7.688-25.82 13.055-45.257 13.055-34.523 0-63.824-22.773-74.269-54.25l-1.531.13-40.298 31.187-.527 1.465C35.393 231.798 79.49 261.1 130.55 261.1"></path>
                                <path fill="#FBBC05" d="M56.281 156.37c-2.756-8.123-4.351-16.827-4.351-25.82 0-8.994 1.595-17.697 4.206-25.82l-.073-1.73L15.26 71.312l-1.335.635C5.077 89.644 0 109.517 0 130.55s5.077 40.905 13.925 58.602l42.356-32.782"></path>
                                <path fill="#EB4335" d="M130.55 50.479c24.514 0 41.05 10.589 50.479 19.438l36.844-35.974C195.245 12.91 165.798 0 130.55 0 79.49 0 35.393 29.301 13.925 71.947l42.211 32.783c10.59-31.477 39.891-54.251 74.414-54.251"></path>
                            </svg>
                        </div>
                        <div>
                            <p className="font-medium">Google</p>
                            <p className="text-sm text-muted-foreground">
                                {linkedProvider === 'google' ? 'Connected' : 'Not connected'}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-2">
                        {linkedProvider === 'google' ? (
                            <>
                                <Badge variant="secondary" className="flex items-center gap-1">
                                    <Check className="h-3 w-3" />
                                    Linked
                                </Badge>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => handleUnlinkProvider('google')}
                                    disabled={!hasPassword}
                                >
                                    <Unlink className="h-4 w-4 mr-1" />
                                    Unlink
                                </Button>
                            </>
                        ) : (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => handleLinkProvider('google')}
                                disabled={!!linkedProvider}
                            >
                                <LinkIcon className="h-4 w-4 mr-1" />
                                Link Account
                            </Button>
                        )}
                    </div>
                </div>
                
                {/* Facebook */}
                <div className="flex items-center justify-between p-4 border rounded-lg">
                    <div className="flex items-center space-x-4">
                        <div className="w-10 h-10 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 256 256">
                                <path fill="#1877F2" d="M256 128C256 57.308 198.692 0 128 0C57.308 0 0 57.307 0 128c0 63.888 46.808 116.843 108 126.445V165H75.5v-37H108V99.8c0-32.08 19.11-49.8 48.347-49.8C170.352 50 185 52.5 185 52.5V84h-16.14C152.958 84 148 93.867 148 103.99V128h35.5l-5.675 37H148v89.445c61.192-9.602 108-62.556 108-126.445"></path>
                            </svg>
                        </div>
                        <div>
                            <p className="font-medium">Facebook</p>
                            <p className="text-sm text-muted-foreground">
                                {linkedProvider === 'facebook' ? 'Connected' : 'Not connected'}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-2">
                        {linkedProvider === 'facebook' ? (
                            <>
                                <Badge variant="secondary" className="flex items-center gap-1">
                                    <Check className="h-3 w-3" />
                                    Linked
                                </Badge>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => handleUnlinkProvider('facebook')}
                                    disabled={!hasPassword}
                                >
                                    <Unlink className="h-4 w-4 mr-1" />
                                    Unlink
                                </Button>
                            </>
                        ) : (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => handleLinkProvider('facebook')}
                                disabled={!!linkedProvider}
                            >
                                <LinkIcon className="h-4 w-4 mr-1" />
                                Link Account
                            </Button>
                        )}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
