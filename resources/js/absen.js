// Alpine component untuk halaman /absensi. Kamera + geolocation + gating + capture.
// Deteksi wajah (MediaPipe) & peta (Leaflet) di-hook di Task 5/6 lewat properti reaktif di sini.

import { LokasiHaversine } from './absen-lokasi.js';
import { mulaiDeteksiWajah } from './absen-wajah.js';
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
        lat: null,
        long: null,
        akurasi: null,
        dalamRadius: false,
        lokasiTeks: 'Mencari lokasi…',
        kameraSiap: false,
        mengirim: false,
        _kameraGagal: false,

        get bolehAbsen() {
            return this.wajahAda && this.dalamRadius && this.akurasi != null
                && this.akurasi <= this.maxAkurasi && this.kameraSiap && !this.mengirim;
        },

        init() {
            this.tickJam();
            setInterval(() => this.tickJam(), 1000);
            this.mulaiKamera();
            this.mulaiLokasi();
            // Mulai deteksi wajah begitu kamera siap (lewati bila kamera gagal → fallback).
            this.$el.addEventListener('kamera-siap', async () => {
                if (this._kameraGagal) return;
                this._stopWajah = await mulaiDeteksiWajah(this.$refs.video, (ada) => { this.wajahAda = ada; });
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
            navigator.geolocation.watchPosition(
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
                const blob = await this.tangkapFoto();
                const wajah = this._kameraGagal ? false : this.wajahAda;
                this.$wire.set('lat', this.lat, false);
                this.$wire.set('long', this.long, false);
                this.$wire.set('akurasi', this.akurasi, false);
                this.$wire.set('wajahAda', wajah, false);
                this.$wire.upload('foto', new File([blob], 'absen.webp', { type: 'image/webp' }),
                    () => { this.$wire.simpan(); this.mengirim = false; },
                    () => { this.mengirim = false; },
                    () => {});
            } catch (e) {
                console.error(e);
                this.mengirim = false;
            }
        },

        tangkapFoto() {
            return new Promise((resolve) => {
                const v = this.$refs.video;
                const c = document.createElement('canvas');
                c.width = v.videoWidth || 480;
                c.height = v.videoHeight || 600;
                const ctx = c.getContext('2d');
                // Un-mirror: kamera depan kirim frame ter-mirror → balik horizontal agar foto natural.
                ctx.translate(c.width, 0);
                ctx.scale(-1, 1);
                ctx.drawImage(v, 0, 0, c.width, c.height);
                c.toBlob((b) => resolve(b), 'image/webp', 0.85);
            });
        },
    }));
});
