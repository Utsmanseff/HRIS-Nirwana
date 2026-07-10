// Peta Leaflet: titik kantor + lingkaran radius + dot posisi live.
// Pakai circleMarker (tanpa gambar) → tak ada ikon default Leaflet yang rusak via Vite.
// Ubin dari OSM eksternal → online-only (caveat §7.3).
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

export function buatPeta(el, cfg) {
    const peta = L.map(el, { zoomControl: false, attributionControl: false })
        .setView([cfg.officeLat, cfg.officeLong], 17);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(peta);

    // Kantor: lingkaran radius (hijau) saja.
    L.circle([cfg.officeLat, cfg.officeLong], {
        radius: cfg.radius, color: '#16A34A', fillColor: '#16A34A', fillOpacity: 0.12, weight: 1.5,
    }).addTo(peta);

    let titik = null; // dot biru posisi user
    return {
        posisi(lat, long) {
            if (titik) {
                titik.setLatLng([lat, long]);
            } else {
                titik = L.circleMarker([lat, long], {
                    radius: 6, color: '#fff', weight: 2, fillColor: '#2563EB', fillOpacity: 1,
                }).addTo(peta);
            }
            peta.panTo([lat, long]);
        },
        invalidate() { setTimeout(() => peta.invalidateSize(), 100); },
    };
}
