import '../css/app.css';
import './bootstrap';

import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { Toaster } from 'sonner';
import { initializeTheme } from './hooks/use-appearance';
import { configureEcho } from '@laravel/echo-react';
import { LoadingProvider } from './contexts/loading-context';
import { CurrencyProvider } from './contexts/CurrencyContext';

configureEcho({
    broadcaster: 'reverb',
});

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <CurrencyProvider>
                <LoadingProvider>
                    <App {...props} />
                    <Toaster 
                        position="top-right" 
                        richColors 
                        expand={true}
                        toastOptions={{
                            style: {
                                zIndex: 9999,
                            },
                        }}
                    />
                </LoadingProvider>
            </CurrencyProvider>
        );
    },
    progress: {
        // We'll use our custom loader instead of Inertia's default progress bar
        color: '#338078',
        showSpinner: false,
    },
});

// This will set light / dark mode on load...
// initializeTheme();
