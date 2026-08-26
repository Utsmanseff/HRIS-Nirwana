{{-- Preload runtime MediaPipe untuk halaman absen.

     MediaPipe memuat berkasnya BERURUTAN: modul JS → glue JS → wasm → tflite, dan
     tflite baru berangkat setelah wasm selesai di-instantiate. Di HP itu empat
     perjalanan bolak-balik yang saling menunggu. Preload di <head> bikin ketiganya
     berangkat barengan saat HTML masih diurai. Diukur 2026-08-26 di localhost:
     563 ms → 299 ms; di HP selisihnya lebih besar karena tiap hop = satu RTT.

     `crossorigin` WAJIB walau se-origin: tanpa itu preload tak cocok dengan permintaan
     MediaPipe dan berkasnya diunduh DUA KALI (terbukti pada glue JS).

     Varian wasm TIDAK boleh di-hardcode. tasks-vision memilih `_nosimd_` bila WebAssembly
     SIMD tak didukung (iOS < 16.4 masih ada di lapangan). Menebak varian yang salah bukan
     cuma gagal preload — 9,4 MB salah unduh DULU, baru varian yang benar menyusul. Karena
     itu probe-nya dijalankan di sini, byte-nya disalin persis dari tasks-vision. --}}
<link rel="preload" href="/mediapipe/blaze_face_short_range.tflite" as="fetch" crossorigin>
<script>
(function () {
    var PROBE_SIMD = new Uint8Array([0,97,115,109,1,0,0,0,1,5,1,96,0,1,123,3,2,1,0,10,10,1,8,0,65,0,253,15,253,98,11]);
    var simd;
    try { simd = WebAssembly.validate(PROBE_SIMD); } catch (e) { return; }
    var nama = 'vision_wasm' + (simd ? '' : '_nosimd') + '_internal';
    [['js', 'script'], ['wasm', 'fetch']].forEach(function (v) {
        var l = document.createElement('link');
        l.rel = 'preload';
        l.as = v[1];
        l.href = '/mediapipe/wasm/' + nama + '.' + v[0];
        if (v[0] === 'wasm') l.type = 'application/wasm';
        l.crossOrigin = 'anonymous';
        document.head.appendChild(l);
    });
})();
</script>
