<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

if (currentUser()['role'] !== 'admin') {
    header('Location: ' . getBaseUrl() . '/public/dashboard.php');
    exit;
}

$pageTitle = 'Booking Approvals';
ob_start();
?>
<div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1>Approvals</h1>
        <p>Review and manage all classroom booking requests</p>
        <p class="muted-text" style="margin-top: 8px;">
            Pending: <span id="pending-count" style="font-weight: 600; color: var(--cr-beige);">0</span>
        </p>
    </div>
</div>

<!-- Filters Row -->
<div class="card" style="margin-bottom: 20px; padding: 16px;">
    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 16px; align-items: end;">
        <div class="form-group" style="margin: 0;">
            <label for="booking-search">Search</label>
            <div style="position: relative;">
                <input type="text" id="booking-search" class="form-input" placeholder="Search by title, room, or user..." style="padding-left: 38px;">
                <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--cr-slate);"></i>
            </div>
        </div>
        <div class="form-group" style="margin: 0;">
            <label for="status-filter">Status Filter</label>
            <select id="status-filter" class="form-select">
                <option value="pending">Pending Only</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="cancelled">Cancelled</option>
                <option value="">All Requests</option>
            </select>
        </div>
        <div style="padding-bottom: 1px;">
            <button class="btn btn-secondary" onclick="loadApprovals()">
                <i data-lucide="refresh-cw" style="width: 15px; height: 15px;"></i> Refresh
            </button>
        </div>
    </div>
</div>

<!-- Booking Approvals List -->
<div id="approvals-list" style="display: grid; gap: 16px;">
    <div class="spinner"></div>
</div>

<!-- Admin Cancel Modal -->
<div class="modal-overlay" id="cancel-booking-modal">
    <div class="modal" style="max-width: 440px;">
        <h2 style="margin-top: 0;">Cancel Booking</h2>
        <p style="color: var(--cr-slate); font-size: 0.9rem; margin-bottom: 16px;" id="cancel-booking-title"></p>
        <form id="cancel-booking-form">
            <input type="hidden" id="cancel-booking-id">
            <div class="form-group">
                <label>Reason for Cancellation</label>
                <textarea id="cancel-reason" class="form-textarea" placeholder="Describe why this booking is being cancelled..." style="min-height: 80px;"></textarea>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" data-close-modal>Keep Booking</button>
                <button type="submit" class="btn btn-primary" style="background: #c07070;">Cancel Booking</button>
            </div>
        </form>
    </div>
</div>

<script>
let allBookings = [];

function getPriorityBadge(priority, role) {
    const roleLabel = role ? role.charAt(0).toUpperCase() + role.slice(1) : 'Student';
    if (priority >= 3) return `<span class="badge badge-urgent">High Priority — ${roleLabel}</span>`;
    if (priority === 2) return `<span class="badge badge-medium">Medium Priority — ${roleLabel}</span>`;
    return `<span class="badge badge-low">Low Priority — ${roleLabel}</span>`;
}

function findConflict(booking, list) {
    if (booking.status !== 'pending') return null;
    const start = new Date(booking.start_datetime);
    const end   = new Date(booking.end_datetime);
    const overlap = list.find(other => {
        if (other.id === booking.id || other.room_id !== booking.room_id || other.status !== 'approved') return false;
        const oStart = new Date(other.start_datetime);
        const oEnd   = new Date(other.end_datetime);
        return start < oEnd && end > oStart;
    });
    if (overlap) {
        return `Approved booking: "${overlap.title}" by ${overlap.user_name} (${ClassReserve.formatTime(overlap.start_datetime)} – ${ClassReserve.formatTime(overlap.end_datetime)})`;
    }
    return null;
}

async function loadApprovals() {
    const list = document.getElementById('approvals-list');
    list.innerHTML = '<div class="spinner"></div>';
    try {
        const status = document.getElementById('status-filter').value;
        const search = document.getElementById('booking-search').value.trim();
        const params = new URLSearchParams({ action: 'list' });
        if (status) params.set('status', status);
        if (search) params.set('search', search);
        const res = await ClassReserve.api('/api/bookings.php?' + params);
        allBookings = res.bookings || [];
        renderApprovals();
    } catch (err) {
        list.innerHTML = `<div class="card" style="padding: 24px; text-align: center; color: var(--cr-slate);">Failed to load: ${ClassReserve.escapeHtml(err.message)}</div>`;
    }
}

