// src/js/api/notifications.js
import { apiFetch } from './client.js';

export const NotificationsService = {
    async list(params = {}) {
        const qs = Object.keys(params).map(k => `${encodeURIComponent(k)}=${encodeURIComponent(params[k])}`).join('&');
        const endpoint = qs ? `/notifications/list?${qs}` : '/notifications/list';
        return apiFetch(endpoint, { method: 'GET' });
    },

    async markAsRead(id) {
        const endpoint = `/notifications/read?id=${encodeURIComponent(id)}`;
        return apiFetch(endpoint, { method: 'POST', body: JSON.stringify({}) });
    },

    async markAllAsRead() {
        return apiFetch('/notifications/read-all', { method: 'POST', body: JSON.stringify({}) });
    }
    ,
    async getPreferences() {
        return apiFetch('/users/me/preferences', { method: 'GET' });
    },

    async updatePreferences(data) {
        return apiFetch('/users/me/preferences', { method: 'POST', body: JSON.stringify(data) });
    }
};
