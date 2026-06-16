<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

if (currentUser()['role'] !== 'admin') {
    header('Location: ' . getBaseUrl() . '/public/dashboard.php');
    exit;
}

$pageTitle = 'Room Maintenance';
ob_start();
?>
<div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1>Room Maintenance</h1>
        <p>Schedule room blockages and manage maintenance windows</p>
    </div>
</div>

<div class="split-grid" style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; align-items: start;">
    <!-- Schedule Maintenance Card -->
    <div class="card">
        <h3 class="card-title" style="display: flex; align-items: center; gap: 8px; font-size: 1.05rem;">
            <i data-lucide="calendar" style="width: 18px; height: 18px; color: var(--cr-maroon);"></i> Schedule Blockage
        </h3>
        <form id="maintenance-form" style="margin-top: 16px;">
            <div class="form-group">
                <label for="maint-room">Select Room</label>
                <select id="maint-room" class="form-select" required>
                    <option value="" disabled selected>Choose a room...</option>
                </select>
            </div>

            <div class="form-group">
                <label for="maint-start">Start Time</label>
                <input type="datetime-local" id="maint-start" class="form-input" required>
            </div>

            <div class="form-group">
                <label for="maint-end">End Time</label>
                <input type="datetime-local" id="maint-end" class="form-input" required>
            </div>

            <div class="form-group">
                <label for="maint-reason">Reason</label>
                <textarea id="maint-reason" class="form-textarea" required placeholder="Describe the maintenance reason..." style="min-height: 80px;"></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                <i data-lucide="wrench" style="width: 16px; height: 16px;"></i> Block Room
            </button>
        </form>
    </div>

    <!-- Maintenance List -->
    <div>
        <!-- Filter Pills -->
        <div style="display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; align-items: center;">
            <button class="filter-pill active" data-filter="all">All</button>
            <button class="filter-pill" data-filter="active">Active Now</button>
            <button class="filter-pill" data-filter="upcoming">Upcoming</button>
            <button class="filter-pill" data-filter="past">Past</button>
            <button class="btn btn-secondary btn-sm" onclick="loadMaintenanceList()" style="margin-left: auto;">
                <i data-lucide="refresh-cw" style="width: 13px; height: 13px;"></i>
            </button>
        </div>

        <div class="card" style="padding: 0; overflow-x: auto;">
            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid rgba(241, 230, 210, 0.08);">
                        <th style="padding: 14px 18px; text-align: left;">Room</th>
                        <th style="padding: 14px 18px; text-align: left;">Reason</th>
                        <th style="padding: 14px 18px; text-align: left;">Schedule</th>
                        <th style="padding: 14px 18px; text-align: left;">Created By</th>
                        <th style="padding: 14px 18px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="maintenance-list-body">
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 24px;">
                            <div class="spinner"></div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Maintenance Modal -->
<div class="modal-overlay" id="edit-maint-modal">
    <div class="modal" style="max-width: 460px;">
        <h2 style="margin-top: 0;">Edit Maintenance Block</h2>
        <form id="edit-maint-form">
            <input type="hidden" id="edit-maint-id">
            <div class="form-group">
                <label for="edit-maint-room">Room</label>
                <select id="edit-maint-room" class="form-select" required></select>
            </div>
            <div class="form-group">
                <label for="edit-maint-start">Start Time</label>
                <input type="datetime-local" id="edit-maint-start" class="form-input" required>
            </div>
            <div class="form-group">
                <label for="edit-maint-end">End Time</label>
                <input type="datetime-local" id="edit-maint-end" class="form-input" required>
            </div>
            <div class="form-group">
                <label for="edit-maint-reason">Reason</label>
                <textarea id="edit-maint-reason" class="form-textarea" required style="min-height: 80px;"></textarea>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
let roomsList = [];
let currentFilter = 'all';
let allMaintenance = [];

async function initMaintenancePage() {
    try {
        const roomsRes = await ClassReserve.api('/api/rooms.php?action=list');
        roomsList = roomsRes.rooms || [];
        const roomOpts = '<option value="" disabled selected>Choose a room...</option>' +
            roomsList.map(r => `<option value="${r.id}">${ClassReserve.escapeHtml(r.name)} (${ClassReserve.escapeHtml(r.building)})</option>`).join('');
        document.getElementById('maint-room').innerHTML = roomOpts;
        document.getElementById('edit-maint-room').innerHTML = roomOpts;

        await loadMaintenanceList();
    } catch (err) {
        console.error(err);
    }
}

async function loadMaintenanceList() {
    const tbody = document.getElementById('maintenance-list-body');
    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 24px;"><div class="spinner"></div></td></tr>';
    try {
        const params = new URLSearchParams({ action: 'list', filter: currentFilter });
        const res = await ClassReserve.api('/api/maintenance.php?' + params);
        allMaintenance = res.maintenance || [];
        renderMaintenance();
    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 24px; color: var(--cr-slate);">Failed to load maintenance records</td></tr>';
    }
}

function toLocalDatetime(dt) {
    const d = new Date(dt);
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
    return d.toISOString().slice(0, 16);
}

