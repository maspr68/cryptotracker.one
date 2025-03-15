const CACHE_NAME = 'crypto-dashboard-v1';
const urlsToCache = [
  '/',
  '/index.php',
  '/manifest.json',
  '/assets/images/icon-192x192.png',
  '/assets/images/icon-512x512.png',
  'https://cdn.jsdelivr.net/npm/apexcharts'
];

// Install: Core Assets cachen
self.addEventListener('install', event => {
  console.log('[Service Worker] Install');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('[Service Worker] Caching app shell');
        // addAll löst Fehler aus, falls eine URL nicht erreichbar ist – du kannst auch add() für einzelne Requests nutzen
        return cache.addAll(urlsToCache);
      })
  );
});

// Activate: Alte Caches löschen
self.addEventListener('activate', event => {
  console.log('[Service Worker] Activate');
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(name => {
          if (name !== CACHE_NAME) {
            console.log('[Service Worker] Deleting cache:', name);
            return caches.delete(name);
          }
        })
      );
    })
  );
  // Macht den SW sofort aktiv
  return self.clients.claim();
});

// Fetch: Cache-first Strategie mit Netzwerkfallback und Offline-Seite
self.addEventListener('fetch', event => {
  // Nur GET-Anfragen verarbeiten
  if (event.request.method !== 'GET') return;
  
  // API-Anfragen werden nicht gecacht – direkt ans Netzwerk weiterleiten
  if (event.request.url.includes('/api/')) {
    return event.respondWith(fetch(event.request));
  }
  
  event.respondWith(
    caches.match(event.request)
      .then(cachedResponse => {
        // Falls vorhanden, liefere die gecachte Antwort
        if (cachedResponse) {
          return cachedResponse;
        }
        // Andernfalls: Hole vom Netzwerk
        return fetch(event.request)
          .then(networkResponse => {
            // Wenn Response ungültig, direkt zurückgeben
            if (!networkResponse || networkResponse.status !== 200 || networkResponse.type !== 'basic') {
              return networkResponse;
            }
            // Antwort klonen, damit sie in den Cache geschrieben werden kann
            const responseToCache = networkResponse.clone();
            caches.open(CACHE_NAME)
              .then(cache => {
                cache.put(event.request, responseToCache);
              });
            return networkResponse;
          })
          .catch(() => {
            // Falls Netzwerk fehlschlägt – offline fallback (nur für HTML-Seiten)
            if (event.request.headers.get('accept') &&
                event.request.headers.get('accept').includes('text/html')) {
              return caches.match('offline.html');
            }
          });
      })
  );
});
