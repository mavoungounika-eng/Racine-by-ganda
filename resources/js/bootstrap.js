import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// ── Laravel Echo — Reverb WebSocket (stock sync temps réel) ───────────────
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

if (typeof window !== 'undefined') {
    const key    = import.meta.env.VITE_REVERB_APP_KEY;
    const host   = import.meta.env.VITE_REVERB_HOST   ?? 'localhost';
    const port   = parseInt(import.meta.env.VITE_REVERB_PORT ?? '8080');
    const scheme = import.meta.env.VITE_REVERB_SCHEME  ?? 'http';

    if (key) {
        window.Echo = new Echo({
            broadcaster      : 'reverb',
            key,
            wsHost           : host,
            wsPort           : port,
            wssPort          : port,
            forceTLS         : scheme === 'https',
            enabledTransports: ['ws', 'wss'],
            authEndpoint     : '/broadcasting/auth',
            auth             : { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
        });
    }
}
