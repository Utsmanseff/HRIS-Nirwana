// Detektor wajah alternatif: TensorFlow.js + BlazeFace (short-range), backend WASM.
//
// Kandidat pengganti MediaPipe Tasks. Dipasang BERDAMPINGAN dulu, dipilih lewat
// ?detektor= di halaman absen, supaya bisa diadu di perangkat asli sebelum yang
// lama dicabut.
//
// Kenapa backend WASM dan BUKAN WebGL — ini pelajaran yang sudah dibayar dua kali
// (diukur 2026-08-26/27, desktop, cache hangat):
//
//   MediaPipe delegate GPU  inferensi pertama 2592 ms
//   tfjs + WebGL            inferensi pertama 2637 ms
//   tfjs + CPU (JS murni)   inferensi pertama  242 ms, tapi 141 ms per inferensi
//   tfjs + WASM (XNNPACK)   inferensi pertama   33 ms, ~9 ms per inferensi
//
// Biaya ~2,6 detik itu KOMPILASI SHADER, bukan framework — siapa pun yang menyentuh
// GPU membayarnya. Jangan pernah pindah ke WebGL/GPU untuk gerbang ini.
//
// Ukuran yang harus dikompilasi tiap halaman dibuka: 9.424 KB (MediaPipe) → 425 KB.
// Itu yang menentukan, karena Safari TIDAK bisa menyimpan WebAssembly.Module yang
// sudah dikompilasi ke IndexedDB (Chrome bisa) — di iPhone biaya itu dibayar ulang
// setiap kali, dan tak ada strategi cache yang bisa menyentuhnya.
import * as tf from '@tensorflow/tfjs-core';
import { setWasmPaths } from '@tensorflow/tfjs-backend-wasm';
import '@tensorflow/tfjs-backend-wasm';
import { loadGraphModel } from '@tensorflow/tfjs-converter';

/** Sisi input model BlazeFace short-range. */
const SISI = 128;

/** Ambang keyakinan setelah sigmoid. 0,75 lebih ketat dari MediaPipe (0,5) karena
 *  di sini skor diambil apa adanya dari anchor terbaik, tanpa NMS. */
const AMBANG = 0.75;

let model = null;
let muatPromise = null;

function muat() {
    if (! muatPromise) {
        muatPromise = (async () => {
            // Host sendiri; tfjs memilih varian simd/nosimd sendiri dari direktori ini.
            // Varian threaded sengaja tak disediakan: ia menuntut header COOP/COEP
            // (cross-origin isolation) yang akan mematahkan peta Leaflet & ikon.
            setWasmPaths('/wajah/');
            await tf.setBackend('wasm');
            await tf.ready();
            model = await loadGraphModel('/wajah/blazeface-front.json');

            // Panaskan graph dengan satu frame boneka, selagi UI masih bilang
            // "menyiapkan" — bukan pada frame kamera pertama.
            const boneka = tf.zeros([1, SISI, SISI, 3]);
            buangSemua(model.execute(boneka));
            boneka.dispose();
        })();
    }

    return muatPromise;
}

function buangSemua(keluaran) {
    (Array.isArray(keluaran) ? keluaran : [keluaran]).forEach((t) => t.dispose());
}

/**
 * Skor keyakinan tertinggi dari seluruh anchor.
 *
 * Kita cuma butuh "ada wajah atau tidak", jadi kotak & landmark-nya tak didekode
 * sama sekali: tak ada anchor, tak ada NMS. Model mengeluarkan empat tensor —
 * dua classificator (logit skor, dimensi terakhir 1) dan dua regressor (16). Ambil
 * maksimum dari yang berdimensi 1, lalu sigmoid.
 */
function skorTertinggi(keluaran) {
    const tensor = Array.isArray(keluaran) ? keluaran : [keluaran];
    let maks = -Infinity;
    for (const t of tensor) {
        if (t.shape[t.shape.length - 1] !== 1) continue;
        const nilai = tf.max(t).dataSync()[0];
        if (nilai > maks) maks = nilai;
    }

    return maks === -Infinity ? 0 : 1 / (1 + Math.exp(-maks));
}

/** Pra-muat detektor. Resolve true bila siap, false bila gagal. */
export function pramuatDeteksiWajah() {
    return muat().then(() => true).catch((e) => {
        console.warn('tfjs pramuat gagal:', e);

        return false;
    });
}

/** Sekali deteksi pada sumber gambar (video/canvas). Return skor 0..1. */
export function skorWajah(sumber) {
    const masukan = tf.tidy(() => {
        const piksel = tf.browser.fromPixels(sumber);
        const kecil = tf.image.resizeBilinear(piksel, [SISI, SISI]);

        // BlazeFace dilatih pada rentang [-1, 1], bukan [0, 1].
        return tf.expandDims(tf.sub(tf.div(tf.cast(kecil, 'float32'), 127.5), 1), 0);
    });

    const keluaran = model.execute(masukan);
    const skor = skorTertinggi(keluaran);
    buangSemua(keluaran);
    masukan.dispose();

    return skor;
}

/**
 * Mulai loop deteksi ringan; panggil setWajah(bool) tiap frame. Return fungsi stop.
 * Antarmuka sengaja dibuat sama persis dengan absen-wajah.js supaya bisa ditukar.
 */
export async function mulaiDeteksiWajah(video, setWajah) {
    if (! await pramuatDeteksiWajah()) {
        setWajah(true);

        return () => {};
    }

    let aktif = true;
    let berikutnya = 0;
    const loop = () => {
        if (! aktif) return;
        const kini = performance.now();
        if (video.readyState >= 2 && kini >= berikutnya) {
            berikutnya = kini + 125; // ~8 fps, sama dengan detektor lama
            try {
                setWajah(skorWajah(video) >= AMBANG);
            } catch (e) {
                console.warn('tfjs deteksi gagal:', e);
            }
        }
        requestAnimationFrame(loop);
    };
    requestAnimationFrame(loop);

    return () => { aktif = false; };
}
