// Store modal konfirmasi global. Markup di components/konfirmasi.blade.php,
// dipasang sekali di layout app. Tombol pemicu: $store.konfirmasi.buka({...}).
document.addEventListener('alpine:init', () => {
    window.Alpine.store('konfirmasi', {
        tampil: false,
        mode: 'konfirmasi', // 'konfirmasi' | 'info'
        judul: '',
        pesan: '',
        varian: 'danger', // 'danger' | 'primary'
        labelYa: 'Ya',
        _onConfirm: null,

        buka({ judul, pesan = '', varian = 'danger', labelYa = 'Ya', onConfirm = null }) {
            this.mode = 'konfirmasi';
            this.judul = judul;
            this.pesan = pesan;
            this.varian = varian;
            this.labelYa = labelYa;
            this._onConfirm = onConfirm;
            this.tampil = true;
        },

        beritahu({ judul, pesan = '' }) {
            this.mode = 'info';
            this.judul = judul;
            this.pesan = pesan;
            this.varian = 'primary';
            this.labelYa = 'Mengerti';
            this._onConfirm = null;
            this.tampil = true;
        },

        setuju() {
            const cb = this._onConfirm;
            this.tutup();
            if (cb) cb();
        },

        tutup() {
            this.tampil = false;
            this._onConfirm = null;
        },
    });
});
