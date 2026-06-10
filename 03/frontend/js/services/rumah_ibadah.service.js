import { CONFIG } from '../config.js';

export const rumahIbadahService = {
    getAll: async () => {
        const res = await fetch(`${CONFIG.BASE_URL}/rumah_ibadah.php`);
        return await res.json();
    },
    save: async (data) => {
        const res = await fetch(`${CONFIG.BASE_URL}/rumah_ibadah.php`, {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data)
        });
        return await res.json();
    },
    delete: async (id) => {
        const res = await fetch(`${CONFIG.BASE_URL}/rumah_ibadah.php?id=${id}`, { method: 'DELETE' });
        return await res.json();
    },
    getJangkauan: async (id, radius = 1) => {
        const res = await fetch(`${CONFIG.BASE_URL}/rumah_ibadah.php?action=jangkauan&id=${id}&radius=${radius}`);
        return await res.json();
    }
};
