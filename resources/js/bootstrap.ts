/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';

// Add axios to the window object
declare global {
  interface Window {
    axios: typeof axios;
    Echo: any;
    Pusher: any;
  }
}

// Get the current origin (protocol, hostname, and port)
const currentOrigin = window.location.origin;

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.baseURL = currentOrigin;

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Make Pusher available globally
window.Pusher = Pusher;

// Get hostname and port from current URL
const hostname = window.location.hostname;
const isSecure = window.location.protocol === 'https:';

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST || hostname,
    wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT || 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME || (isSecure ? 'https' : 'http')) === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
    cluster: 'mt1',
    authEndpoint: `${currentOrigin}/broadcasting/auth`
}); 