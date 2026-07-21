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

// Deteksi lingkungan pemasangan. iOS hanya mengizinkan Web Push saat PWA sudah
// dipasang ke Home Screen — di tab Safari, PushManager memang tak ada.
const adalahStandalone = () =>
    window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

const adalahIos = () => /iPad|iPhone|iPod/.test(navigator.userAgent);

function beritahu(judul, pesan) {
    const store = window.Alpine?.store('konfirmasi');
    if (store) {
        store.beritahu({ judul, pesan });
    } else {
        alert(`${judul}\n\n${pesan}`);
    }
}

document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-push-subscribe]');
    if (!btn) return;
    e.preventDefault();

    const vapid = document.querySelector('meta[name="vapid-public-key"]')?.content;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!vapid || !('serviceWorker' in navigator) || !('PushManager' in window)) {
        // iOS di tab Safari: push-nya DIDUKUNG, tapi hanya setelah dipasang ke Home
        // Screen. Bilang "tidak didukung" di sini menyesatkan dan bikin orang menyerah.
        if (adalahIos() && !adalahStandalone()) {
            beritahu(
                'Pasang aplikasi dulu',
                'Supaya bisa menerima notifikasi, pasang NirwanaHRIS ke Home Screen. Ketuk tombol Bagikan di Safari, lalu pilih "Tambah ke Layar Utama", dan buka aplikasinya dari ikon tersebut.',
            );
        } else {
            beritahu('Notifikasi tidak didukung', 'Browser ini tidak mendukung notifikasi push.');
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

    tandaiTombolAktif(btn);
});

// Samakan label tombol dengan langganan yang sudah ada (bukan menunggu diklik).
// Tanpa ini, setelah refresh tombol kembali berbunyi "Aktifkan Notifikasi"
// seolah langganannya hilang.
function tandaiTombolAktif(btn) {
    const label = btn.querySelector('span');
    if (label) label.textContent = 'Notifikasi Aktif';
    btn.disabled = true;
    btn.classList.add('opacity-60');
}

async function segarkanTombolPush() {
    const tombol = document.querySelectorAll('[data-push-subscribe]');
    if (!tombol.length || !('serviceWorker' in navigator) || !('PushManager' in window)) return;

    const reg = await navigator.serviceWorker.ready;
    if (!(await reg.pushManager.getSubscription())) return;

    tombol.forEach(tandaiTombolAktif);
}

document.addEventListener('livewire:navigated', segarkanTombolPush);
document.addEventListener('DOMContentLoaded', segarkanTombolPush);

// Android/Chrome menawarkan pemasangan lewat event ini. iOS tak punya padanannya —
// di sana pemasangan dituntun lewat pesan pada cabang push di atas.
let promptPasang = null;

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    promptPasang = e;
    document.querySelectorAll('[data-pwa-install]').forEach((b) => b.classList.remove('hidden'));
});

document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-pwa-install]');
    if (!btn || !promptPasang) return;
    e.preventDefault();

    promptPasang.prompt();
    await promptPasang.userChoice;
    promptPasang = null;
    btn.classList.add('hidden');
});

window.addEventListener('appinstalled', () => {
    promptPasang = null;
    document.querySelectorAll('[data-pwa-install]').forEach((b) => b.classList.add('hidden'));
});
