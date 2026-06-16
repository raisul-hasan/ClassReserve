<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

if (currentUser()['role'] !== 'admin') {
    header('Location: ' . getBaseUrl() . '/public/dashboard.php');
    exit;
}

$pageTitle = 'Audit Logs';
ob_start();
?>
<div class="page-header">
    <h1>Audit Logs</h1>
    <p>Inspect system-wide operations and user actions</p>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 20px; padding: 16px;">
    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 14px; align-items: end; flex-wrap: wrap;">
        <div class="form-group" style="margin: 0;">
            <label for="audit-user-search">User</label>
            <div style="position: relative;">
                <input type="text" id="audit-user-search" class="form-input" placeholder="Search by user name..." style="padding-left: 36px;">
                <i data-lucide="user" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: var(--cr-slate);"></i>
            </div>
        </div>
        <div class="form-group" style="margin: 0;">
            <label for="audit-action-filter">Action Type</label>
            <select id="audit-action-filter" class="form-select">
                <option value="">All Actions</option>
                <option value="booking">Bookings</option>
                <option value="room">Rooms</option>
                <option value="maintenance">Maintenance</option>
                <option value="issue">Issues</option>
                <option value="user">Users</option>
                <option value="login">Login / Logout</option>
            </select>
        </div>
        <div class="form-group" style="margin: 0;">
            <label for="audit-date-from">From Date</label>
            <input type="date" id="audit-date-from" class="form-input">
        </div>
        <div class="form-group" style="margin: 0;">
            <label for="audit-date-to">To Date</label>
            <input type="date" id="audit-date-to" class="form-input">
        </div>
        <div style="padding-bottom: 1px; display: flex; gap: 8px;">
            <button class="btn btn-primary btn-sm" onclick="loadAuditLogs(true)" style="display: flex; align-items: center; gap: 5px;">
                <i data-lucide="search" style="width: 13px; height: 13px;"></i> Filter
            </button>
            <button class="btn btn-secondary btn-sm" onclick="clearFilters()" title="Clear filters">
                <i data-lucide="x" style="width: 13px; height: 13px;"></i>
            </button>
        </div>
    </div>
</div>

<!-- Audit Table Card -->
<div class="card" style="padding: 0; overflow-x: auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid rgba(241,230,210,0.06);">
        <span id="audit-total-count" style="font-size: 0.85rem; color: var(--cr-slate);"></span>
        <div style="display: flex; gap: 8px;">
            <button class="btn btn-ghost btn-sm" id="audit-load-more" onclick="loadMore()" style="display: none;">
                Load More
            </button>
        </div>
    </div>
    <table class="data-table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 1px solid rgba(241, 230, 210, 0.08);">
                <th style="padding: 13px 18px; text-align: left;">Timestamp</th>
                <th style="padding: 13px 18px; text-align: left;">User / Actor</th>
                <th style="padding: 13px 18px; text-align: left;">Action</th>
                <th style="padding: 13px 18px; text-align: left;">Target</th>
                <th style="padding: 13px 18px; text-align: left;">Details</th>
            </tr>
        </thead>
        <tbody id="audit-list-body">
            <tr>
                <td colspan="5" style="text-align: center; padding: 24px;">
                    <div class="spinner"></div>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<script>
const LIMIT = 100;
let currentOffset = 0;
let totalLogs = 0;

async function loadAuditLogs(reset = false) {
    if (reset) currentOffset = 0;
    const tbody = document.getElementById('audit-list-body');
    if (reset) tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 24px;"><div class="spinner"></div></td></tr>';

    try {
        const params = new URLSearchParams({ action: 'list', limit: LIMIT, offset: currentOffset });
        const userSearch   = document.getElementById('audit-user-search').value.trim();
        const actionFilter = document.getElementById('audit-action-filter').value;
        const dateFrom     = document.getElementById('audit-date-from').value;
        const dateTo       = document.getElementById('audit-date-to').value;

        if (userSearch)   params.set('user_search',   userSearch);
        if (actionFilter) params.set('action_filter', actionFilter);
        if (dateFrom)     params.set('date_from',     dateFrom);
        if (dateTo)       params.set('date_to',       dateTo);

        const res = await ClassReserve.api('/api/audit_logs.php?' + params);
        const logs = res.logs || [];
        totalLogs  = res.total || 0;
        currentOffset += logs.length;

        const totalLabel = document.getElementById('audit-total-count');
        totalLabel.textContent = `Showing ${currentOffset} of ${totalLogs} records`;

        const loadMoreBtn = document.getElementById('audit-load-more');
        loadMoreBtn.style.display = currentOffset < totalLogs ? 'block' : 'none';

        if (reset) tbody.innerHTML = '';

        if (logs.length === 0 && currentOffset === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 32px; color: var(--cr-slate);">No audit logs match this filter.</td></tr>';
            return;
        }

        tbody.innerHTML += logs.map(l => {
            const timeStr  = `${ClassReserve.formatDate(l.created_at)} ${ClassReserve.formatTime(l.created_at)}`;
            const userText = l.user_name
                ? ClassReserve.escapeHtml(l.user_name)
                : '<span style="color: var(--cr-slate);">System / Visitor</span>';

            let roleBadge = '';
            if (l.user_role) {
                const cls = l.user_role === 'admin' ? 'role-admin' : l.user_role === 'faculty' ? 'role-faculty' : l.user_role === 'club' ? 'role-club' : 'role-student';
                roleBadge = ` <span class="role-badge ${cls}" style="margin: 0; font-size: 0.6rem; padding: 1px 6px;">${l.user_role.toUpperCase()}</span>`;
            }

            const targetText = l.target_type
                ? `<span class="badge" style="background: var(--cr-glass); font-size: 0.75rem; border: 1px solid rgba(241,230,210,0.06);">${ClassReserve.escapeHtml(l.target_type)} #${l.target_id}</span>`
                : '<span style="color: var(--cr-slate);">—</span>';

            return `
                <tr style="border-bottom: 1px solid rgba(241, 230, 210, 0.06);">
                    <td style="padding: 13px 18px; font-size: 0.85rem; color: var(--cr-slate); white-space: nowrap;">${timeStr}</td>
                    <td style="padding: 13px 18px; font-size: 0.9rem;">
                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                            <strong style="color: var(--cr-beige);">${userText}</strong>
                            ${roleBadge}
                        </div>
                    </td>
                    <td style="padding: 13px 18px; font-size: 0.9rem; font-weight: 600;">${formatAction(l.action)}</td>
                    <td style="padding: 13px 18px;">${targetText}</td>
                    <td style="padding: 13px 18px; font-size: 0.85rem; color: var(--cr-slate); max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                        title="${ClassReserve.escapeHtml(l.details || '')}">
                        ${ClassReserve.escapeHtml(l.details || '—')}
                    </td>
                </tr>
            `;
        }).join('');

        lucide.createIcons();
    } catch (err) {
        document.getElementById('audit-list-body').innerHTML =
            '<tr><td colspan="5" style="text-align: center; padding: 24px; color: var(--cr-slate);">Failed to load audit trail logs</td></tr>';
    }
}

