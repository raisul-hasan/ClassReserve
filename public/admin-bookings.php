<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

if (currentUser()['role'] !== 'admin') {
    header('Location: ' . getBaseUrl() . '/public/dashboard.php');
    exit;
}

$pageTitle = 'Manage Bookings';
ob_start();
?>
<div class="page-header">
    <h1>Bookings</h1>
    <p>View all room booking requests and their status</p>
</div>

<!-- Search and Filter Pills -->
<div class="card" style="margin-bottom: 24px; padding: 18px;">
    <div style="display: grid; grid-template-columns: 2fr 1.5fr; gap: 16px; align-items: end;">
        <div class="form-group" style="margin: 0;">
            <label for="booking-search">Search Bookings</label>
            <div style="position: relative;">
                <input type="text" id="booking-search" class="form-input" placeholder="Search by event, room, or user name..." style="padding-left: 38px;">
                <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--cr-slate);"></i>
            </div>
        </div>
        <div class="form-group" style="margin: 0;">
            <label for="filter-status">Status</label>
            <select id="filter-status" class="form-select">
                <option value="all">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
    </div>
</div>

<!-- Bookings Table Card -->
<div class="card" style="padding: 0; overflow-x: auto;">
    <table class="data-table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 1px solid rgba(241, 230, 210, 0.08);">
                <th style="padding: 14px 18px; text-align: left;">Event Name</th>
                <th style="padding: 14px 18px; text-align: left;">User / Role</th>
                <th style="padding: 14px 18px; text-align: left;">Room</th>
                <th style="padding: 14px 18px; text-align: left;">Time</th>
                <th style="padding: 14px 18px; text-align: left;">Status</th>
            </tr>
        </thead>
        <tbody id="bookings-list-body">
            <tr>
                <td colspan="5" style="text-align: center; padding: 24px;">
                    <div class="spinner"></div>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<script>
let allBookingsList = [];

async function loadBookings() {
    try {
        const res = await ClassReserve.api('/api/bookings.php?action=list');
        allBookingsList = res.bookings || [];
        renderBookings();
    } catch (err) {
        console.error(err);
        document.getElementById('bookings-list-body').innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 24px; color: var(--cr-slate);">Failed to load bookings</td></tr>';
    }
}

function getRoleBadge(role) {
    const roleLabel = role ? role.charAt(0).toUpperCase() + role.slice(1) : 'Student';
    const badgeClass = role === 'faculty' ? 'role-faculty' : (role === 'club' ? 'role-club' : 'role-student');
    return `<span class="badge ${badgeClass}" style="margin: 0;">${roleLabel}</span>`;
}

function renderBookings() {
    const listBody = document.getElementById('bookings-list-body');
    const searchQuery = document.getElementById('booking-search').value.toLowerCase();
    const statusFilter = document.getElementById('filter-status').value;
    
    const filtered = allBookingsList.filter(b => {
        if (statusFilter !== 'all' && b.status !== statusFilter) return false;
        
        if (searchQuery) {
            const titleMatch = b.title && b.title.toLowerCase().includes(searchQuery);
            const userMatch = b.user_name && b.user_name.toLowerCase().includes(searchQuery);
            const roomMatch = b.room_name && b.room_name.toLowerCase().includes(searchQuery);
            return titleMatch || userMatch || roomMatch;
        }
        return true;
    });
    
    if (filtered.length === 0) {
        listBody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 32px; color: var(--cr-slate);">No bookings found.</td></tr>';
        return;
    }
    
    listBody.innerHTML = filtered.map(b => {
        const dateStr = ClassReserve.formatDate(b.start_datetime);
        const timeStr = `${ClassReserve.formatTime(b.start_datetime)} - ${ClassReserve.formatTime(b.end_datetime)}`;
        
        return `
            <tr style="border-bottom: 1px solid rgba(241, 230, 210, 0.06);">
                <td style="padding: 14px 18px;">
                    <strong style="color: var(--cr-beige); font-size: 0.95rem; display: block;">${ClassReserve.escapeHtml(b.title)}</strong>
                    ${b.description ? `<span style="font-size: 0.8rem; color: var(--cr-slate);">${ClassReserve.escapeHtml(b.description)}</span>` : ''}
                </td>
                <td style="padding: 14px 18px;">
                    <div style="display: flex; flex-direction: column; gap: 4px; align-items: flex-start;">
                        <span style="color: var(--cr-beige); font-size: 0.9rem;">${ClassReserve.escapeHtml(b.user_name)}</span>
                        ${getRoleBadge(b.user_role)}
                    </div>
                </td>
                <td style="padding: 14px 18px; color: var(--cr-beige); font-size: 0.9rem;">${ClassReserve.escapeHtml(b.room_name)}</td>
                <td style="padding: 14px 18px; font-size: 0.88rem; color: var(--cr-beige);">
                    <div>${dateStr}</div>
                    <div style="font-size: 0.8rem; color: var(--cr-slate); margin-top: 4px; display: inline-flex; align-items: center; gap: 4px;">
                        <i data-lucide="clock" style="width: 12px; height: 12px;"></i> ${timeStr}
                    </div>
                </td>
                <td style="padding: 14px 18px;">${ClassReserve.statusBadge(b.status)}</td>
            </tr>
        `;
    }).join('');
    
    lucide.createIcons();
}

document.addEventListener('DOMContentLoaded', () => {
    loadBookings();
    
    document.getElementById('booking-search').addEventListener('input', renderBookings);
    document.getElementById('filter-status').addEventListener('change', renderBookings);
});
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/includes/layout.php';
