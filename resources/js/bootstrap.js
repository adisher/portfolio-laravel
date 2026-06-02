import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Laravel Echo - Real-time broadcasting for live sports scores.
 * Uncomment the following lines and install dependencies to enable:
 *   npm install --save laravel-echo pusher-js
 *
 * Then set VITE_PUSHER_APP_KEY and VITE_PUSHER_APP_CLUSTER in your .env file.
 */

if (import.meta.env.VITE_PUSHER_APP_KEY) {
    import('pusher-js').then((Pusher) => {
        window.Pusher = Pusher.default;
        import('laravel-echo').then(({ default: Echo }) => {
            window.Echo = new Echo({
                broadcaster: 'pusher',
                key: import.meta.env.VITE_PUSHER_APP_KEY,
                cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
                forceTLS: true,
            });
        });
    }).catch(() => {
        // Pusher not installed - live scores will use polling fallback
    });
}
