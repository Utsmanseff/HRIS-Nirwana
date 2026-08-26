// Daftar berkas runtime detektor wajah + penghangat cache-nya.
//
// Sengaja DIPISAH dari absen-wajah.js: modul itu meng-import TensorFlow.js (~480 KB
// chunk), sedangkan app shell cuma perlu menarik berkasnya ke cache. Menaruh keduanya
// di satu berkas memaksa setiap halaman ikut memikul bundel tfjs.

// Probe SIMD standar — byte yang sama dipakai tfjs-backend-wasm untuk memilih varian
// wasm-nya. Varian TIDAK boleh ditebak: perangkat tanpa SIMD (iOS < 16.4) memakai
// build nosimd, dan menghangatkan varian yang salah berarti menarik ratusan KB sia-sia
// lalu tetap mengunduh yang benar. Harus sama dengan partials/preload-wajah.blade.php.
const PROBE_SIMD = new Uint8Array([0, 97, 115, 109, 1, 0, 0, 0, 1, 5, 1, 96, 0, 1, 123, 3, 2, 1, 0, 10, 10, 1, 8, 0, 65, 0, 253, 15, 253, 98, 11]);

/** Berkas runtime detektor untuk perangkat ini. */
export function berkasWajah() {
    let simd = true;
    try { simd = WebAssembly.validate(PROBE_SIMD); } catch (e) { simd = true; }

    return [
        `/wajah/tfjs-backend-wasm${simd ? '-simd' : ''}.wasm`,
        '/wajah/blazeface-front.json',
        '/wajah/blazeface-front.bin',
    ];
}

/**
 * Tarik berkas detektor ke cache service worker selagi user ada di halaman lain,
 * supaya begitu tombol Absen ditekan berkasnya sudah ada. Dilewati saat mode hemat
 * data atau jaringan 2G: ~2,6 MB tak pantas ditarik diam-diam di jaringan seperti itu.
 */
export function hangatkanBerkasWajah() {
    const koneksi = navigator.connection;
    if (koneksi?.saveData) return;
    if (koneksi?.effectiveType && /2g$/.test(koneksi.effectiveType)) return;

    for (const url of berkasWajah()) {
        fetch(url, { cache: 'force-cache' }).catch(() => {});
    }
}
