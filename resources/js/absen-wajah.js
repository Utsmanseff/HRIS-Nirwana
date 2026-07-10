// Deteksi "ada wajah" via MediaPipe FaceDetector (host-lokal). Bukan recognition/liveness.
import { FaceDetector, FilesetResolver } from '@mediapipe/tasks-vision';

let detector = null;

async function muat() {
    const fileset = await FilesetResolver.forVisionTasks('/mediapipe/wasm');
    detector = await FaceDetector.createFromOptions(fileset, {
        baseOptions: { modelAssetPath: '/mediapipe/blaze_face_short_range.tflite' },
        runningMode: 'VIDEO',
        minDetectionConfidence: 0.5,
    });
}

/**
 * Mulai loop deteksi ringan pada elemen video; panggil setWajah(bool) tiap frame.
 * Return fungsi stop. Bila gagal muat → setWajah(true) (fallback: tak mengunci tombol;
 * komponen menandai wajah_verif=false karena kamera dianggap tak terverifikasi).
 */
export async function mulaiDeteksiWajah(video, setWajah) {
    try {
        if (!detector) await muat();
    } catch (e) {
        console.warn('MediaPipe gagal muat — fallback:', e);
        setWajah(true);
        return () => {};
    }

    let aktif = true;
    const loop = () => {
        if (!aktif) return;
        if (video.readyState >= 2) {
            const hasil = detector.detectForVideo(video, performance.now());
            setWajah((hasil.detections?.length ?? 0) > 0);
        }
        requestAnimationFrame(loop);
    };
    requestAnimationFrame(loop);

    return () => { aktif = false; };
}
