// Frontend Configuration
// This file determines the backend API endpoint

export const getApiBaseUrl = () => {
  // Priority order:
  // 1. Check if VITE_API_URL is available (for Vite builds)
  // 2. Check window.ENV.API_URL (for injected environment)
  // 3. Check localStorage (for manual configuration)
  // 4. Default to localhost for development

  if (typeof import.meta !== 'undefined' && import.meta.env?.VITE_API_URL) {
    return import.meta.env.VITE_API_URL;
  }

  if (typeof window !== 'undefined' && window.ENV?.API_URL) {
    return window.ENV.API_URL;
  }

  const stored = localStorage.getItem('API_BASE_URL');
  if (stored) {
    return stored;
  }

  // Default for development
  return 'http://localhost:8000';
};

export const setApiBaseUrl = (url) => {
  localStorage.setItem('API_BASE_URL', url);
  window.location.reload();
};

export const API_BASE_URL = getApiBaseUrl();
