import { router } from './core/router.js';
import { AuthService } from './api/auth.js';
import { renderLogin } from './pages/login.js';
import { renderStudentDashboard } from './pages/dashboard-student.js';
import { renderTechDashboard } from './pages/dashboard-tech.js';
import { renderAdminDashboard } from './pages/dashboard-admin.js';
import { renderRequestsPage } from './pages/requests.js';
import { renderUserManagement } from './pages/users.js';

// Setup basic layout UI logic
function updateLayout() {
    const sidebar = document.getElementById('sidebar');
    const header = document.getElementById('header');
    
    if (AuthService.isAuthenticated()) {
        sidebar.classList.remove('hidden');
        header.classList.remove('hidden');
        
        const user = AuthService.getCurrentUser();
        
        // Render Sidebar
        sidebar.innerHTML = `
            <div class="sidebar-header">
                <div class="sidebar-logo">Campus CMS</div>
            </div>
            <div class="sidebar-nav">
                ${user.role === 'Admin' ? '<a href="#/dashboard/admin" class="nav-item" data-link>Admin Dashboard</a>' : ''}
                ${user.role === 'Technician' ? '<a href="#/dashboard/tech" class="nav-item" data-link>Tech Dashboard</a>' : ''}
                ${user.role === 'Student' || user.role === 'Staff' ? '<a href="#/dashboard/student" class="nav-item" data-link>My Dashboard</a>' : ''}
                <a href="#/requests" class="nav-item" data-link>Maintenance Requests</a>
                ${user.role === 'Admin' ? '<a href="#/users" class="nav-item" data-link>User Management</a>' : ''}
            </div>
        `;
        
        // Render Header
        header.innerHTML = `
            <div class="header-left">
                <!-- Mobile toggle button could go here -->
            </div>
            <div class="header-right">
                <button class="theme-toggle" id="theme-toggle" title="Toggle Dark Mode">
                    🌙
                </button>
                <div style="position:relative; margin:0 0.5rem;">
                    <button id="notifications-btn" class="btn btn-ghost" title="Notifications">🔔 <span id="notif-badge" class="badge badge-danger" style="display:none; font-size:0.65rem; vertical-align:top;">0</span></button>
                    <div id="notif-dropdown" class="card" style="position:absolute; right:0; top:2.25rem; width:320px; display:none; z-index:50; max-height:360px; overflow:auto;"></div>
                </div>
                <div class="user-profile" id="user-profile-btn" style="cursor:pointer;">
                    <div class="avatar">${user.name.charAt(0)}</div>
                    <div style="display:flex; flex-direction:column;">
                        <span class="font-medium" style="font-size:0.875rem">${user.name}</span>
                        <span class="text-secondary" style="font-size:0.75rem">${user.role}</span>
                    </div>
                </div>
                <button class="btn btn-secondary" style="padding:0.25rem 0.75rem; font-size:0.75rem;" id="logout-btn">Logout</button>
            </div>
        `;
        
        // Bind header events
        document.getElementById('logout-btn')?.addEventListener('click', () => {
            AuthService.logout();
            router.navigateTo('/login');
            updateLayout();
        });

        // Preferences modal
        document.getElementById('user-profile-btn')?.addEventListener('click', async () => {
            try {
                const { NotificationsService } = await import('./api/notifications.js');
                const res = await NotificationsService.getPreferences();
                const prefs = (res && res.preferences) ? res.preferences : { email: true, push: true };

                showModal({
                    title: 'Notification Preferences',
                    body: `
                        <div class="form-group">
                            <label class="form-label"><input type="checkbox" id="pref-email"> Email Notifications</label>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><input type="checkbox" id="pref-push"> Push/ In-app Notifications</label>
                        </div>
                    `,
                    onShow: () => {
                        document.getElementById('pref-email').checked = !!prefs.email;
                        document.getElementById('pref-push').checked = !!prefs.push;
                    },
                    actions: [
                        {
                            label: 'Save Preferences',
                            onClick: async (e, close) => {
                                const payload = { email: !!document.getElementById('pref-email').checked, push: !!document.getElementById('pref-push').checked };
                                try {
                                    await (await import('./api/notifications.js')).NotificationsService.updatePreferences(payload);
                                    showToast('Preferences saved', 'success');
                                    close();
                                } catch (err) {
                                    console.error(err);
                                    showToast('Failed to save preferences', 'danger');
                                }
                            }
                        }
                    ]
                });
            } catch (e) {
                console.error('Failed to load preferences', e);
            }
        });

        // Notifications: fetch unread count and recent
        (async function loadNotifications() {
            try {
                const { NotificationsService } = await import('./api/notifications.js');
                const res = await NotificationsService.list({ limit: 5 });
                const unread = (res && res.unread_count) ? res.unread_count : 0;
                const list = (res && res.data) ? res.data : [];

                const badge = document.getElementById('notif-badge');
                if (unread > 0) {
                    badge.style.display = 'inline-block';
                    badge.textContent = unread;
                } else {
                    badge.style.display = 'none';
                }

                const dropdown = document.getElementById('notif-dropdown');
                dropdown.innerHTML = list.length ? list.map(n => `
                    <div style="padding:0.5rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; gap:0.5rem; align-items:flex-start;">
                        <div style="flex:1;">
                            <div style="font-size:0.9rem;">${n.message}</div>
                            <div style="font-size:0.75rem; color:var(--muted-color);">${n.created_at || ''}</div>
                        </div>
                        <div>
                            <button class="btn btn-link btn-sm" onclick="(async ()=>{ try{ await (await import('./api/notifications.js')).NotificationsService.markAsRead(${n.id}); const badgeEl=document.getElementById('notif-badge'); badgeEl.textContent = Math.max(0, parseInt(badgeEl.textContent||0)-1); }catch(e){console.error(e);} })()">Mark</button>
                        </div>
                    </div>
                `).join('') : '<div style="padding:1rem;">No notifications</div>';

                const btn = document.getElementById('notifications-btn');
                btn?.addEventListener('click', (e) => {
                    const dd = document.getElementById('notif-dropdown');
                    dd.style.display = dd.style.display === 'block' ? 'none' : 'block';
                });
            } catch (e) {
                console.error('Notifications failed', e);
            }
        })();

        const themeToggle = document.getElementById('theme-toggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                if (isDark) {
                    document.documentElement.removeAttribute('data-theme');
                    localStorage.setItem('theme', 'light');
                    themeToggle.textContent = '🌙';
                } else {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    localStorage.setItem('theme', 'dark');
                    themeToggle.textContent = '☀️';
                }
            });
            // Initial icon state
            if (document.documentElement.getAttribute('data-theme') === 'dark') {
                themeToggle.textContent = '☀️';
            }
        }
    } else {
        sidebar.classList.add('hidden');
        header.classList.add('hidden');
    }
}

// Initialize application
document.addEventListener('DOMContentLoaded', () => {
    // Load saved theme
    if (localStorage.getItem('theme') === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    }

    // Register Routes
    router.addRoute('/login', renderLogin, ['*']);
    
    router.addRoute('/dashboard/admin', renderAdminDashboard, ['Admin']);
    
    router.addRoute('/dashboard/tech', renderTechDashboard, ['Technician', 'Admin']);
    
    router.addRoute('/dashboard/student', renderStudentDashboard, ['Student', 'Staff']);
    
    router.addRoute('/requests', renderRequestsPage, ['Student', 'Staff', 'Technician', 'Admin']);
    
    router.addRoute('/users', renderUserManagement, ['Admin']);

    // Listen for route changes to update layout active states
    document.addEventListener('route:changed', (e) => {
        updateLayout();
        
        // Update active nav item
        const links = document.querySelectorAll('.sidebar-nav .nav-item');
        links.forEach(link => {
            if (link.getAttribute('href') === e.detail.path) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    });

    // Handle unauthorized event from API
    document.addEventListener('auth:unauthorized', () => {
        AuthService.logout();
        router.navigateTo('/login');
    });

    // Start router
    router.handleLocation();
});
