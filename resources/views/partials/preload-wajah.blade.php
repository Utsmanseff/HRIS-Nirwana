{{-- Preload runtime detektor wajah untuk halaman absen.

     Berkasnya dimuat berurutan oleh tfjs (bundel → wasm backend → model.json →
     model.bin), jadi tanpa preload tiap berkas menunggu yang sebelumnya selesai. Di
     HP tiap tunggu itu satu perjalanan bolak-balik jaringan penuh. Diukur di iPhone
     saat masih MediaPipe: rantai serial seperti itu memakan 8995 ms hanya untuk satu
     berkas 2,6 MB.

     `crossorigin` WAJIB walau se-origin: tanpa itu preload tak cocok dengan permintaan
     tfjs dan berkasnya diunduh DUA KALI (terbukti waktu memakai MediaPipe).

     Varian wasm TIDAK boleh di-hardcode. tfjs-backend-wasm jatuh ke build nosimd bila
     WebAssembly SIMD tak didukung (iOS < 16.4 masih ada di lapangan), dan menebak
     varian yang salah berarti ratusan KB terunduh sia-sia dulu sebelum varian benar
     menyusul. Probe-nya dijalankan di sini; byte-nya standar, sama dengan yang dipakai
     tfjs sendiri dan dengan absen-wajah-berkas.js. --}}
{{-- Bundel tfjs itu chunk dinamis: tanpa baris ini ia baru mulai diunduh SETELAH
     app.js selesai dijalankan. Vite::asset menyelesaikan namanya dari manifest, jadi
     hash-nya tak pernah basi. --}}
<link rel="modulepreload" href="{{ Vite::asset('resources/js/absen-wajah.js') }}">
<link rel="preload" href="/wajah/blazeface-front.json" as="fetch" crossorigin>
<link rel="preload" href="/wajah/blazeface-front.bin" as="fetch" crossorigin>
<script>
(function () {
    var PROBE_SIMD = new Uint8Array([0,97,115,109,1,0,0,0,1,5,1,96,0,1,123,3,2,1,0,10,10,1,8,0,65,0,253,15,253,98,11]);
    var simd;
    try { simd = WebAssembly.validate(PROBE_SIMD); } catch (e) { return; }
    var l = document.createElement('link');
    l.rel = 'preload';
    l.as = 'fetch';
    l.type = 'application/wasm';
    l.href = '/wajah/tfjs-backend-wasm' + (simd ? '-simd' : '') + '.wasm';
    l.crossOrigin = 'anonymous';
    document.head.appendChild(l);
})();
</script>
