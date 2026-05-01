// src/js/core/auth-guard.js
import { AuthService } from '../api/auth.js';

export function isAuthenticated() {
    return AuthService.isAuthenticated();
}

export function getUserRole() {
    const user = AuthService.getCurrentUser();
    return user ? user.role : null;
}

export function checkRouteAccess(route) {
    if (!route) return { allowed: false, redirect: '/404' };

    // Public routes or routes that allow any role
    if (!route.roles || route.roles.includes('*')) {
        return { allowed: true };
    }

    // Require authentication
    if (!isAuthenticated()) {
        return { allowed: false, redirect: '/login' };
    }

    // Check specific roles
    const userRole = getUserRole();
    if (route.roles.includes(userRole)) {
        return { allowed: true };
    }

    // Authenticated but unauthorized role
    return { allowed: false, redirect: '/unauthorized' };
}
