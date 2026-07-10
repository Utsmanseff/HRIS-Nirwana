// Peta Leaflet: titik kantor + lingkaran radius + marker posisi live.
// JS/CSS di-bundle Vite (npm). Ubin dari OSM eksternal → online-only (caveat §7.3).
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

const ikon = L.icon({
    iconUrl: markerIcon, iconRetinaUrl: markerIcon2x, shadowUrl: markerShadow,
    iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41],
});

export function buatPeta(el, cfg) {
    const peta = L.map(el, { zoomControl: false, attributionControl: false })
        .setView([cfg.officeLat, cfg.officeLong], 17);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(peta);

    L.circle([cfg.officeLat, cfg.officeLong], {
        radius: cfg.radius, color: '#16A34A', fillColor: '#16A34A', fillOpacity: 0.12, weight: 1.5,
    }).addTo(peta);
    L.marker([cfg.officeLat, cfg.officeLong]).addTo(peta); // kantor

    let titik = null;   // dot biru posisi user
    let halo = null;    // lingkaran akurasi GPS
    return {
        posisi(lat, long, akurasi) {
            if (titik) {
                titik.setLatLng([lat, long]);
            } else {
                titik = L.circleMarker([lat, long], {
                    radius: 6, color: '#fff', weight: 2, fillColor: '#2563EB', fillOpacity: 1,
                }).addTo(peta);
            }
            // Lingkaran akurasi (radius = akurasi meter) — makin besar makin tak akurat.
            const akur = Math.max(akurasi || 0, 1);
            if (halo) {
                halo.setLatLng([lat, long]).setRadius(akur);
            } else {
                halo = L.circle([lat, long], {
                    radius: akur, color: '#2563EB', fillColor: '#2563EB', fillOpacity: 0.1, weight: 1,
                }).addTo(peta);
            }
            peta.panTo([lat, long]);
        },
        invalidate() { setTimeout(() => peta.invalidateSize(), 100); },
    };
}
