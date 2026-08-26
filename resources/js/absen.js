// Alpine component untuk halaman /absensi. Kamera + geolocation + gating + capture.
// Deteksi wajah (MediaPipe) & peta (Leaflet) di-hook di Task 5/6 lewat properti reaktif di sini.

import { LokasiHaversine } from './absen-lokasi.js';
import { mulaiDeteksiWajah, pramuatDeteksiWajah } from './absen-wajah.js';
import { buatPeta } from './absen-peta.js';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('absenSwipe', (cfg) => ({
        // konfigurasi kantor (dari server)
        officeLat: cfg.officeLat,
        officeLong: cfg.officeLong,
        radius: cfg.radius,
        maxAkurasi: cfg.maxAkurasi,

        // state reaktif
        jam: '--:--',
        wajahAda: false,       // diisi MediaPipe (Task 5); default false → gerbang UX
        detektorSiap: false,   // false = model masih dimuat → UI bilang "menyiapkan", bukan "tak terdeteksi"
        // Mode diagnostik detektor. null = normal (staf tak akan pernah melihatnya).
        // ?detektor=tfjs    → pakai TensorFlow.js + BlazeFace, untuk mengukur kecepatan
        // ?detektor=banding → jalankan KEDUANYA berbarengan, untuk mengadu kecocokan
        diag: null,
        deteksiAktif: false,   // true HANYA saat loop deteksi benar-benar jalan
        lat: null,
        long: null,
        akurasi: null,
        dalamRadius: false,
        lokasiTeks: 'Mencari lokasi…',
        kameraSiap: false,
        mengirim: false,
        _kameraGagal: false,
        _jamTimer: null,
        _watchId: null,
        _deteksiTersedia: null,
        _detektor: null,
        _stopBanding: null,

        get bolehAbsen() {
            return this.wajahAda && this.dalamRadius && this.akurasi != null
                && this.akurasi <= this.maxAkurasi && this.kameraSiap && !this.mengirim;
        },

        init() {
            this.tickJam();
            this._jamTimer = setInterval(() => this.tickJam(), 1000);
            // Pra-muat model DULU (fire-and-forget) supaya unduh + kompilasi WASM jalan
            // BARENGAN prompt izin kamera/GPS, bukan setelahnya. Statusnya dilacak
            // TERPISAH dari kamera: dulu detektorSiap baru true setelah kamera siap,
            // jadi izin kamera yang lambat terbaca user sebagai "detektor lelet".
            const pilihan = new URLSearchParams(location.search).get('detektor');
            if (pilihan === 'tfjs' || pilihan === 'banding') {
                this.diag = { mode: pilihan, siap: {}, inferensi: {}, wajah: {}, berkas: [] };
            }

            // Gagal muat pun harus MENGAKHIRI keadaan "menyiapkan" — kalau tidak,
            // badge menggantung selamanya di HP yang unduhannya kandas.
            const t0 = performance.now();
            this.detektorUtama().then((det) => det.pramuatDeteksiWajah()).then((siap) => {
                this.detektorSiap = true;
                this._deteksiTersedia = siap;
                if (this.diag) {
                    this.diag.siap[this.namaDetektor()] = Math.round(performance.now() - t0);
                    this.kumpulkanBerkas();
                }
            });
            this.mulaiKamera();
            this.mulaiLokasi();
            // Mulai deteksi wajah begitu kamera siap (lewati bila kamera gagal → fallback).
            this.$el.addEventListener('kamera-siap', async () => {
                if (this._kameraGagal) return;
                const det = await this.detektorUtama();
                this._stopWajah = await det.mulaiDeteksiWajah(this.$refs.video, (ada) => {
                    this.wajahAda = ada;
                    if (this.diag) this.diag.wajah[this.namaDetektor()] = ada;
                });
                this.detektorSiap = true;
                this.deteksiAktif = this._deteksiTersedia !== false;
                if (this.diag?.mode === 'banding') this.mulaiPembanding();
            });
            // Peta Leaflet + marker posisi live.
            this.$nextTick(() => {
                this._peta = buatPeta(this.$refs.peta, {
                    officeLat: this.officeLat, officeLong: this.officeLong, radius: this.radius,
                });
                this._peta.invalidate();
            });
            this.$el.addEventListener('lokasi-berubah', (e) => {
                this._peta?.posisi(e.detail.lat, e.detail.long, e.detail.akurasi);
            });
            // Hasil absen (berhasil MAUPUN gagal) naik ke modal global.
            // Pakai addEventListener di $el, bukan $wire.on: Livewire melempar
            // event server di elemen komponen ini juga, dan $wire.on tak
            // mengembalikan pelepas — jadi tak bisa dibersihkan saat destroy().
            this._onTersimpan = (e) => {
                const { aksi, jam } = e.detail ?? {};
                this.beritahu(
                    aksi === 'pulang' ? 'Absen Pulang Berhasil' : 'Absen Masuk Berhasil',
                    `Tercatat jam ${jam}.`,
                );
            };
            this._onGagal = (e) => {
                this.beritahu('Absen Gagal', (e.detail ?? {}).pesan ?? 'Absen tidak tersimpan.');
            };
            this.$el.addEventListener('absen-tersimpan', this._onTersimpan);
            this.$el.addEventListener('absen-gagal', this._onGagal);
        },

        // wire:navigate memasang ulang komponen ini tiap kali halaman dibuka.
        // Tanpa destroy(), interval jam & pendengar event menumpuk tiap kunjungan.
        destroy() {
            clearInterval(this._jamTimer);
            if (this._watchId != null) navigator.geolocation.clearWatch(this._watchId);
            this._stopWajah?.();
            this._stopBanding?.();
            this.$el.removeEventListener('absen-tersimpan', this._onTersimpan);
            this.$el.removeEventListener('absen-gagal', this._onGagal);
        },

        beritahu(judul, pesan) {
            const store = window.Alpine?.store('konfirmasi');
            if (store && typeof store.beritahu === 'function') {
                store.beritahu({ judul, pesan });
            } else {
                window.alert(judul + '\n\n' + pesan);
            }
        },

        /** Detektor yang menyetir gerbang. tfjs di-import dinamis supaya halaman lain
         *  tak ikut memikul bundelnya. */
        detektorUtama() {
            if (this._detektor) return this._detektor;
            this._detektor = this.diag?.mode === 'tfjs'
                ? import('./absen-wajah-tfjs.js')
                : Promise.resolve({ pramuatDeteksiWajah, mulaiDeteksiWajah });

            return this._detektor;
        },

        namaDetektor() {
            return this.diag?.mode === 'tfjs' ? 'tfjs' : 'mediapipe';
        },

        /** Mode banding: MediaPipe tetap menyetir gerbang, tfjs jalan berbarengan dan
         *  hanya menulis ke panel — supaya dua verdict bisa dilihat pada wajah yang sama. */
        async mulaiPembanding() {
            try {
                const m = await import('./absen-wajah-tfjs.js');
                const t0 = performance.now();
                if (! await m.pramuatDeteksiWajah()) return;
                this.diag.siap.tfjs = Math.round(performance.now() - t0);
                this.kumpulkanBerkas();
                this._stopBanding = await m.mulaiDeteksiWajah(this.$refs.video, (ada) => {
                    this.diag.wajah.tfjs = ada;
                });
                this.ukurInferensi(m);
            } catch (e) {
                console.warn('pembanding tfjs gagal:', e);
            }
        },

        /** Rata-rata 10 inferensi, sekali saja — angka yang bisa dilaporkan. */
        ukurInferensi(m) {
            const t = performance.now();
            for (let i = 0; i < 10; i++) m.skorWajah(this.$refs.video);
            this.diag.inferensi.tfjs = Math.round((performance.now() - t) / 10);
        },

        /** transferSize 0 = dilayani cache; > 0 = benar-benar diunduh lagi. Inilah yang
         *  memisahkan "cache tak jalan" dari "kompilasi memang selama itu". */
        kumpulkanBerkas() {
            this.diag.berkas = performance.getEntriesByType('resource')
                .filter((r) => /\/(mediapipe|wajah)\//.test(r.name))
                .map((r) => ({
                    nama: r.name.split('/').pop(),
                    kb: Math.round(r.transferSize / 1024),
                    ms: Math.round(r.duration),
                }));
        },

        // Satu sumber untuk teks badge: tiga sebab tunggu yang berbeda tak boleh
        // dipukul rata jadi "wajah tak terdeteksi" (itu bikin orang menyalahkan kameranya).
        get statusWajah() {
            if (! this.detektorSiap) return 'Menyiapkan deteksi wajah…';
            if (! this.kameraSiap) return 'Menyiapkan kamera…';
            if (! this.deteksiAktif) return 'Deteksi wajah tak aktif';

            return this.wajahAda ? 'Wajah terdeteksi' : 'Wajah tak terdeteksi';
        },

        get warnaStatusWajah() {
            if (! this.detektorSiap || ! this.kameraSiap || ! this.deteksiAktif) {
                return 'background:rgba(71,85,105,.9)';
            }

            return this.wajahAda ? 'background:rgba(22,163,74,.9)' : 'background:rgba(220,38,38,.9)';
        },

        tickJam() {
            this.jam = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        },

        async mulaiKamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
                this.$refs.video.srcObject = stream;
                await this.$refs.video.play();
                this.kameraSiap = true;
                this.$dispatch('kamera-siap'); // untuk MediaPipe loop (Task 5)
            } catch (e) {
                // Fallback: kamera gagal → tetap boleh absen (wajahAda dipaksa true agar tombol tak terkunci),
                // tapi ambil() mengirim wajahAda=false → server catat wajah_verif=false.
                this.kameraSiap = true;
                this.wajahAda = true;
                this.detektorSiap = true;
                this._kameraGagal = true;
                console.warn('Kamera gagal:', e);
            }
        },

        mulaiLokasi() {
            if (!('geolocation' in navigator)) { this.lokasiTeks = 'GPS tak tersedia'; return; }
            if (!window.isSecureContext) {
                this.lokasiTeks = 'GPS butuh HTTPS/localhost';
                console.warn('Geolocation diblokir: origin bukan secure context (butuh https atau localhost).');
                return;
            }
            this._watchId = navigator.geolocation.watchPosition(
                (pos) => {
                    this.lat = pos.coords.latitude;
                    this.long = pos.coords.longitude;
                    this.akurasi = pos.coords.accuracy;
                    const jarak = LokasiHaversine(this.lat, this.long, this.officeLat, this.officeLong);
                    this.dalamRadius = jarak <= this.radius;
                    const akur = Math.round(this.akurasi);
                    this.lokasiTeks = this.dalamRadius
                        ? `Dalam radius · ${Math.round(jarak)}m (±${akur}m)`
                        : `Di luar radius · ${Math.round(jarak)}m (±${akur}m)`;
                    this.$dispatch('lokasi-berubah', { lat: this.lat, long: this.long, akurasi: this.akurasi }); // untuk Leaflet
                },
                (err) => {
                    // Bedakan sebab: 1=izin ditolak, 2=posisi tak tersedia, 3=timeout.
                    const pesan = { 1: 'Izin lokasi ditolak', 2: 'Lokasi tak tersedia (tak ada sinyal GPS)', 3: 'GPS timeout — coba lagi' };
                    this.lokasiTeks = pesan[err.code] || 'GPS gagal';
                    console.warn('Geolocation error', err.code, err.message);
                },
                { enableHighAccuracy: true, maximumAge: 0, timeout: 30000 }, // maximumAge:0 = paksa fix GPS segar, bukan cache network
            );
        },

        async ambil() {
            if (!this.bolehAbsen) return;
            this.mengirim = true;
            try {
                const foto = this.tangkapFoto();
                // wajah_verif hanya boleh true kalau detektornya BENAR-BENAR jalan.
                // Sebelumnya cuma kamera-gagal yang dicek, jadi HP yang gagal memuat
                // MediaPipe tetap mengirim true (wajahAda dipaksa true agar tombol tak
                // terkunci) — verifikasi yang tak pernah terjadi tercatat lulus.
                const wajah = this.deteksiAktif && this.wajahAda;
                // set(..., false) menunda pengiriman: keempatnya menumpang permintaan
                // berikutnya, yaitu simpan(). Jadi sekali absen = SATU permintaan.
                // Dulu fotonya lewat $wire.upload(), dan itu diam-diam tiga permintaan
                // berurutan sendiri (_startUpload → POST berkas → _finishUpload) sebelum
                // simpan() jadi yang keempat.
                this.$wire.set('lat', this.lat, false);
                this.$wire.set('long', this.long, false);
                this.$wire.set('akurasi', this.akurasi, false);
                this.$wire.set('wajahAda', wajah, false);
                await this.$wire.simpan(foto);
            } catch (e) {
                console.error(e);
            } finally {
                this.mengirim = false;
            }
        },

        /**
         * Tangkap frame → data URL. Dikecilkan ke maksimal 720 px sisi terpanjang DI SINI,
         * bukan di server: dulu frame penuh dikirim lewat jaringan seluler hanya untuk
         * dibuang oleh downscale server ke ukuran yang sama.
         */
        tangkapFoto() {
            const v = this.$refs.video;
            const asalW = v.videoWidth || 480;
            const asalH = v.videoHeight || 600;
            const skala = Math.min(1, 720 / Math.max(asalW, asalH));
            const w = Math.round(asalW * skala);
            const h = Math.round(asalH * skala);

            const c = document.createElement('canvas');
            c.width = w;
            c.height = h;
            const ctx = c.getContext('2d');
            // Un-mirror: kamera depan kirim frame ter-mirror → balik horizontal agar foto natural.
            ctx.translate(w, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(v, 0, 0, w, h);

            // Safari < 16.4 TIDAK meng-encode WebP: ia diam-diam mengembalikan PNG,
            // tanpa error. PNG kamera ~7x lebih besar dari WebP, jadi HP lama dulu
            // mengunggah 1,5 MB tanpa ada yang tahu. Periksa hasilnya, jangan percaya
            // tipe yang kita minta.
            const webp = c.toDataURL('image/webp', 0.8);

            return webp.startsWith('data:image/webp') ? webp : c.toDataURL('image/jpeg', 0.8);
        },
    }));
});