function renderMaintenance() {
    const tbody = document.getElementById('maintenance-list-body');
    if (allMaintenance.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 32px; color: var(--cr-slate);">No maintenance blocks for this filter.</td></tr>';
        return;
    }

    const now = new Date();
    tbody.innerHTML = allMaintenance.map(m => {
        const startDate = new Date(m.start_datetime);
        const endDate   = new Date(m.end_datetime);
        const startStr  = `${ClassReserve.formatDate(m.start_datetime)} ${ClassReserve.formatTime(m.start_datetime)}`;
        const endStr    = `${ClassReserve.formatDate(m.end_datetime)} ${ClassReserve.formatTime(m.end_datetime)}`;

        let statusBadge = '';
        if (now >= startDate && now <= endDate) {
            statusBadge = '<span class="status-pill status-rejected" style="font-size: 0.65rem; margin-left: 6px;">Active</span>';
        } else if (now < startDate) {
            statusBadge = '<span class="status-pill status-pending" style="font-size: 0.65rem; margin-left: 6px;">Upcoming</span>';
        } else {
            statusBadge = '<span class="status-pill status-cancelled" style="font-size: 0.65rem; margin-left: 6px;">Past</span>';
        }

        return `
            <tr style="border-bottom: 1px solid rgba(241, 230, 210, 0.06);">
                <td style="padding: 14px 18px;">
                    <strong style="color: var(--cr-beige); font-size: 0.95rem; display: block;">${ClassReserve.escapeHtml(m.room_name)}${statusBadge}</strong>
                    <span style="font-size: 0.8rem; color: var(--cr-slate);">${ClassReserve.escapeHtml(m.building)}</span>
                </td>
                <td style="padding: 14px 18px; color: var(--cr-beige); font-size: 0.88rem; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${ClassReserve.escapeHtml(m.reason)}">
                    ${ClassReserve.escapeHtml(m.reason)}
                </td>
                <td style="padding: 14px 18px; font-size: 0.8rem; color: var(--cr-slate);">
                    <div><strong style="color: var(--cr-beige);">From:</strong> ${startStr}</div>
                    <div style="margin-top: 2px;"><strong style="color: var(--cr-beige);">To:</strong> ${endStr}</div>
                </td>
                <td style="padding: 14px 18px; font-size: 0.88rem; color: var(--cr-beige);">${ClassReserve.escapeHtml(m.created_by_name || 'Admin')}</td>
                <td style="padding: 14px 18px; text-align: right;">
                    <div style="display: inline-flex; gap: 6px;">
                        <button class="btn btn-secondary btn-sm" onclick="openEditMaintModal(${m.id})" style="display: flex; align-items: center; gap: 4px;">
                            <i data-lucide="edit" style="width: 12px; height: 12px;"></i> Edit
                        </button>
                        <button class="btn btn-ghost btn-sm" onclick="deleteMaintenance(${m.id})" style="color: #c07070; display: flex; align-items: center; gap: 4px;">
                            <i data-lucide="x-circle" style="width: 14px; height: 14px;"></i> End
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    lucide.createIcons();
}

function openEditMaintModal(id) {
    const m = allMaintenance.find(x => x.id === id);
    if (!m) return;
    document.getElementById('edit-maint-id').value     = m.id;
    document.getElementById('edit-maint-room').value   = m.room_id;
    document.getElementById('edit-maint-start').value  = toLocalDatetime(m.start_datetime);
    document.getElementById('edit-maint-end').value    = toLocalDatetime(m.end_datetime);
    document.getElementById('edit-maint-reason').value = m.reason;
    document.getElementById('edit-maint-modal').classList.add('open');
}

document.getElementById('edit-maint-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = parseInt(document.getElementById('edit-maint-id').value);
    try {
        await ClassReserve.api('/api/maintenance.php?action=update', {
            method: 'PUT',
            body: JSON.stringify({
                id,
                room_id:        parseInt(document.getElementById('edit-maint-room').value),
                start_datetime: document.getElementById('edit-maint-start').value.replace('T', ' ') + ':00',
                end_datetime:   document.getElementById('edit-maint-end').value.replace('T', ' ') + ':00',
                reason:         document.getElementById('edit-maint-reason').value,
            })
        });
        document.getElementById('edit-maint-modal').classList.remove('open');
        await loadMaintenanceList();
    } catch (err) { alert(err.message); }
});

async function deleteMaintenance(id) {
    if (!confirm('End this maintenance block and restore room to available?')) return;
    try {
        await ClassReserve.api(`/api/maintenance.php?action=delete&id=${id}`, { method: 'DELETE' });
        await loadMaintenanceList();
    } catch (err) { alert(err.message); }
}

document.addEventListener('DOMContentLoaded', () => {
    const now = new Date();
    const formatDt = (d) => { d.setMinutes(d.getMinutes() - d.getTimezoneOffset()); return d.toISOString().slice(0, 16); };
    document.getElementById('maint-start').value = formatDt(new Date(now));
    const tomorrow = new Date(now);
    tomorrow.setDate(tomorrow.getDate() + 1);
    document.getElementById('maint-end').value = formatDt(tomorrow);

    initMaintenancePage();

    // Filter pills
    document.querySelectorAll('.filter-pill[data-filter]').forEach(pill => {
        pill.addEventListener('click', () => {
            document.querySelectorAll('.filter-pill[data-filter]').forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            currentFilter = pill.dataset.filter;
            loadMaintenanceList();
        });
    });

    document.getElementById('maintenance-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const result = await ClassReserve.api('/api/maintenance.php?action=create', {
                method: 'POST',
                body: JSON.stringify({
                    room_id:        parseInt(document.getElementById('maint-room').value),
                    start_datetime: document.getElementById('maint-start').value.replace('T', ' ') + ':00',
                    end_datetime:   document.getElementById('maint-end').value.replace('T', ' ') + ':00',
                    reason:         document.getElementById('maint-reason').value,
                })
            });
            document.getElementById('maint-reason').value = '';
            if (result.affected_bookings > 0) {
                alert(`Maintenance block created. ${result.affected_bookings} user(s) with overlapping approved bookings have been notified.`);
            }
            currentFilter = 'all';
            document.querySelectorAll('.filter-pill[data-filter]').forEach(p => p.classList.toggle('active', p.dataset.filter === 'all'));
            await loadMaintenanceList();
        } catch (err) { alert(err.message); }
    });
});
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/includes/layout.php';
