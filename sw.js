const CACHE_NAME = 'ricettario-v1.1.24';
const STATIC_ASSETS = [
    '<?php echo BASE_PATH; ?>',
    '<?php echo BASE_PATH; ?>index',
    '<?php echo BASE_PATH; ?>css/style.css',
    '<?php echo BASE_PATH; ?>js/script.js',
    '<?php echo BASE_PATH; ?>images/icon-192x192.png',
    '<?php echo BASE_PATH; ?>manifest.json',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css'
];

// Installazione
self.addEventListener('install', event => {
    console.log('Service Worker: Installazione', CACHE_NAME);
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(STATIC_ASSETS);
        }).catch(error => {
            console.error('Errore durante il caching iniziale:', error);
        }).then(() => self.skipWaiting())
    );
});

// Attivazione
self.addEventListener('activate', event => {
    console.log('Service Worker: Attivazione', CACHE_NAME);
    event.waitUntil(
        caches.keys().then(cacheNames =>
            Promise.all(
                cacheNames.filter(name => name !== CACHE_NAME).map(name => {
                    console.log('Service Worker: Eliminazione cache obsoleta:', name);
                    return caches.delete(name);
                })
            )
        ).then(() => self.clients.claim())
    );
});

// Fetch
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);
    console.log('Service Worker: Fetch', url.pathname, event.request.method);

    if (
        !url.protocol.startsWith('http') ||
        event.request.method !== 'GET' ||
        url.pathname.includes('/login/') ||
        url.pathname.includes('/logout/')
    ) {
        console.log('Service Worker: Ignorata richiesta:', url.pathname);
        event.respondWith(
            fetch(event.request).catch(error => {
                console.error('Errore di rete per richiesta ignorata:', error);
                return new Response('Errore di rete', { status: 500 });
            })
        );
        return;
    }

    event.respondWith(
        caches.match(event.request).then(response => {
            if (response) {
                console.log('Service Worker: Risposta dalla cache:', url.pathname);
                return response;
            }
            return fetch(event.request, { redirect: 'follow' }).then(networkResponse => {
                if (!networkResponse || !networkResponse.ok || networkResponse.type === 'opaqueredirect') {
                    return networkResponse;
                }
                return caches.open(CACHE_NAME).then(cache => {
                    cache.put(event.request, networkResponse.clone()).catch(error => {
                        console.error('Errore durante il caching:', error);
                    });
                    return networkResponse;
                });
            }).catch(error => {
                console.error('Errore di rete:', error);
                return new Response('Errore di rete', { status: 500 });
            });
        })
    );
});

// Ping per debugging
self.addEventListener('message', event => {
    if (event.data === 'ping') {
        event.ports[0]?.postMessage('pong');
    }
});
