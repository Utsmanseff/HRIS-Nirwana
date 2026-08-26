// Daftar berkas runtime detektor wajah + penghangat cache-nya.
//
// Sengaja DIPISAH dari absen-wajah.js: modul itu meng-import @mediapipe/tasks-vision
// (~137 KB), sedangkan app shell cuma perlu menarik berkasnya ke cache. Menaruh
// keduanya di satu berkas memaksa setiap halaman ikut memikul modul MediaPipe.

// Probe SIMD, byte-nya disalin persis dari @mediapipe/tasks-vision. Varian wasm
// TIDAK boleh ditebak: tasks-vision jatuh ke `_nosimd_` bila SIMD tak didukung
// (iOS < 16.4), dan menghangatkan varian yang salah berarti menarik 9,4 MB sia-sia
// lalu tetap mengunduh yang benar. Sama dengan partials/preload-wajah.blade.php.
const PROBE_SIMD = new Uint8Array([0, 97, 115, 109, 1, 0, 0, 0, 1, 5, 1, 96, 0, 1, 123, 3, 2, 1, 0, 10, 10, 1, 8, 0, 65, 0, 253, 15, 253, 98, 11]);

/** Berkas runtime detektor untuk perangkat ini. */
export function berkasWajah() {
    let simd = true;
    try { simd = WebAssembly.validate(PROBE_SIMD); } catch (e) { simd = true; }
    const nama = `vision_wasm${simd ? '' : '_nosimd'}_internal`;

    return [
        `/mediapipe/wasm/${nama}.js`,
        `/mediapipe/wasm/${nama}.wasm`,
        '/mediapipe/blaze_face_short_range.tflite',
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