function loadMore() {
    loadAuditLogs(false);
}

function clearFilters() {
    document.getElementById('audit-user-search').value   = '';
    document.getElementById('audit-action-filter').value = '';
    document.getElementById('audit-date-from').value     = '';
    document.getElementById('audit-date-to').value       = '';
    loadAuditLogs(true);
}

function formatAction(action) {
    const map = {
        'login':                '<span style="color: #60a5fa;"><i data-lucide="log-in" style="width:13px;height:13px;display:inline;"></i> Login</span>',
        'logout':               '<span style="color: #94a3b8;"><i data-lucide="log-out" style="width:13px;height:13px;display:inline;"></i> Logout</span>',
        'register':             '<span style="color: #60a5fa;"><i data-lucide="user-plus" style="width:13px;height:13px;display:inline;"></i> Register</span>',
        'create_booking':       '<span style="color: #60a5fa;"><i data-lucide="plus" style="width:13px;height:13px;display:inline;"></i> Create Booking</span>',
        'booking_approved':     '<span style="color: #34d399;"><i data-lucide="check-circle" style="width:13px;height:13px;display:inline;"></i> Approved</span>',
        'booking_rejected':     '<span style="color: #f87171;"><i data-lucide="x-circle" style="width:13px;height:13px;display:inline;"></i> Rejected</span>',
        'booking_cancelled':    '<span style="color: #fb7185;"><i data-lucide="ban" style="width:13px;height:13px;display:inline;"></i> Cancelled</span>',
        'admin_cancel_booking': '<span style="color: #c07070;"><i data-lucide="shield-off" style="width:13px;height:13px;display:inline;"></i> Admin Cancel</span>',
        'create_room':          '<span style="color: #60a5fa;"><i data-lucide="plus-square" style="width:13px;height:13px;display:inline;"></i> Create Room</span>',
        'update_room':          '<span style="color: #34d399;"><i data-lucide="edit-3" style="width:13px;height:13px;display:inline;"></i> Update Room</span>',
        'delete_room':          '<span style="color: #f87171;"><i data-lucide="trash-2" style="width:13px;height:13px;display:inline;"></i> Delete Room</span>',
        'create_maintenance':   '<span style="color: #fbbf24;"><i data-lucide="wrench" style="width:13px;height:13px;display:inline;"></i> Start Maintenance</span>',
        'update_maintenance':   '<span style="color: #fbbf24;"><i data-lucide="edit" style="width:13px;height:13px;display:inline;"></i> Update Maintenance</span>',
        'delete_maintenance':   '<span style="color: #34d399;"><i data-lucide="check" style="width:13px;height:13px;display:inline;"></i> End Maintenance</span>',
        'create_issue':         '<span style="color: #fb923c;"><i data-lucide="alert-circle" style="width:13px;height:13px;display:inline;"></i> Create Issue</span>',
        'update_issue_status':  '<span style="color: #a78bfa;"><i data-lucide="refresh-cw" style="width:13px;height:13px;display:inline;"></i> Update Issue</span>',
        'create_user':          '<span style="color: #60a5fa;"><i data-lucide="user-plus" style="width:13px;height:13px;display:inline;"></i> Create User</span>',
        'update_user':          '<span style="color: #34d399;"><i data-lucide="user-check" style="width:13px;height:13px;display:inline;"></i> Update User</span>',
        'delete_user':          '<span style="color: #f87171;"><i data-lucide="user-x" style="width:13px;height:13px;display:inline;"></i> Delete User</span>',
        'reset_password':       '<span style="color: #fbbf24;"><i data-lucide="key" style="width:13px;height:13px;display:inline;"></i> Reset Password</span>',
    };
    return map[action] || ClassReserve.escapeHtml(action);
}

document.addEventListener('DOMContentLoaded', () => {
    loadAuditLogs(true);
    let searchTimeout;
    document.getElementById('audit-user-search').addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => loadAuditLogs(true), 400);
    });
});
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/includes/layout.php';
