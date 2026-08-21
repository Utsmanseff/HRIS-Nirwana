// Deteksi "ada wajah" via MediaPipe FaceDetector (host-lokal). Bukan recognition/liveness.
import { FaceDetector, FilesetResolver } from '@mediapipe/tasks-vision';

let detector = null;
let muatPromise = null;

/**
 * Muat runtime WASM (~9,4 MB) + model tflite. Berat, jadi:
 * - promise-nya di-memo → dipanggil berkali-kali tetap satu kali unduh;
 * - dipisah dari mulaiDeteksiWajah() supaya bisa DIPRA-MUAT paralel dengan prompt izin
 *   kamera/GPS. Dulu muat baru mulai setelah kamera siap → user menunggu seri
 *   (izin → unduh 9,4 MB → kompilasi WASM) dan layar menahan "wajah tak terdeteksi"
 *   5–10 detik.
 */
function muat() {
    if (! muatPromise) {
        muatPromise = (async () => {
            const fileset = await FilesetResolver.forVisionTasks('/mediapipe/wasm');
            detector = await FaceDetector.createFromOptions(fileset, {
                baseOptions: { modelAssetPath: '/mediapipe/blaze_face_short_range.tflite' },
                runningMode: 'VIDEO',
                minDetectionConfidence: 0.5,
            });
        })();
    }

    return muatPromise;
}

/** Pra-muat detektor tanpa menunggu; aman dipanggil lebih dari sekali. */
export function pramuatDeteksiWajah() {
    return muat().catch((e) => { console.warn('MediaPipe pramuat gagal:', e); });
}

/**
 * Mulai loop deteksi ringan pada elemen video; panggil setWajah(bool) tiap frame.
 * Return fungsi stop. Bila gagal muat → setWajah(true) (fallback: tak mengunci tombol;
 * komponen menandai wajah_verif=false karena kamera dianggap tak terverifikasi).
 */
export async function mulaiDeteksiWajah(video, setWajah) {
    try {
        if (! detector) await muat();
    } catch (e) {
        console.warn('MediaPipe gagal muat — fallback:', e);
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
