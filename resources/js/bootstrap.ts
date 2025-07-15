/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Add TypeScript declarations for window
declare global {
    interface Window {
        axios: typeof axios;
        Echo: any; // Using 'any' to avoid TypeScript generic issues
        Pusher: typeof Pusher;
    }
}

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Get the CSRF token from the meta tag
const token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// Make Pusher available globally
window.Pusher = Pusher;

// Simple Echo initialization with minimal configuration
try {
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
    
    // Add connection event handlers for debugging
    if (window.Echo.connector) {
        window.Echo.connector.pusher.connection.bind('connected', () => {
            console.log('✅ WebSocket connected successfully');
        });
        
        window.Echo.connector.pusher.connection.bind('connecting', () => {
            console.log('⏳ WebSocket connecting...');
        });
        
        window.Echo.connector.pusher.connection.bind('disconnected', () => {
            console.warn('❌ WebSocket disconnected');
        });
        
        window.Echo.connector.pusher.connection.bind('error', (error: any) => {
            console.error('❌ WebSocket connection error:', error);
        });
    }
    
    console.log('WebSocket connection initialized');
} catch (error) {
    console.error('Failed to initialize WebSocket connection:', error);
} 