/**
 * jalan.service.js
 * Tanggung Jawab: Komunikasi HTTP ke endpoint API jalan.php
 */
import { CONFIG } from '../config.js';

export const jalanService = {
    getAll: async () => {
        const response = await fetch(`${CONFIG.BASE_URL}/jalan.php`);
        return await response.json();
    },

    save: async (data) => {
        const response = await fetch(`${CONFIG.BASE_URL}/jalan.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return await response.json();
    },

    delete: async (id) => {
        const response = await fetch(`${CONFIG.BASE_URL}/jalan.php?id=${id}`, {
            method: 'DELETE'
        });
        return await response.json();
    }
};
