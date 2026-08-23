// Sheet panduan kontekstual (tombol "?" di appbar/topbar).
// Satu store global menyetir satu sheet; tombol mana pun hanya memanggil tampil().
// URL sengaja TIDAK diubah: sheet ini bukan halaman, dan mengubah hash membuat
// tombol kembali di HP terasa rusak.

const singgahan = new Map(); // "slug/bagian" -> data fragmen

document.addEventListener('alpine:init', () => {
    window.Alpine.store('tanyaPanduan', {
        buka: false,
        memuat: false,
        gagal: false,
        slug: '',
        bagian: '',
        bab: '',
        judul: '',
        isi: '',
        lain: [],
        fokusSebelumnya: null,

        async tampil(slug, bagian) {
            if (!this.buka) {
                this.fokusSebelumnya = document.activeElement;
                document.body.classList.add('ptny-terkunci');
            }

            this.buka = true;
            this.gagal = false;
            this.slug = slug;
            this.bagian = bagian;

            const kunci = `${slug}/${bagian}`;
            const tersimpan = singgahan.get(kunci);

            if (tersimpan) {
                this.pakai(tersimpan);
            } else {
                this.memuat = true;
                this.judul = '';
                this.isi = '';
                try {
                    const r = await fetch(`/panduan/${slug}/bagian/${bagian}`, {
                        headers: { Accept: 'application/json' },
                    });
                    if (!r.ok) throw new Error(`HTTP ${r.status}`);
                    const data = await r.json();
                    singgahan.set(kunci, data);
                    this.pakai(data);
                } catch (e) {
                    this.gagal = true;
                    this.judul = 'Tidak bisa dimuat';
                    this.lain = [];
                }
                this.memuat = false;
            }

            // Store bukan komponen: pakai Alpine.nextTick, bukan this.$nextTick.
            window.Alpine.nextTick(() => {
                const sheet = document.querySelector('.ptny-sheet');
                const isi = document.querySelector('.ptny-isi');
                if (isi) isi.scrollTop = 0;
                const tutup = sheet?.querySelector('.ptny-tutup');
                if (tutup) tutup.focus();
            });
        },

        pakai(data) {
            this.bab = data.bab;
            this.judul = data.judul;
            this.isi = data.html;
            this.lain = data.lain;
        },

        tutup() {
            if (!this.buka) return;
            this.buka = false;
            document.body.classList.remove('ptny-terkunci');
            const kembali = this.fokusSebelumnya;
            this.fokusSebelumnya = null;
            // body bukan sasaran fokus yang berguna: biarkan saja di tempatnya.
            if (kembali && kembali !== document.body && typeof kembali.focus === 'function') {
                kembali.focus();
            }
        },

        // Tab tak boleh kabur ke halaman di belakang sheet selama dialog terbuka.
        jagaFokus(e) {
            const sheet = document.querySelector('.ptny-sheet');
            if (!sheet) return;

            const fokusable = [...sheet.querySelectorAll('a[href], button, [tabindex]:not([tabindex="-1"])')]
                .filter((el) => el.offsetParent !== null);
            if (fokusable.length === 0) return;

            const pertama = fokusable[0];
            const terakhir = fokusable[fokusable.length - 1];

            if (e.shiftKey && document.activeElement === pertama) {
                e.preventDefault();
                terakhir.focus();
            } else if (!e.shiftKey && document.activeElement === terakhir) {
                e.preventDefault();
                pertama.focus();
            }
        },
    });
});
