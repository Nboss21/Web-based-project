// src/js/core/router.js
import { checkRouteAccess, isAuthenticated, getUserRole } from './auth-guard.js';

class Router {
    constructor() {
        this.routes = {};
        this.currentRoute = null;
        this.appRoot = document.getElementById('app-root');
        
        // Listen to hash changes
        window.addEventListener('hashchange', this.handleLocation.bind(this));
        
        // Setup initial navigation interception
        document.body.addEventListener('click', e => {
            if (e.target.matches('[data-link]')) {
                e.preventDefault();
                this.navigateTo(e.target.getAttribute('href'));
            }
        });
    }

    addRoute(path, viewFunction, roles = ['*']) {
        this.routes[path] = { view: viewFunction, roles };
    }

    navigateTo(path) {
        window.location.hash = path;
    }

    async handleLocation() {
        let path = window.location.hash.slice(1) || '/login';
        if (path === '/') path = '/login'; // Default route
        
        // Redirect authenticated users away from login page
        if (path === '/login' && isAuthenticated()) {
            const role = getUserRole();
            if (role === 'Admin') {
                this.navigateTo('/dashboard/admin');
            } else if (role === 'Technician') {
                this.navigateTo('/dashboard/tech');
            } else {
                this.navigateTo('/dashboard/student');
            }
            return;
        }
        
        let route = this.routes[path];
        
        // Check access
        const access = checkRouteAccess(route);
        if (!access.allowed) {
            this.navigateTo(access.redirect || '/login');
            return;
        }

        this.currentRoute = path;
        
        // Show loading
        this.appRoot.innerHTML = `
            <div class="loading-state">
                <div class="spinner"></div>
                <p>Loading...</p>
            </div>
        `;
        
        try {
            // Render the view
            await route.view(this.appRoot);
            
            // Dispatch event for components to hook into
            document.dispatchEvent(new CustomEvent('route:changed', { detail: { path } }));
        } catch (error) {
            console.error('Error rendering view:', error);
            this.appRoot.innerHTML = `
                <div style="padding: 2rem; color: var(--danger-color); text-align: center;">
                    <h2>Error loading page</h2>
                    <p>${error.message}</p>
                </div>
            `;
        }
    }
}

export const router = new Router();
