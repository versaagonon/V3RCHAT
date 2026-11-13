/* ====================================================================
 * V3RCHAT Progressive Web App — Production Service Worker
 * Sovereign syndicate messaging network offline & caching engine
 * ==================================================================== */

const CACHE_VERSION = 'v3rchat-v2.1.0';
const STATIC_CACHE = `v3rchat-static-${CACHE_VERSION}`;
const RUNTIME_CACHE = `v3rchat-runtime-${CACHE_VERSION}`;
const OFFLINE_URL = '/offline.html';

// Critical app shell assets to precache
const PRECACHE_ASSETS = [
  OFFLINE_URL,
  '/manifest.json',
  '/icons/icon-192x192.png',
  '/icons/icon-512x512.png',
  '/icons/icon-maskable-192x192.png',
  '/icons/icon-maskable-512x512.png',
  '/icons/apple-touch-icon-180x180.png',
  '/vendor/chatify/logo_transparant.png',
  '/vendor/chatify/logo.png',
  '/vendor/chatify/fonts/SF-Pro-Text-Regular.woff2',
  '/vendor/chatify/fonts/SF-Pro-Text-Medium.woff2',
  '/vendor/chatify/fonts/SF-Pro-Text-Semibold.woff2',
  '/vendor/chatify/fonts/SF-Pro-Display-Bold.woff2',
];

// URLs/patterns that must NEVER be cached (private data & auth)
const NON_CACHEABLE_PATTERNS = [
  /\/api\//i,
  /\/broadcasting\//i,
  /\/sanctum\//i,
  /\/csrf-cookie/i,
  /\/login/i,
  /\/register/i,
  /\/logout/i,
  /\/password\//i,
  /\/email\//i,
  /\/profile/i,
  /\/storage\//i,
  /\/attachments\//i,
];

// Install: precache shell assets
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE).then(async (cache) => {
      // Precache critical assets gracefully (don't fail entire install if one font 404s)
      await Promise.allSettled(
        PRECACHE_ASSETS.map(async (url) => {
          try {
            const response = await fetch(url, { cache: 'no-cache' });
            if (response.ok) {
              await cache.put(url, response);
            }
          } catch (err) {
            console.warn('[V3RCHAT SW] Precache skipped for:', url, err);
          }
        })
      );
    })
  );
  self.skipWaiting();
});

// Activate: clean up outdated cache buckets and claim clients
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== STATIC_CACHE && key !== RUNTIME_CACHE) {
            return caches.delete(key);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Helper: check if URL should be ignored
function isNonCacheable(url) {
  const path = url.pathname + url.search;
  return NON_CACHEABLE_PATTERNS.some((pattern) => pattern.test(path));
}

// Fetch handler
self.addEventListener('fetch', (event) => {
  const request = event.request;

  // 1. Only handle GET requests; never intercept POST/PUT/DELETE/etc.
  if (request.method !== 'GET') {
    return;
  }

  const url = new URL(request.url);

  // 2. Ignore non-http protocols (chrome-extension, data, blob)
  if (!url.protocol.startsWith('http')) {
    return;
  }

  // 3. Security Boundary: Never cache sensitive API calls, auth routes, or private payloads
  if (isNonCacheable(url)) {
    return; // Pass through directly to network
  }

  // 4. HTML Navigation requests (e.g. /v3rchat, /dashboard, etc.)
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((networkResponse) => {
          return networkResponse;
        })
        .catch(async () => {
          // If network is down, serve offline fallback
          const cache = await caches.open(STATIC_CACHE);
          const cachedOffline = await cache.match(OFFLINE_URL);
          return cachedOffline || new Response('Offline', { status: 503, statusText: 'Service Unavailable' });
        })
    );
    return;
  }

  // 5. Static Assets (CSS, JS, Fonts, Images, Icons)
  const isStaticAsset =
    url.pathname.includes('/vendor/chatify/') ||
    url.pathname.includes('/icons/') ||
    url.pathname.endsWith('.js') ||
    url.pathname.endsWith('.css') ||
    url.pathname.endsWith('.woff2') ||
    url.pathname.endsWith('.woff') ||
    url.pathname.endsWith('.png') ||
    url.pathname.endsWith('.jpg') ||
    url.pathname.endsWith('.svg');

  if (isStaticAsset) {
    // Stale-While-Revalidate strategy for static resources
    event.respondWith(
      caches.match(request).then((cachedResponse) => {
        const fetchPromise = fetch(request)
          .then((networkResponse) => {
            if (networkResponse && networkResponse.status === 200) {
              const responseToCache = networkResponse.clone();
              caches.open(RUNTIME_CACHE).then((cache) => {
                cache.put(request, responseToCache);
              });
            }
            return networkResponse;
          })
          .catch(() => cachedResponse);

        return cachedResponse || fetchPromise;
      })
    );
    return;
  }

  // 6. Default Network-First for other static requests
  event.respondWith(
    fetch(request)
      .then((response) => {
        return response;
      })
      .catch(async () => {
        return caches.match(request);
      })
  );
});

// Message handler for client communication
self.addEventListener('message', (event) => {
  if (!event.data) return;

  if (event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }

  if (event.data.type === 'CLEAR_DATA') {
    // Purge cached assets on explicit logout
    event.waitUntil(
      caches.keys().then((keys) => {
        return Promise.all(keys.map((k) => caches.delete(k)));
      })
    );
  }
});

