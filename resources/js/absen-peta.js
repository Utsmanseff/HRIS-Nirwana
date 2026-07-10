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

    let saya = null;
    return {
        posisi(lat, long) {
            if (saya) saya.setLatLng([lat, long]);
            else saya = L.marker([lat, long], { icon: ikon, opacity: 0.9 }).addTo(peta);
            peta.panTo([lat, long]);
        },
        invalidate() { setTimeout(() => peta.invalidateSize(), 100); },
    };
}
