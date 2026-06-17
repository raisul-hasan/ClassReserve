const ClassReserve = {
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',
<<<<<<< HEAD
=======
    baseUrl: document.querySelector('meta[name="base-url"]')?.content || '',
>>>>>>> origin/Riche01
    themeStorageKey: 'classreserve.theme',

    getStoredTheme() {
        try {
            const theme = localStorage.getItem(this.themeStorageKey);
            return theme === 'light' || theme === 'dark' ? theme : 'dark';
        } catch {
            return 'dark';
        }
    },

    setTheme(theme) {
        const nextTheme = theme === 'light' ? 'light' : 'dark';
        document.documentElement.dataset.theme = nextTheme;
        document.documentElement.style.colorScheme = nextTheme;
        try {
            localStorage.setItem(this.themeStorageKey, nextTheme);
        } catch {
            // Ignore storage failures so the visible switch still works.
        }

        document.querySelectorAll('[data-theme-toggle]').forEach(toggle => {
            const label = nextTheme === 'dark' ? 'Light' : 'Dark';
            const icon = nextTheme === 'dark' ? 'sun' : 'moon';
            toggle.setAttribute('aria-label', `Switch to ${label.toLowerCase()} mode`);
            toggle.setAttribute('title', `Switch to ${label.toLowerCase()} mode`);
            toggle.innerHTML = `<i data-lucide="${icon}"></i><span>${label}</span>`;
        });

        if (typeof lucide !== 'undefined') lucide.createIcons();
    },

    initTheme() {
        this.setTheme(this.getStoredTheme());
        document.querySelectorAll('[data-theme-toggle]').forEach(toggle => {
            toggle.addEventListener('click', () => {
                const current = document.documentElement.dataset.theme === 'light' ? 'light' : 'dark';
                this.setTheme(current === 'light' ? 'dark' : 'light');
            });
        });
    },

    async api(endpoint, options = {}) {
        const headers = { ...options.headers };
        if (!(options.body instanceof FormData)) {
            headers['Content-Type'] = 'application/json';
        }
        if (this.csrfToken && options.method && options.method !== 'GET') {
            headers['X-CSRF-Token'] = this.csrfToken;
        }
<<<<<<< HEAD
        const res = await fetch(endpoint, { credentials: 'same-origin', ...options, headers });
        const data = await res.json();
=======
        const url = this.baseUrl + endpoint;
        const res = await fetch(url, { credentials: 'same-origin', ...options, headers });
        const contentType = res.headers.get('content-type') || '';
        const data = contentType.includes('application/json')
            ? await res.json()
            : { error: (await res.text()).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim() || 'Request failed' };
>>>>>>> origin/Riche01
        if (!res.ok) throw new Error(data.error || 'Request failed');
        return data;
    },

    initSidebar() {
        const toggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');
        if (toggle && sidebar) {
            const setCollapsed = (collapsed) => {
                sidebar.classList.toggle('collapsed', collapsed);
                toggle.setAttribute('aria-expanded', String(!collapsed));
                toggle.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
                toggle.innerHTML = `<i data-lucide="${collapsed ? 'panel-left-open' : 'panel-left-close'}"></i>`;
                if (typeof lucide !== 'undefined') lucide.createIcons();
                localStorage.setItem('classreserve.sidebarCollapsed', collapsed ? '1' : '0');
            };

            setCollapsed(localStorage.getItem('classreserve.sidebarCollapsed') === '1');
            toggle.addEventListener('click', () => setCollapsed(!sidebar.classList.contains('collapsed')));
        }
        const logoutBtn = document.getElementById('logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                await this.api('/api/auth.php?action=logout', { method: 'POST' });
<<<<<<< HEAD
                window.location.href = '/public/login.php';
=======
                window.location.href = this.baseUrl + '/public/login.php';
>>>>>>> origin/Riche01
            });
        }
    },

    escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    },

    notificationDotClass(type) {
        if (type === 'success') return 'dot-success';
        if (type === 'pending' || type === 'warning') return 'dot-pending';
        if (type === 'error') return 'dot-error';
        return 'dot-info';
    },

    async loadNotifications() {
        const list = document.getElementById('notification-list');
        const badge = document.getElementById('notification-badge');
        if (!list || !badge) return;

        try {
            const [items, count] = await Promise.all([
                this.api('/api/notifications.php?action=list'),
                this.api('/api/notifications.php?action=count')
            ]);
            const notifications = items.notifications || [];
            const unreadCount = count.unread_count || 0;

            badge.textContent = unreadCount > 9 ? '9+' : String(unreadCount);
            badge.classList.toggle('hidden', unreadCount === 0);

            if (notifications.length === 0) {
                list.innerHTML = '<div class="notification-empty">No notifications yet</div>';
                return;
            }

            list.innerHTML = notifications.map(n => {
                const created = n.created_at ? `${this.formatDate(n.created_at)} ${this.formatTime(n.created_at)}` : '';
<<<<<<< HEAD
                return `
                    <button class="notification-item ${Number(n.is_read) ? '' : 'unread'}" type="button" data-id="${n.id}">
=======
                const link = n.link ? ` data-link="${this.escapeHtml(n.link)}"` : '';
                return `
                    <button class="notification-item ${Number(n.is_read) ? '' : 'unread'}" type="button" data-id="${n.id}"${link}>
>>>>>>> origin/Riche01
                        <span class="notification-dot ${this.notificationDotClass(n.type)}"></span>
                        <span class="notification-body">
                            <span class="notification-title">
                                <span>${this.escapeHtml(n.title)}</span>
                                <span class="notification-time">${this.escapeHtml(created)}</span>
                            </span>
                            <span class="notification-message">${this.escapeHtml(n.message)}</span>
                        </span>
                    </button>
                `;
            }).join('');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        } catch (err) {
            list.innerHTML = '<div class="notification-empty">Could not load notifications</div>';
            console.error(err);
        }
    },

    async markNotificationsRead(id = 0) {
        await this.api('/api/notifications.php?action=mark_read', {
            method: 'POST',
            body: JSON.stringify(id ? { id } : {})
        });
        await this.loadNotifications();
    },

    initNotifications() {
        const menu = document.querySelector('.notification-menu');
        const toggle = document.getElementById('notification-toggle');
        const panel = document.getElementById('notification-panel');
        const markRead = document.getElementById('notification-mark-read');
        const list = document.getElementById('notification-list');
        if (!menu || !toggle || !panel || !list) return;

        this.loadNotifications();
<<<<<<< HEAD
=======
        if (localStorage.getItem('classreserve.autoRefreshNotifications') !== '0') {
            window.setInterval(() => this.loadNotifications(), 60000);
        }
>>>>>>> origin/Riche01

        toggle.addEventListener('click', async (e) => {
            e.stopPropagation();
            const open = !menu.classList.contains('open');
            menu.classList.toggle('open', open);
            toggle.setAttribute('aria-expanded', String(open));
            panel.setAttribute('aria-hidden', String(!open));
            if (open) await this.loadNotifications();
        });

        markRead?.addEventListener('click', async (e) => {
            e.stopPropagation();
            await this.markNotificationsRead();
        });

        list.addEventListener('click', async (e) => {
            const item = e.target.closest('.notification-item');
            if (!item) return;
            const id = Number(item.dataset.id || 0);
            if (id && item.classList.contains('unread')) await this.markNotificationsRead(id);
<<<<<<< HEAD
=======
            if (item.dataset.link) window.location.href = ClassReserve.baseUrl + item.dataset.link;
>>>>>>> origin/Riche01
        });

        document.addEventListener('click', (e) => {
            if (!menu.contains(e.target)) {
                menu.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
                panel.setAttribute('aria-hidden', 'true');
            }
        });
    },

    initDrawers() {
        document.querySelectorAll('[data-drawer]').forEach(trigger => {
            trigger.addEventListener('click', () => {
                const id = trigger.dataset.drawer;
                document.getElementById(id)?.classList.add('open');
                document.getElementById(id + '-overlay')?.classList.add('open');
            });
        });
        document.querySelectorAll('.drawer-close, .drawer-overlay').forEach(el => {
            el.addEventListener('click', (e) => {
                if (e.target !== el && !el.classList.contains('drawer-close') && !el.classList.contains('drawer-overlay')) return;
                document.querySelectorAll('.drawer, .drawer-overlay').forEach(d => d.classList.remove('open'));
            });
        });
    },

    initModals() {
        document.querySelectorAll('[data-modal]').forEach(trigger => {
            trigger.addEventListener('click', () => {
                document.getElementById(trigger.dataset.modal)?.classList.add('open');
            });
        });
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) overlay.classList.remove('open');
            });
        });
        document.querySelectorAll('[data-close-modal]').forEach(btn => {
            btn.addEventListener('click', () => {
                btn.closest('.modal-overlay')?.classList.remove('open');
            });
        });
    },

    formatDate(dt) {
        return new Date(dt).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    },

    formatTime(dt) {
        return new Date(dt).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    },

    statusBadge(status) {
        const map = { approved: 'status-approved', pending: 'status-pending', rejected: 'status-rejected', cancelled: 'status-cancelled' };
        return `<span class="status-pill ${map[status] || ''}">${status}</span>`;
    },

    issueStatusBadge(status) {
        const map = { 'Open': 'badge-open', 'Under Review': 'badge-review', 'In Progress': 'badge-progress', 'Resolved': 'badge-resolved', 'Rejected': 'badge-rejected' };
        return `<span class="badge ${map[status] || ''}">${status}</span>`;
    },

    priorityBadge(priority) {
        const map = { Urgent: 'badge-urgent', High: 'badge-high', Medium: 'badge-medium', Low: 'badge-low' };
        return `<span class="badge ${map[priority] || ''}">${priority}</span>`;
    },

    eventChipClass(event) {
        if (event.type === 'maintenance' || event.role === 'maintenance') return 'chip-maintenance';
        if (event.status === 'pending') return 'chip-pending';
        return `chip-${event.role || 'student'}`;
    }
};

document.addEventListener('DOMContentLoaded', () => {
    ClassReserve.initTheme();
    ClassReserve.initSidebar();
    ClassReserve.initNotifications();
    ClassReserve.initDrawers();
    ClassReserve.initModals();
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
