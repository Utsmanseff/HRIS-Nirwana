// Nirwana HRIS — app entry.

import './absen.js';
import './absen-pengaturan.js';
import './konfirmasi.js';

// Register the service worker (PWA shell + web push).
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch((err) => {
            console.warn('SW registration failed:', err);
        });
    });
}

// Web Push subscribe — tombol [data-push-subscribe] (di dropdown notifikasi / profil).
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);
    return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)));
}

document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-push-subscribe]');
    if (!btn) return;
    e.preventDefault();

    const vapid = document.querySelector('meta[name="vapid-public-key"]')?.content;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!vapid || !('serviceWorker' in navigator) || !('PushManager' in window)) {
        const store = window.Alpine?.store('konfirmasi');
        if (store) {
            store.beritahu({ judul: 'Notifikasi tidak didukung', pesan: 'Browser ini tidak mendukung notifikasi push.' });
        } else {
            alert('Browser tidak mendukung notifikasi push.');
        }
        return;
    }
    if ((await Notification.requestPermission()) !== 'granted') return;

    const reg = await navigator.serviceWorker.ready;
    const sub = await reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapid),
    });

    await fetch('/push/subscribe', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify(sub.toJSON()),
    });

    const label = btn.querySelector('span');
    if (label) label.textContent = 'Notifikasi Aktif';
});
