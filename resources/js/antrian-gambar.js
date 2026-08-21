// Pemuat gambar terantre untuk thumbnail yang dilayani PHP (foto absensi).
//
// Satu layar laporan bisa memuat 40+ thumbnail sekaligus, dan tiap thumbnail adalah
// permintaan ber-autentikasi yang mem-boot Laravel. Di hosting bersama, ledakan
// permintaan serentak itu menabrak batas proses/worker: sebagian gambar balas error
// dan tampil sebagai ikon rusak, padahal berkasnya ada — dibuka satu-satu tetap muncul.
//
// Solusinya dua lapis: muat hanya saat mendekati layar, dan batasi berapa yang boleh
// jalan bersamaan. Kegagalan dicoba ulang beberapa kali dengan jeda menaik, karena
// penolakan akibat batas worker sifatnya sementara.

const BATAS_SERENTAK = 3;
const MAKS_PERCOBAAN = 3;

const antre = [];
let berjalan = 0;

function jalankan() {
    while (berjalan < BATAS_SERENTAK && antre.length > 0) {
        muat(antre.shift());
    }
}

function muat(img) {
    berjalan++;

    const selesai = () => {
        berjalan--;
        jalankan();
    };

    img.onload = () => {
        img.removeAttribute('data-src');
        delete img.dataset.gagal;
        selesai();
    };

    img.onerror = () => {
        const percobaan = Number(img.dataset.percobaan || 0) + 1;
        img.dataset.percobaan = String(percobaan);
        berjalan--;

        if (percobaan < MAKS_PERCOBAAN) {
            setTimeout(() => { antre.push(img); jalankan(); }, 400 * percobaan);
        } else {
            img.dataset.gagal = '1';
            jalankan();
        }
    };

    img.src = img.dataset.src;
}

const tertunda = new Set();

function antrekan(img) {
    if (! tertunda.delete(img)) {
        return;   // sudah diantre lewat jalur lain
    }
    pengamatLayar?.unobserve(img);
    antre.push(img);
}

const pengamatLayar = 'IntersectionObserver' in window
    ? new IntersectionObserver((entri) => {
        entri.filter((e) => e.isIntersecting).forEach((e) => antrekan(e.target));
        jalankan();
    }, { rootMargin: '300px' })
    : null;

/**
 * Daftarkan semua <img data-src> yang belum tercatat di dalam akar tertentu.
 *
 * IntersectionObserver dipakai sekadar untuk mendahulukan yang terlihat, TIDAK sebagai
 * syarat. Ada lingkungan yang tak pernah memicu callback-nya (tab latar, halaman yang
 * tak digambar) — kalau pemuatan digantungkan padanya, thumbnail bisa tak pernah muncul
 * sama sekali. Karena itu sisanya tetap dilepas setelah jeda pendek.
 */
export function antreGambar(akar = document) {
    const baru = [...akar.querySelectorAll('img[data-src]:not([data-antre])')];

    baru.forEach((img) => {
        img.dataset.antre = '1';
        tertunda.add(img);
        pengamatLayar?.observe(img);
    });

    if (! pengamatLayar) {
        baru.forEach(antrekan);
    }

    jalankan();
    setTimeout(() => { baru.forEach(antrekan); jalankan(); }, 1200);
}

document.addEventListener('DOMContentLoaded', () => antreGambar());
document.addEventListener('livewire:navigated', () => antreGambar());

// Livewire mengganti potongan DOM saat filter berubah, jadi gambar baru muncul tanpa
// event navigasi. Amati perubahan DOM supaya barisan hasil filter ikut terantre.
new MutationObserver((mutasi) => {
    const adaTambahan = mutasi.some((m) => m.addedNodes.length > 0);
    if (adaTambahan) {
        antreGambar();
    }
}).observe(document.documentElement, { childList: true, subtree: true });
