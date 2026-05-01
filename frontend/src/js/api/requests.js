// src/js/api/requests.js
import { apiFetch } from './client.js';

function buildQuery(params = {}) {
    const esc = encodeURIComponent;
    return Object.keys(params)
        .filter(k => params[k] !== undefined && params[k] !== null && params[k] !== '')
        .map(k => `${esc(k)}=${esc(params[k])}`)
        .join('&');
}

export const RequestsService = {
    async list(params = {}) {
        const qs = buildQuery(params);
        const endpoint = qs ? `/requests/list?${qs}` : '/requests/list';
        return apiFetch(endpoint, { method: 'GET' });
    },

    async create(formData) {
        // formData should be a FormData instance (supports files)
        const token = localStorage.getItem('jwt_token');
        const headers = token ? { 'Authorization': `Bearer ${token}` } : {};

        // Use fetch directly because apiFetch assumes JSON
        const url = `/backend/api/requests/create.php`;
        const response = await fetch(url, { method: 'POST', body: formData, headers });
        const json = await response.json();
        if (json && json.error) throw new Error(json.error);
        return { success: response.ok, ...json };
    },

    async details(id) {
        const endpoint = `/requests/view?id=${encodeURIComponent(id)}`;
        return apiFetch(endpoint, { method: 'GET' });
    },

    async assign(id, data) {
        const endpoint = `/requests/assign?id=${encodeURIComponent(id)}`;
        return apiFetch(endpoint, { method: 'POST', body: JSON.stringify(data) });
    }
};
