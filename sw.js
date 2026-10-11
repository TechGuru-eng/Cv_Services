const CACHE_NAME = 'hireme-v11-favicon';
const ASSETS_TO_CACHE = [
    '/',
    '/index.html',
    '/assets/js/pwa.js',
    '/assets/js/site-language.js?v=6',
    '/assets/css/site-language.css?v=2',
    '/assets/lang/en.json?v=6',
    '/assets/css/public-refinements.css?v=1',
    '/assets/css/linkedin-landing.css?v=4',
    '/assets/js/linkedin-landing.js?v=2',
    'https://cdn.tailwindcss.com',
    // Add other local assets if any
];

self.addEventListener('install', event => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Opened cache');
                return cache.addAll(ASSETS_TO_CACHE);
            })
    );
});

self.addEventListener('fetch', event => {
    if (event.request.method !== 'GET') return;
    // Refresh page HTML so metadata and favicon updates reach returning visitors.
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(() => caches.match(event.request).then(response => {
                if (response) return response;
                return Response.error();
            }))
        );
        return;
    }
    // Network first, then cache (for dynamic content), or Cache first (for static)
    // Simple Cache First strategy for now
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                if (response) {
                    return response;
                }
                return fetch(event.request);
            })
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(self.clients.claim());
    const cacheWhitelist = [CACHE_NAME];
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheWhitelist.indexOf(cacheName) === -1) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});
