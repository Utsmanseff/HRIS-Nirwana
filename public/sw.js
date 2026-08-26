/* Nirwana HRIS — Service Worker
   Scope: / (served from public root).
   - App-shell offline fallback for navigations.
   - Cache-first for Vite build assets (/build/*).
   - Web Push handlers (in-app notif center remains the source of truth;
     push is best-effort per docs/superpowers/specs/2026-06-22-notifikasi-design.md).
   Bump CACHE on each deploy that changes precached files. */
const CACHE = 'nirwana-v2';
const PRECACHE = ['/offline.html', '/icons/icon.svg', '/manifest.webmanifest'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((c) => c.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

// In-flight de-duplication for cache-first assets.
//
// The absen page asks for the same detector file twice within a few hundred ms:
// once from <link rel=preload>, once from tfjs itself. Both miss the cache
// (nothing is stored yet) and both hit the network — double bytes on the exact page
// where speed matters. Sharing one fetch per URL removes that regardless of whether
// the browser reuses the preload, which Safari is not reliable about.
const sedangJalan = new Map();

function ambilSekali(req) {
    const kunci = req.url;
    if (sedangJalan.has(kunci)) {
        return sedangJalan.get(kunci).then((res) => res.clone());
    }

    const janji = fetch(req).then((res) => {
        // put() is best-effort: a full storage quota must not become an unhandled
        // rejection. The in-flight entry is held until the write settles so a request
        // arriving right after this one still finds it.
        caches.open(CACHE)
            .then((c) => c.put(req, res.clone()))
            .catch(() => {})
            .finally(() => sedangJalan.delete(kunci));

        return res;
    }).catch((e) => {
        sedangJalan.delete(kunci);
        throw e;
    });

    sedangJalan.set(kunci, janji);

    return janji.then((res) => res.clone());
}

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;
    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return;

    // Cache-first for fingerprinted build assets AND the MediaPipe runtime.
    //
    // /wajah/* is the TensorFlow.js face detector runtime (~1.3 MB). The HTTP cache
    // already marks it immutable for a year, but on iOS PWAs that cache gets evicted
    // often — and every eviction means staff wait through a fresh multi-megabyte
    // download at the exact moment they are trying to clock in. Cache Storage survives
    // that. These files are versioned by hand, so cache-first never goes stale;
    // replacing them means bumping CACHE above.
    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/wajah/')) {
        // ignoreVary: the same file is requested in two different modes on the absen
        // page — once by <link rel=preload> (CORS) and once by MediaPipe — and the
        // origin sends `Vary: Accept-Encoding`. Without this, the second request misses
        // the cache and re-downloads.
        event.respondWith(
            caches.match(req, { ignoreVary: true }).then((hit) => hit || ambilSekali(req))
        );
        return;
    }

    // Network-first for navigations, fall back to offline shell.
    if (req.mode === 'navigate') {
        event.respondWith(fetch(req).catch(() => caches.match('/offline.html')));
        return;
    }
});

// ---- Web Push (best-effort) ----
self.addEventListener('push', (event) => {
    let data = {};
    try { data = event.data ? event.data.json() : {}; } catch (e) { data = { body: event.data && event.data.text() }; }
    const title = data.title || 'Nirwana HRIS';
    const options = {
        body: data.body || '',
        icon: data.icon || '/icons/icon.svg',
        badge: data.badge || '/icons/icon.svg',
        data: { url: data.url || '/' },
        tag: data.tag,
        renotify: !!data.tag,
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = (event.notification.data && event.notification.data.url) || '/';
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
            for (const c of list) {
                if ('focus' in c) { c.navigate(target); return c.focus(); }
            }
            return self.clients.openWindow(target);
        })
    );
});
