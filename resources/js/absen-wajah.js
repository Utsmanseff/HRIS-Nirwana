// Deteksi "ada wajah" via MediaPipe FaceDetector (host-lokal). Bukan recognition/liveness.
import { FaceDetector, FilesetResolver } from '@mediapipe/tasks-vision';

let detector = null;
let muatPromise = null;

/**
 * Muat runtime WASM (~9,4 MB; ~2,6 MB di kabel karena brotli) + model tflite.
 *
 * MediaPipe memuat ketiga berkasnya BERURUTAN: modul JS → glue JS → wasm →
 * tflite (tflite bahkan baru mulai setelah wasm selesai di-instantiate). Di HP
 * itu empat perjalanan bolak-balik yang saling menunggu. Karena itu halaman
 * absen mem-preload ketiganya di <head> supaya berangkat BARENGAN, dan service
 * worker menyimpannya cache-first supaya absen kedua dan seterusnya nol jaringan.
 *
 * delegate DIBIARKAN CPU dengan sengaja: dengan 'GPU', inferensi PERTAMA memakan
 * ~2,6 detik untuk kompilasi shader (diukur 2026-08-26) — persis gejala "menyiapkan
 * deteksi wajah lama". CPU: inferensi pertama ~100 ms, berikutnya ~15 ms.
 */
function muat() {
    if (! muatPromise) {
        muatPromise = (async () => {
            const fileset = await FilesetResolver.forVisionTasks('/mediapipe/wasm');
            detector = await FaceDetector.createFromOptions(fileset, {
                baseOptions: { modelAssetPath: '/mediapipe/blaze_face_short_range.tflite', delegate: 'CPU' },
                runningMode: 'VIDEO',
                minDetectionConfidence: 0.5,
            });
            // Panaskan graph dengan satu frame boneka. Inferensi pertama ~7x lebih
            // mahal dari berikutnya; biarkan mahalnya terjadi SEKARANG, selagi UI
            // masih bilang "menyiapkan", bukan pada frame kamera pertama.
            try {
                const kanvas = document.createElement('canvas');
                kanvas.width = 64;
                kanvas.height = 64;
                kanvas.getContext('2d').fillRect(0, 0, 64, 64);
                detector.detectForVideo(kanvas, 0);
            } catch (e) {
                console.warn('Pemanasan detektor dilewati:', e);
            }
        })();
    }

    return muatPromise;
}

/**
 * Pra-muat detektor. Resolve true bila siap, false bila gagal (pemanggil boleh
 * lanjut tanpa deteksi). Aman dipanggil lebih dari sekali.
 */
export function pramuatDeteksiWajah() {
    return muat().then(() => true).catch((e) => {
        console.warn('MediaPipe pramuat gagal:', e);

        return false;
    });
}

/**
 * Mulai loop deteksi ringan pada elemen video; panggil setWajah(bool) tiap frame.
 * Return fungsi stop. Bila gagal muat → setWajah(true) (fallback: tak mengunci tombol;
 * komponen menandai wajah_verif=false karena kamera dianggap tak terverifikasi).
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
        // Deteksi dibatasi ~8 fps: cukup responsif untuk gerbang "ada wajah", tapi tidak
        // memanggang CPU HP tiap frame (yang justru memperlambat preview kamera).
        const kini = performance.now();
        if (video.readyState >= 2 && kini >= berikutnya) {
            berikutnya = kini + 125;
            const hasil = detector.detectForVideo(video, kini);
            setWajah((hasil.detections?.length ?? 0) > 0);
        }
        requestAnimationFrame(loop);
    };
    requestAnimationFrame(loop);

    return () => { aktif = false; };
}
