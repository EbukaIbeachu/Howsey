const CACHE_VERSION = 'howsey-pwa-v2-mobile-brand-fix';
const STATIC_CACHE = `static-${CACHE_VERSION}`;

const STATIC_ASSETS = [
  '/'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => cache.addAll(STATIC_ASSETS))
      .catch(() => {})
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(
        keys
          .filter(key => key.startsWith('static-') && key !== STATIC_CACHE)
          .map(key => caches.delete(key))
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', event => {
  const request = event.request;

  if (request.method !== 'GET') {
    return;
  }

  const url = new URL(request.url);

  if (url.pathname.startsWith('/login') ||
      url.pathname.startsWith('/logout') ||
      url.pathname.startsWith('/coins') ||
      url.pathname.startsWith('/bot') ||
      url.pathname.startsWith('/users') ||
      url.pathname.startsWith('/roles') ||
      url.pathname.startsWith('/tenants') ||
      url.pathname.startsWith('/payments') ||
      url.pathname.startsWith('/maintenance') ||
      url.pathname.startsWith('/rents') ||
      url.pathname.startsWith('/buildings')) {
    return;
  }

  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request).catch(() => caches.match('/'))
    );
    return;
  }

  if (request.destination === 'style' ||
      request.destination === 'script' ||
      request.destination === 'image' ||
      request.destination === 'font') {
    event.respondWith(
      caches.match(request).then(cached => {
        if (cached) {
          return cached;
        }
        return fetch(request).then(response => {
          const copy = response.clone();
          caches.open(STATIC_CACHE).then(cache => cache.put(request, copy));
          return response;
        }).catch(() => cached);
      })
    );
  }
});

self.addEventListener('sync', event => {
  if (event.tag === 'sync-coins') {
    event.waitUntil(
      fetch('/coins', {
        method: 'GET',
        credentials: 'include',
        headers: { 'Accept': 'application/json' }
      }).catch(() => {})
    );
  }
});