function renderApprovals() {
    const listContainer = document.getElementById('approvals-list');
    const pendingCount  = document.getElementById('pending-count');
    pendingCount.textContent = allBookings.filter(b => b.status === 'pending').length;

    if (allBookings.length === 0) {
        listContainer.innerHTML = '<div class="card" style="padding: 32px; text-align: center;"><p class="muted-text" style="margin: 0;">No booking requests to display.</p></div>';
        return;
    }

    listContainer.innerHTML = allBookings.map(b => {
        const conflict = findConflict(b, allBookings);
        const timeStr  = `${ClassReserve.formatDate(b.start_datetime)} · ${ClassReserve.formatTime(b.start_datetime)} – ${ClassReserve.formatTime(b.end_datetime)}`;
        const submittedAt = ClassReserve.formatDate(b.created_at) + ' ' + ClassReserve.formatTime(b.created_at);
        const cardBorder = conflict ? '#ef4444' : 'rgba(241, 230, 210, 0.1)';
        const cardBg     = conflict ? 'rgba(239, 68, 68, 0.04)' : '';

        const canApprove = b.status === 'pending' && !conflict;
        const canReject  = b.status === 'pending';
        const canCancel  = b.status !== 'cancelled';

        return `
            <div class="card" style="border: 1px solid ${cardBorder}; ${cardBg ? 'background:' + cardBg + ';' : ''} border-radius: var(--cr-radius); padding: 20px 24px;">
                <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <h3 style="margin: 0 0 8px; font-size: 1.1rem; color: var(--cr-beige); font-family: var(--font-body);">${ClassReserve.escapeHtml(b.title)}</h3>
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            ${getPriorityBadge(b.priority, b.user_role)}
                            ${ClassReserve.statusBadge(b.status)}
                            ${conflict ? `<span class="badge badge-urgent" style="display: inline-flex; align-items: center; gap: 4px;"><i data-lucide="alert-triangle" style="width: 11px; height: 11px;"></i> Conflict</span>` : ''}
                        </div>
                    </div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                        <button class="btn btn-secondary btn-sm" onclick="rejectBooking(${b.id})" ${!canReject ? 'disabled' : ''} style="color: #c07070;">
                            <i data-lucide="x" style="width: 13px; height: 13px;"></i> Reject
                        </button>
                        <button class="btn btn-primary btn-sm" onclick="approveBooking(${b.id})" ${!canApprove ? 'disabled' : ''} style="${canApprove ? 'background: #10b981; box-shadow: 0 4px 12px rgba(16,185,129,0.2);' : ''}">
                            <i data-lucide="check" style="width: 13px; height: 13px;"></i> Approve
                        </button>
                        ${canCancel ? `
                        <button class="btn btn-ghost btn-sm" onclick="openCancelModal(${b.id}, '${ClassReserve.escapeHtml(b.title)}')" style="color: var(--cr-slate);">
                            <i data-lucide="ban" style="width: 13px; height: 13px;"></i>
                        </button>` : ''}
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; font-size: 0.85rem; color: var(--cr-slate);">
                    <div>
                        <span style="display: block; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Requested By</span>
                        <strong style="color: var(--cr-beige);">${ClassReserve.escapeHtml(b.user_name)}</strong>
                        <span class="role-badge role-${b.user_role}" style="margin: 0 0 0 6px; font-size: 0.65rem;">${b.user_role}</span>
                    </div>
                    <div>
                        <span style="display: block; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Room</span>
                        <strong style="color: var(--cr-beige);">${ClassReserve.escapeHtml(b.room_name)}</strong>
                        ${b.building ? `<span style="color: var(--cr-slate); font-size: 0.8rem;"> · ${ClassReserve.escapeHtml(b.building)}</span>` : ''}
                    </div>
                    <div>
                        <span style="display: block; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Date & Time</span>
                        <strong style="color: var(--cr-beige);">${timeStr}</strong>
                    </div>
                    <div>
                        <span style="display: block; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Submitted At</span>
                        <strong style="color: var(--cr-beige);">${submittedAt}</strong>
                    </div>
                </div>

                ${b.description ? `<p style="margin: 12px 0 0; font-size: 0.85rem; color: var(--cr-slate);">Description: ${ClassReserve.escapeHtml(b.description)}</p>` : ''}

                ${conflict ? `
                    <div style="margin-top: 14px; padding: 12px 16px; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.15); border-radius: 10px; display: flex; align-items: start; gap: 8px; font-size: 0.85rem;">
                        <i data-lucide="alert-triangle" style="color: #ef4444; width: 16px; height: 16px; flex-shrink: 0; margin-top: 2px;"></i>
                        <div>
                            <strong style="color: #ef4444; display: block; margin-bottom: 2px;">Scheduling Conflict</strong>
                            <span style="color: var(--cr-beige);">${conflict}</span>
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
    }).join('');

    lucide.createIcons();
}

async function approveBooking(id) {
    if (!confirm('Approve this booking request?')) return;
    try {
        await ClassReserve.api('/api/bookings.php?action=update', {
            method: 'PUT',
            body: JSON.stringify({ id, status: 'approved' })
        });
        await loadApprovals();
    } catch (err) { alert(err.message); }
}

async function rejectBooking(id) {
    const note = prompt('Rejection reason (optional, will be sent to the user):') ?? '';
    try {
        await ClassReserve.api('/api/bookings.php?action=update', {
            method: 'PUT',
            body: JSON.stringify({ id, status: 'rejected', admin_note: note })
        });
        await loadApprovals();
    } catch (err) { alert(err.message); }
}

function openCancelModal(id, title) {
    document.getElementById('cancel-booking-id').value = id;
    document.getElementById('cancel-booking-title').textContent = `Booking: "${title}"`;
    document.getElementById('cancel-reason').value = '';
    document.getElementById('cancel-booking-modal').classList.add('open');
}

document.getElementById('cancel-booking-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id     = parseInt(document.getElementById('cancel-booking-id').value);
    const reason = document.getElementById('cancel-reason').value.trim() || 'Cancelled by administrator.';
    try {
        await ClassReserve.api('/api/bookings.php?action=admin_cancel', {
            method: 'POST',
            body: JSON.stringify({ id, reason })
        });
        document.getElementById('cancel-booking-modal').classList.remove('open');
        await loadApprovals();
    } catch (err) { alert(err.message); }
});

document.addEventListener('DOMContentLoaded', () => {
    loadApprovals();

    let searchTimeout;
    document.getElementById('booking-search').addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(loadApprovals, 350);
    });
    document.getElementById('status-filter').addEventListener('change', loadApprovals);
});
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/includes/layout.php';
