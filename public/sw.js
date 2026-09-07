// Qlinkon BIZNESS — Service Worker
// Static and tenant-agnostic. Tenant isolation is handled entirely via the
// `scope` option passed at registration time (see login.blade.php) — this
// file itself has no knowledge of which tenant installed it.

// Bump this on every deploy that changes cached assets. Bumping it creates a
// fresh cache and the activate handler deletes the old one, which is the only
// way to evict entries a browser has already stored.
const CACHE_VERSION = "v2";
const STATIC_CACHE = `biz-static-${CACHE_VERSION}`;
const OFFLINE_URL = "/offline.html";

// Only truly static, non-tenant assets belong here.
const PRECACHE_ASSETS = [OFFLINE_URL, "/assets/pwa/icon-512.webp"];

self.addEventListener("install", (event) => {
    event.waitUntil(caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE_ASSETS)));
    self.skipWaiting();
});

self.addEventListener("activate", (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(
                    keys
                        .filter((key) => key.startsWith("biz-static-") && key !== STATIC_CACHE)
                        .map((key) => caches.delete(key)),
                ),
            ),
    );
    self.clients.claim();
});

self.addEventListener("fetch", (event) => {
    const { request } = event;

    // Never intercept non-GET traffic (forms, API writes, etc.).
    if (request.method !== "GET") return;

    // Navigation requests (HTML, incl. the dashboard) — network-first.
    // Tenant data is dynamic; never serve it stale from cache.
    if (request.mode === "navigate") {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    const assetPath = new URL(request.url).pathname;

    // Only ever store a real success. The previous handler cached whatever came
    // back, so a 404 on a missing product image became permanent: restoring the
    // file on the server did not help, the browser kept replaying the 404.
    const cachePut = (response) => {
        if (response && response.ok && response.status === 200 && response.type !== "opaque") {
            const clone = response.clone();
            caches.open(STATIC_CACHE).then((cache) => cache.put(request, clone));
        }
        return response;
    };

    // Code — network-first. Application JS and CSS change on every deploy and
    // are requested at URLs that may not carry a version query, so cache-first
    // pins whatever copy the browser saw first and no server-side change can
    // dislodge it. The cache stays as an offline fallback only.
    if (/\.(?:js|css)$/.test(assetPath)) {
        event.respondWith(
            fetch(request)
                .then(cachePut)
                .catch(() => caches.match(request)),
        );
        return;
    }

    // Fonts and images — cache-first is safe here: these are content-addressed
    // or effectively immutable, and serving them instantly matters more.
    if (/\.(?:woff2?|png|jpg|jpeg|webp|svg|ico)$/.test(assetPath)) {
        event.respondWith(
            caches.match(request).then((cached) => cached || fetch(request).then(cachePut)),
        );
        return;
    }

    // Everything else (API calls, etc.) — network only, no caching.
});
