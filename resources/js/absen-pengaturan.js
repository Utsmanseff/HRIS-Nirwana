// Peta drag-marker untuk halaman Pengaturan Absen. Dua-arah dengan Livewire via @entangle.
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
        model: null,
        peta: null,
        marker: null,
        lingkaran: null,

        init(model) {
            this.model = model;
            const lat = model.lat ?? -6.9147;
            const long = model.long ?? 107.6098;

            this.$nextTick(() => {
                this.peta = L.map(this.$refs.peta).setView([lat, long], 16);
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(this.peta);

                this.marker = L.marker([lat, long], { icon: ikon, draggable: true }).addTo(this.peta);
                this.lingkaran = L.circle([lat, long], {
                    radius: model.radius ?? 100, color: '#16A34A', fillColor: '#16A34A', fillOpacity: 0.12, weight: 1.5,
                }).addTo(this.peta);
                setTimeout(() => this.peta.invalidateSize(), 100);

                // Seret marker → update model.
                this.marker.on('drag', (e) => this.setDari(e.target.getLatLng()));
                // Klik peta → pindah marker + update model.
                this.peta.on('click', (e) => { this.marker.setLatLng(e.latlng); this.setDari(e.latlng); });

                // Edit field (model berubah dari Livewire) → pindah marker + lingkaran.
                this.$watch('model.lat', () => this.sinkronDariModel());
                this.$watch('model.long', () => this.sinkronDariModel());
                this.$watch('model.radius', () => { if (this.lingkaran) this.lingkaran.setRadius(Number(this.model.radius) || 0); });
            });
        },

        setDari(latlng) {
            this.model.lat = Number(latlng.lat.toFixed(7));
            this.model.long = Number(latlng.lng.toFixed(7));
            this.lingkaran.setLatLng(latlng);
        },

        sinkronDariModel() {
            const lat = Number(this.model.lat);
            const long = Number(this.model.long);
            if (Number.isNaN(lat) || Number.isNaN(long)) return;
            const cur = this.marker.getLatLng();
            if (Math.abs(cur.lat - lat) < 1e-7 && Math.abs(cur.lng - long) < 1e-7) return; // hindari loop
            this.marker.setLatLng([lat, long]);
            this.lingkaran.setLatLng([lat, long]);
            this.peta.panTo([lat, long]);
        },
    }));
});
