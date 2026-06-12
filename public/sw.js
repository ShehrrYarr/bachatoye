// MobileHub POS — offline service worker.
// Lets the POS screen open and run without internet, as long as it was
// loaded online at least once (so the shell + assets are cached).
//
// Strategy:
//   - POS page navigation  → network-first, fall back to cached page offline
//   - Build assets / fonts → cache-first (populated on first online load)
//   - Everything dynamic   → not intercepted (normal network; fails offline,
//                            which is what the app's offline fallbacks expect)
//
// Bump CACHE_VERSION whenever you want to discard old cached assets/pages.
const CACHE_VERSION = 'pos-v1';

self.addEventListener('install', () => {
    // Activate this worker immediately instead of waiting for old tabs to close.
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE_VERSION).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

// Should this request be served from / written to the offline cache?
function isCacheableAsset(url) {
    // Vite build output
    if (url.origin === self.location.origin && url.pathname.startsWith('/build/')) return true;
    // Cross-origin static assets we rely on (Font Awesome CSS + its font files)
    if (url.origin !== self.location.origin) {
        return /font-?awesome|cdnjs\.cloudflare\.com|\.(woff2?|ttf|eot|css)$/i.test(url.href);
    }
    return false;
}

self.addEventListener('fetch', (event) => {
    const req = event.request;

    // Only ever cache GETs. POSTs (placing/syncing orders) must hit the network.
    if (req.method !== 'GET') return;

    const url = new URL(req.url);

    // ── POS page navigation: network-first, cached shell as offline fallback ──
    if (req.mode === 'navigate' && url.origin === self.location.origin && url.pathname === '/pos') {
        event.respondWith(
            fetch(req)
                .then((res) => {
                    const copy = res.clone();
                    caches.open(CACHE_VERSION).then((c) => c.put('/pos', copy));
                    return res;
                })
                .catch(() => caches.match('/pos').then((cached) => cached || caches.match(req)))
        );
        return;
    }

    // ── Static assets: cache-first, fill cache on first successful fetch ──
    if (isCacheableAsset(url)) {
        event.respondWith(
            caches.match(req).then((cached) => {
                if (cached) return cached;
                return fetch(req).then((res) => {
                    if (res && (res.ok || res.type === 'opaque')) {
                        const copy = res.clone();
                        caches.open(CACHE_VERSION).then((c) => c.put(req, copy));
                    }
                    return res;
                }).catch(() => cached);
            })
        );
        return;
    }

    // Everything else (APIs, /pos/ping, etc.): leave to the network so the
    // app's own offline detection and fallbacks take over when it fails.
});
