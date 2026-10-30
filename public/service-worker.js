const CACHE_VERSION = 'howsey-pwa-v4';
const STATIC_CACHE = `static-${CACHE_VERSION}`;
const APP_SCOPE_URL = new URL(self.registration.scope);
const APP_BASE_PATH = APP_SCOPE_URL.pathname.replace(/\/$/, '');

function scopedPath(path) {
  const normalizedPath = path.startsWith('/') ? path : `/${path}`;
  return `${APP_BASE_PATH}${normalizedPath}`;
}

const HOME_URL = scopedPath('/');
const OFFLINE_FALLBACK_URL = scopedPath('/offline.html');
const PUSH_LATEST_URL = scopedPath('/push-subscriptions/latest');
const LOGIN_URL = scopedPath('/login');
const LOGOUT_URL = scopedPath('/logout');
const REGISTER_URL = scopedPath('/register');
const PASSWORD_URL = scopedPath('/password');
const BOT_URL = scopedPath('/bot');
const PWA_ICON_URL = scopedPath('/pwa-icon.png');
const DASHBOARD_URL = scopedPath('/dashboard');

const STATIC_ASSETS = [
  HOME_URL,
  OFFLINE_FALLBACK_URL,
  PWA_ICON_URL
];

function isSameOrigin(url) {
  return url.origin === self.location.origin;
}

function shouldHandleNavigation(url) {
  if (!isSameOrigin(url)) {
    return false;
  }

  return !(
    url.pathname.startsWith(LOGIN_URL) ||
    url.pathname.startsWith(LOGOUT_URL) ||
    url.pathname.startsWith(REGISTER_URL) ||
    url.pathname.startsWith(PASSWORD_URL) ||
    url.pathname.startsWith(BOT_URL)
  );
}

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

  // Never intercept file downloads (receipts, exports). Handling them here
  // makes mobile browsers drop the download silently, so let the browser
  // fetch them natively instead.
  if (url.searchParams.has('download')) {
    return;
  }

  if (request.mode === 'navigate' && shouldHandleNavigation(url)) {
    event.respondWith(
      fetch(request)
        .then(response => {
          if (
            response.ok &&
            !response.redirected &&
            (response.headers.get('content-type') || '').includes('text/html')
          ) {
            const copy = response.clone();
            caches.open(STATIC_CACHE).then(cache => cache.put(request, copy));
          }

          return response;
        })
        .catch(async () => {
          const cached = await caches.match(request);
          if (cached) {
            return cached;
          }

          return caches.match(OFFLINE_FALLBACK_URL);
        })
    );
    return;
  }

  if (request.destination === 'style' ||
      request.destination === 'script') {
    event.respondWith(
      fetch(request).then(response => {
        if (response.ok) {
          const copy = response.clone();
          caches.open(STATIC_CACHE).then(cache => cache.put(request, copy));
        }
        return response;
      }).catch(() => caches.match(request))
    );
    return;
  }

  if (request.destination === 'image' ||
      request.destination === 'font') {
    event.respondWith(
      caches.match(request).then(cached => {
        if (cached) {
          return cached;
        }
        return fetch(request).then(response => {
          if (response.ok) {
            const copy = response.clone();
            caches.open(STATIC_CACHE).then(cache => cache.put(request, copy));
          }
          return response;
        }).catch(() => cached);
      })
    );
  }
});

self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

self.addEventListener('sync', event => {
  if (event.tag === 'sync-coins') {
    event.waitUntil(
      fetch(scopedPath('/coins'), {
        method: 'GET',
        credentials: 'include',
        headers: { 'Accept': 'application/json' }
      }).catch(() => {})
    );
  }
});

async function buildPushNotificationPayload(event) {
  if (event.data) {
    try {
      return await event.data.json();
    } catch (_) {
      try {
        return {
          title: 'HOWSEY Update',
          message: event.data.text(),
          open_url: DASHBOARD_URL
        };
      } catch (__unused) {
        // Fall through to server-backed payload lookup.
      }
    }
  }

  try {
    const response = await fetch(PUSH_LATEST_URL, {
      method: 'GET',
      credentials: 'include',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    if (response.ok) {
      return response.json();
    }
  } catch (_) {
    // Ignore and return fallback payload below.
  }

  return {
    title: 'HOWSEY Update',
    message: 'You have a new notification in HOWSEY.',
    open_url: DASHBOARD_URL
  };
}

self.addEventListener('push', event => {
  event.waitUntil((async () => {
    const payload = await buildPushNotificationPayload(event);
    const title = payload.title || 'HOWSEY Update';

    await self.registration.showNotification(title, {
      body: payload.message || 'You have a new notification in HOWSEY.',
      icon: PWA_ICON_URL,
      badge: PWA_ICON_URL,
      tag: payload.tag || 'howsey-push-notification',
      renotify: true,
      data: {
        url: payload.open_url || DASHBOARD_URL
      }
    });
  })());
});

self.addEventListener('notificationclick', event => {
  event.notification.close();

  event.waitUntil((async () => {
    const requestedUrl = new URL(event.notification.data?.url || DASHBOARD_URL, self.location.origin).href;
    const windowClients = await clients.matchAll({ type: 'window', includeUncontrolled: true });

    for (const client of windowClients) {
      if ('focus' in client) {
        if (client.url === requestedUrl && 'navigate' in client) {
          await client.navigate(requestedUrl);
        }

        await client.focus();

        if ('navigate' in client && client.url !== requestedUrl) {
          await client.navigate(requestedUrl);
        }

        return;
      }
    }

    if (clients.openWindow) {
      await clients.openWindow(requestedUrl);
    }
  })());
});
