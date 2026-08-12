import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

/*
 * Realtime — необязательный слой: без него страницы работают на обычных
 * Inertia-запросах. Поэтому Echo создаётся только когда ключ Reverb реально
 * задан; иначе window.Echo остаётся undefined, и потребители (AdminLayout,
 * Masters/Map, Orders/Show, Settings/Index) тихо деградируют через `window.Echo?`
 * вместо бесконечных попыток переподключения в консоли.
 */
const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;

if (reverbKey) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
