import { initMap } from './modules/map.js';
import { setupDrawControls } from './modules/draw.js';
import { renderForm } from './modules/form.js';
import { spbuService, jalanService, kavlingService } from './services/api.service.js';

let appMap;
let drawControl;

// Custom SVG Icons Generator
export const createIcon = (svgPath, color) => {
    return L.divIcon({
        className: 'custom-icon',
        html: `<div style="background-color: ${color}; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.5); border: 2px solid rgba(255,255,255,0.8); color: white;">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">${svgPath}</svg>
               </div>`,
        iconSize: [34, 34],
        iconAnchor: [17, 34],
        popupAnchor: [0, -34]
    });
};

export const iconSPBU = (is24h) => createIcon('<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.242-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>', is24h ? '#10B981' : '#EF4444');

window.showToast = (msg, type='success') => {
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = msg;
    document.getElementById('toast-container').appendChild(t);
    setTimeout(() => { t.style.transform='translateX(100%)'; t.style.opacity=0; setTimeout(()=>t.remove(),300); }, 3000);
};

document.addEventListener('DOMContentLoaded', async () => {
    appMap = initMap('map');
    drawControl = setupDrawControls(appMap, handleGeometryCreated);
    await loadAllData();
});

const loadAllData = async () => {
    try {
        const spbu = await spbuService.getAll();
        L.geoJSON(spbu, {
            pointToLayer: (f, latlng) => L.marker(latlng, { icon: iconSPBU(f.properties.buka_24_jam) }),
            onEachFeature: (f, l) => bindPopup(l, 'spbu', f.properties)
        }).addTo(appMap);

        const jalan = await jalanService.getAll();
        L.geoJSON(jalan, { style: { color: '#F59E0B', weight: 4 }, onEachFeature: (f, l) => bindPopup(l, 'jalan', f.properties) }).addTo(appMap);

        const kavling = await kavlingService.getAll();
        L.geoJSON(kavling, { style: { color: '#3B82F6', weight: 2, fillColor: '#3B82F6', fillOpacity: 0.3 }, onEachFeature: (f, l) => bindPopup(l, 'kavling', f.properties) }).addTo(appMap);
    } catch (e) {
        window.showToast("Gagal meload data: "+e.message, 'error');
    }
};

const bindPopup = (layer, type, props) => {
    const ext = props.buka_24_jam !== undefined ? `<p>Buka 24 Jam: <strong style="color:${props.buka_24_jam?'#10B981':'#EF4444'}">${props.buka_24_jam ? 'Ya' : 'Tidak'}</strong></p>` : '';
    layer.bindPopup(`
        <div class="popup-custom">
            <h3>${props.nama}</h3>
            <p>${props.deskripsi || ''}</p>
            ${ext}
            <button class="btn-delete" onclick="window.deleteData('${type}', ${props.id})">Hapus Data</button>
        </div>
    `);
};

window.deleteData = async (type, id) => {
    if(!confirm('Yakin ingin menghapus?')) return;
    try {
        if(type==='spbu') await spbuService.delete(id);
        if(type==='jalan') await jalanService.delete(id);
        if(type==='kavling') await kavlingService.delete(id);
        window.showToast('Data dihapus');
        setTimeout(()=>location.reload(), 800);
    } catch(e) { window.showToast('Gagal hapus', 'error'); }
};

const handleGeometryCreated = (type, geometry, layer) => {
    const tempLayer = L.geoJSON(geometry).addTo(appMap);
    renderForm(type, geometry, async (payload) => {
        try {
            if(type==='spbu') await spbuService.create(payload);
            if(type==='jalan') await jalanService.create(payload);
            if(type==='kavling') await kavlingService.create(payload);
            window.showToast('Berhasil disimpan');
            setTimeout(()=>location.reload(), 800);
        } catch(e) { window.showToast('Gagal simpan', 'error'); }
    }, () => {
        appMap.removeLayer(tempLayer);
    });
};
