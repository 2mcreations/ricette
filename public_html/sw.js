const DEBUG = false;
const CACHE_NAME = 'ricettario-v1.1.49';
const STATIC_ASSETS = [
    '/css/style.css',
    '/js/script.js',
    '/images/icon-192x192.png',
    '/manifest.json',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css'
];

// Installazione: cache delle risorse statiche
self.addEventListener('install', event => {
    if (DEBUG) console.log('Service Worker: Installazione', CACHE_NAME);
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(STATIC_ASSETS).catch(error => {
                console.error('Errore durante il caching iniziale:', error);
            });
        }).then(() => {
            return self.skipWaiting();
        })
    );
});

// Attivazione: pulizia cache obsolete e controllo client
self.addEventListener('activate', event => {
    if (DEBUG) console.log('Service Worker: Attivazione', CACHE_NAME);
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.filter(name => name !== CACHE_NAME).map(name => {
                    if (DEBUG) console.log('Service Worker: Eliminazione cache obsoleta:', name);
                    return caches.delete(name);
                }))
        }).then(() => {
            return self.clients.claim();
        })
    );
});

self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // Ignora richieste non HTTP
    if (!url.protocol.startsWith('http')) return;

    // Escludi pagine dinamiche (.php, .html, root /, ecc.)
    if (
        url.pathname === '/' ||
        url.pathname.endsWith('.php') ||
        url.pathname.endsWith('/index') ||
        event.request.headers.get('accept')?.includes('text/html')
    ) {
        if (DEBUG) console.log('Service Worker: Ignora cache per', url.href);
        return;
    }

    // Per asset statici: cache-first strategy
    event.respondWith(
        caches.match(event.request).then(cachedResponse => {
            if (cachedResponse) return cachedResponse;

            return fetch(event.request).then(networkResponse => {
                if (networkResponse.ok) {
                    const responseClone = networkResponse.clone();
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(event.request, responseClone).catch(console.error);
                    });
                }
                return networkResponse;
            }).catch(error => {
                console.error('Errore durante il fetch da rete:', error);
                return new Response('Errore di rete', { status: 500 });
            });
        })
    );
});

// Gestione messaggi per evitare errori di porta chiusa
self.addEventListener('message', event => {
    if (DEBUG) console.log('Service Worker: Messaggio ricevuto:', event.data);
    if (event.data === 'ping') {
        event.ports[0]?.postMessage('pong');
    }
});