const ClassReserve = {
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',

    async api(endpoint, options = {}) {
        const headers = { ...options.headers };
        if (!(options.body instanceof FormData)) {
            headers['Content-Type'] = 'application/json';
        }
        if (this.csrfToken && options.method && options.method !== 'GET') {
            headers['X-CSRF-Token'] = this.csrfToken;
        }
        const res = await fetch(endpoint, { credentials: 'same-origin', ...options, headers });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Request failed');
        return data;
    },

    initSidebar() {
        const toggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');
        if (toggle && sidebar) {
            toggle.addEventListener('click', () => sidebar.classList.toggle('collapsed'));
        }
        const logoutBtn = document.getElementById('logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                await this.api('/api/auth.php?action=logout', { method: 'POST' });
                window.location.href = '/public/login.php';
            });
        }
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
    ClassReserve.initSidebar();
    ClassReserve.initDrawers();
    ClassReserve.initModals();
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
