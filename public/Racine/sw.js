/**
 * SERVICE WORKER - RACINE BY GANDA
 * Cache les ressources pour améliorer les performances
 */

const CACHE_NAME = 'racine-ganda-v1.0.0';
const STATIC_CACHE = 'racine-static-v1';
const DYNAMIC_CACHE = 'racine-dynamic-v1';

// Ressources à mettre en cache
const STATIC_ASSETS = [
    '/',
    '/index.html',
    '/css/style.css',
    '/css/perfectionnement.css',
    '/css/testimony-enhancement.css',
    '/js/main.js',
    '/js/perfectionnement.js',
    '/images/logo.webp',
    '/images/favicon.png',
    'https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800&display=swap',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'
];

// Ressources dynamiques (images, etc.)
const DYNAMIC_ASSETS = [
    '/images/',
    '/css/',
    '/js/'
];

// Installation du service worker
self.addEventListener('install', event => {
    console.log('[SW] Installation...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(cache => {
                console.log('[SW] Cache des ressources statiques...');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => {
                console.log('[SW] Installation terminée');
                return self.skipWaiting();
            })
            .catch(error => {
                console.error('[SW] Erreur d\'installation:', error);
            })
    );
});

// Activation du service worker
self.addEventListener('activate', event => {
    console.log('[SW] Activation...');
    
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (cacheName !== STATIC_CACHE && cacheName !== DYNAMIC_CACHE) {
                            console.log('[SW] Suppression de l\'ancien cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('[SW] Activation terminée');
                return self.clients.claim();
            })
    );
});

// Interception des requêtes
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);
    
    // Ignorer les requêtes non-GET
    if (request.method !== 'GET') {
        return;
    }
    
    // Ignorer les requêtes externes (sauf Google Fonts et CDN)
    if (url.origin !== location.origin && 
        !url.host.includes('fonts.googleapis.com') &&
        !url.host.includes('cdnjs.cloudflare.com')) {
        return;
    }
    
    event.respondWith(
        handleRequest(request)
    );
});

// Gestionnaire de requêtes
async function handleRequest(request) {
    const url = new URL(request.url);
    
    try {
        // Stratégie Cache First pour les ressources statiques
        if (isStaticAsset(request.url)) {
            return await cacheFirst(request);
        }
        
        // Stratégie Network First pour les pages HTML
        if (isHTMLRequest(request)) {
            return await networkFirst(request);
        }
        
        // Stratégie Stale While Revalidate pour les autres ressources
        return await staleWhileRevalidate(request);
        
    } catch (error) {
        console.error('[SW] Erreur de requête:', error);
        
        // Fallback pour les pages HTML
        if (isHTMLRequest(request)) {
            const cachedResponse = await caches.match('/index.html');
            if (cachedResponse) {
                return cachedResponse;
            }
        }
        
        // Retourner une réponse d'erreur
        return new Response('Contenu non disponible hors ligne', {
            status: 503,
            statusText: 'Service Unavailable',
            headers: { 'Content-Type': 'text/plain' }
        });
    }
}

// Stratégie Cache First
async function cacheFirst(request) {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
        return cachedResponse;
    }
    
    const networkResponse = await fetch(request);
    const cache = await caches.open(STATIC_CACHE);
    cache.put(request, networkResponse.clone());
    
    return networkResponse;
}

// Stratégie Network First
async function networkFirst(request) {
    try {
        const networkResponse = await fetch(request);
        const cache = await caches.open(DYNAMIC_CACHE);
        cache.put(request, networkResponse.clone());
        return networkResponse;
    } catch (error) {
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        throw error;
    }
}

// Stratégie Stale While Revalidate
async function staleWhileRevalidate(request) {
    const cache = await caches.open(DYNAMIC_CACHE);
    const cachedResponse = await cache.match(request);
    
    const fetchPromise = fetch(request).then(networkResponse => {
        cache.put(request, networkResponse.clone());
        return networkResponse;
    }).catch(() => {
        // En cas d'erreur réseau, retourner le cache si disponible
        return cachedResponse;
    });
    
    return cachedResponse || fetchPromise;
}

// Utilitaires
function isStaticAsset(url) {
    return url.includes('.css') || 
           url.includes('.js') || 
           url.includes('.woff') || 
           url.includes('.woff2') ||
           url.includes('fonts.googleapis.com') ||
           url.includes('cdnjs.cloudflare.com');
}

function isHTMLRequest(request) {
    return request.headers.get('accept')?.includes('text/html');
}

// Nettoyage périodique du cache
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'CLEAN_CACHE') {
        cleanOldCache();
    }
});

async function cleanOldCache() {
    const cache = await caches.open(DYNAMIC_CACHE);
    const requests = await cache.keys();
    
    // Supprimer les entrées de plus de 7 jours
    const oneWeekAgo = Date.now() - (7 * 24 * 60 * 60 * 1000);
    
    for (const request of requests) {
        const response = await cache.match(request);
        if (response) {
            const dateHeader = response.headers.get('date');
            if (dateHeader) {
                const responseDate = new Date(dateHeader).getTime();
                if (responseDate < oneWeekAgo) {
                    await cache.delete(request);
                    console.log('[SW] Cache expiré supprimé:', request.url);
                }
            }
        }
    }
}

console.log('[SW] Service Worker chargé');
