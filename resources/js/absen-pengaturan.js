// Peta drag-marker untuk halaman Pengaturan Absen. Dua-arah dengan Livewire via $wire.
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

const ikon = L.icon({
    iconUrl: markerIcon, iconRetinaUrl: markerIcon2x, shadowUrl: markerShadow,
    iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41],
});

document.addEventListener('alpine:init', () => {
    window.Alpine.data('petaPengaturan', () => ({
        peta: null,
        marker: null,
        lingkaran: null,

        init() {
            const lat = Number(this.$wire.get('officeLat')) || -6.9147;
            const long = Number(this.$wire.get('officeLong')) || 107.6098;
            const radius = Number(this.$wire.get('radiusM')) || 100;

            this.$nextTick(() => {
                this.peta = L.map(this.$refs.peta).setView([lat, long], 16);
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(this.peta);

                this.marker = L.marker([lat, long], { icon: ikon, draggable: true }).addTo(this.peta);
                this.lingkaran = L.circle([lat, long], {
                    radius, color: '#16A34A', fillColor: '#16A34A', fillOpacity: 0.12, weight: 1.5,
                }).addTo(this.peta);
                setTimeout(() => this.peta.invalidateSize(), 150);

                // Seret marker / klik peta → tulis ke Livewire.
                this.marker.on('drag', (e) => this.setDari(e.target.getLatLng()));
                this.peta.on('click', (e) => { this.marker.setLatLng(e.latlng); this.setDari(e.latlng); });

                // Edit field (Livewire berubah) → pindahkan marker + lingkaran.
                this.$watch('$wire.officeLat', () => this.sinkron());
                this.$watch('$wire.officeLong', () => this.sinkron());
                this.$watch('$wire.radiusM', (v) => { if (this.lingkaran) this.lingkaran.setRadius(Number(v) || 0); });
            });
        },

        setDari(latlng) {
            // Deferred (arg ke-3 = false) → update properti tanpa round-trip/re-render (peta tak morph).
            this.$wire.set('officeLat', Number(latlng.lat.toFixed(7)), false);
            this.$wire.set('officeLong', Number(latlng.lng.toFixed(7)), false);
            this.lingkaran.setLatLng(latlng);
        },

        sinkron() {
            const lat = Number(this.$wire.get('officeLat'));
            const long = Number(this.$wire.get('officeLong'));
            if (Number.isNaN(lat) || Number.isNaN(long)) return;
            const cur = this.marker.getLatLng();
            if (Math.abs(cur.lat - lat) < 1e-7 && Math.abs(cur.lng - long) < 1e-7) return; // hindari loop
            this.marker.setLatLng([lat, long]);
            this.lingkaran.setLatLng([lat, long]);
            this.peta.panTo([lat, long]);
        },
    }));
});
