import { CONFIG } from '../config.js';

export const wargaMiskinService = {
    getAll: async () => {
        const res = await fetch(`${CONFIG.BASE_URL}/warga_miskin.php`);
        return await res.json();
    },
    save: async (data) => {
        const res = await fetch(`${CONFIG.BASE_URL}/warga_miskin.php`, {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data)
        });
        return await res.json();
    },
    delete: async (id) => {
        const res = await fetch(`${CONFIG.BASE_URL}/warga_miskin.php?id=${id}`, { method: 'DELETE' });
        return await res.json();
    }
};
