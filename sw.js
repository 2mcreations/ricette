const CACHE_NAME = 'ricettario-v1.1.27';
const STATIC_ASSETS = [
    '/',
    '/css/style.css',
    '/js/script.js',
    '/images/icon-192x192.png',
    '/images/icon-512x512.png',
    '/manifest.json',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css'
];

// Installazione: cache delle risorse statiche
self.addEventListener('install', event => {
    console.log('Service Worker: Installazione', CACHE_NAME);
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(STATIC_ASSETS).catch(error => {
                console.error('Errore durante il caching iniziale:', error);
            });
        }).then(() => {
            console.log('Service Worker: Skip waiting');
            return self.skipWaiting();
        })
    );
});

// Attivazione: pulizia cache obsolete e controllo client
self.addEventListener('activate', event => {
    console.log('Service Worker: Attivazione', CACHE_NAME);
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.filter(name => name !== CACHE_NAME).map(name => {
                    console.log('Service Worker: Eliminazione cache obsoleta:', name);
                    return caches.delete(name);
                })
            );
        }).then(() => {
            console.log('Service Worker: Claim clients');
            return self.clients.claim();
        })
    );
});

// Gestione richieste
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);
    console.log('Service Worker: Fetch', url.pathname, event.request.method);

    // Ignora richieste non-HTTP/HTTPS, non-GET, o specifiche
    if (
        !url.protocol.startsWith('http') ||
        event.request.method !== 'GET' ||
        url.pathname.includes('/admin/') ||
        url.pathname.includes('/api/') ||
        url.pathname.match(/^\/(index|login|register|add_recipe|edit_recipe|view_recipe|logout)$/i) // Ignora pagine dinamiche
    ) {
        console.log('Service Worker: Ignorata richiesta:', url.pathname);
        event.respondWith(
            fetch(event.request).catch(error => {
                console.error('Errore di rete per richiesta ignorata:', error, url.pathname);
                return new Response('Errore di rete', { status: 500 });
            })
        );
        return;
    }

    // Cache-first per risorse statiche
    event.respondWith(
        caches.match(event.request).then(response => {
            if (response) {
                console.log('Service Worker: Risposta dalla cache:', url.pathname);
                return response;
            }
            console.log('Service Worker: Fetch dalla rete:', url.pathname);
            return fetch(event.request, { redirect: 'follow' }).then(networkResponse => {
                if (!networkResponse || !networkResponse.ok || networkResponse.type === 'opaqueredirect') {
                    return networkResponse;
                }
                const responseToCache = networkResponse.clone();
                caches.open(CACHE_NAME).then(cache => {
                    cache.put(event.request, responseToCache).catch(error => {
                        console.error('Errore durante il caching:', error);
                    });
                });
                return networkResponse;
            }).catch(error => {
                console.error('Errore di rete:', error);
                return new Response('Errore di rete', { status: 500 });
            });
        })
    );
});

// Gestione messaggi per evitare errori di porta chiusa
self.addEventListener('message', event => {
    console.log('Service Worker: Messaggio ricevuto:', event.data);
    if (event.data === 'ping') {
        event.ports[0]?.postMessage('pong');
    }
});