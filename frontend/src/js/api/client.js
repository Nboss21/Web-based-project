// src/js/api/client.js

// Base path to backend API. Change to your dev server if needed.
// Default development server: PHP built-in at http://localhost:8000 serving /api
const API_BASE = (window && window.__API_BASE__) ? window.__API_BASE__ : 'http://localhost:8000/api';

export async function apiFetch(endpoint, options = {}) {
    const token = localStorage.getItem('jwt_token');
    const headers = {
        'Content-Type': 'application/json',
        ...options.headers
    };

    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    // Ensure endpoint starts with '/'
    const path = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
    // Append .php since backend endpoints are PHP scripts
    const url = `${API_BASE}${path}.php`;

    try {
        const response = await fetch(url, { ...options, headers });
        const json = await response.json();

        // Normalize backend response shapes:
        // - error responses: { error: 'message' }
        // - success responses: { message: '...', ...data }
        if (json && json.error) {
            const err = new Error(json.error);
            err.status = response.status;
            throw err;
        }

        const normalized = {
            success: response.ok,
            message: json.message || '',
            // merge any top-level keys (token, user, data, etc.)
            ...json
        };

        return normalized;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}
