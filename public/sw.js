/* Nirwana HRIS — Service Worker
   Scope: / (served from public root).
   - App-shell offline fallback for navigations.
   - Cache-first for Vite build assets (/build/*).
   - Web Push handlers (in-app notif center remains the source of truth;
     push is best-effort per docs/superpowers/specs/2026-06-22-notifikasi-design.md).
   Bump CACHE on each deploy that changes precached files. */
const CACHE = 'nirwana-v1';
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

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;
    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return;

    // Cache-first for fingerprinted build assets.
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.match(req).then((hit) => hit || fetch(req).then((res) => {
                const copy = res.clone();
                caches.open(CACHE).then((c) => c.put(req, copy));
                return res;
            }))
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
