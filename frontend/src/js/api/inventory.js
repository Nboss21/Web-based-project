// src/js/api/inventory.js
import { apiFetch } from './client.js';

function buildQuery(params = {}) {
    const esc = encodeURIComponent;
    return Object.keys(params)
        .filter(k => params[k] !== undefined && params[k] !== null && params[k] !== '')
        .map(k => `${esc(k)}=${esc(params[k])}`)
        .join('&');
}

export const InventoryService = {
    async list(params = {}) {
        const qs = buildQuery(params);
        const endpoint = qs ? `/inventory/list?${qs}` : '/inventory/list';
        return apiFetch(endpoint, { method: 'GET' });
    },

    async create(data) {
        return apiFetch('/inventory/create', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    async update(id, data) {
        const endpoint = `/inventory/update?id=${encodeURIComponent(id)}`;
        return apiFetch(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    async adjust(id, adjustment) {
        const endpoint = `/inventory/adjust?id=${encodeURIComponent(id)}`;
        return apiFetch(endpoint, {
            method: 'POST',
            body: JSON.stringify(adjustment)
        });
    }
};
