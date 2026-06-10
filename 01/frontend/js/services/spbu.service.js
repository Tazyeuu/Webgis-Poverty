/**
 * spbu.service.js
 * Tanggung Jawab: Komunikasi HTTP ke endpoint API spbu.php
 */
import { CONFIG } from '../config.js';

export const spbuService = {
    getAll: async () => {
        const response = await fetch(`${CONFIG.BASE_URL}/spbu.php`);
        return await response.json();
    },

    save: async (data) => {
        const response = await fetch(`${CONFIG.BASE_URL}/spbu.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return await response.json();
    },

    delete: async (id) => {
        const response = await fetch(`${CONFIG.BASE_URL}/spbu.php?id=${id}`, {
            method: 'DELETE'
        });
        return await response.json();
    }
};
