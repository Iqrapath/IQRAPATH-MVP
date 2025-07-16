import './bootstrap.ts';
import '../css/app.css';

import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { Toaster } from 'sonner';
import { initializeTheme } from './hooks/use-appearance';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Import Echo test script for debugging
// import './test-echo';

// Ensure Echo is properly initialized from bootstrap.ts
if (!window.Echo) {
    console.warn('Laravel Echo is not initialized. Attempting to initialize it now...');
    
    // Try to initialize Echo if it's not already initialized
    try {
        const token = document.head.querySelector('meta[name="csrf-token"]');
        const csrfToken = token ? token.getAttribute('content') || '' : '';
        
        // Debug info
        console.log('Initializing WebSocket connection with:');
        console.log('- Host:', window.location.hostname);
        console.log('- Port: 8080');
        console.log('- CSRF Token available:', !!csrfToken);
        
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: 'diawgqsegr5sajcpkowf', // From .env REVERB_APP_KEY
            wsHost: window.location.hostname,
            wsPort: 8080,
            forceTLS: false,
            enabledTransports: ['ws'],
            disableStats: true,
            authEndpoint: '/api/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }
        });
        
        console.log('Echo initialized successfully in app.tsx');
    } catch (error) {
        console.error('Failed to initialize Echo in app.tsx:', error);
    }
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <>
                <App {...props} />
                <Toaster position="top-right" richColors />
            </>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
// initializeTheme();
