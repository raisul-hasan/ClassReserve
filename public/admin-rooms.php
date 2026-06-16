<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

if (currentUser()['role'] !== 'admin') {
    header('Location: ' . getBaseUrl() . '/public/dashboard.php');
    exit;
}

$pageTitle = 'Manage Rooms';
ob_start();
?>
<div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1>Rooms</h1>
        <p>Browse, configure, and manage classroom spaces</p>
    </div>
    <button class="btn btn-primary" data-modal="add-room-modal"><i data-lucide="plus"></i> Add Room</button>
</div>

<!-- Search and Filters Card -->
<div class="card" style="margin-bottom: 24px; padding: 18px;">
    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 16px; align-items: end;">
        <div class="form-group" style="margin: 0;">
            <label for="room-search">Search Rooms</label>
            <div style="position: relative;">
                <input type="text" id="room-search" class="form-input" placeholder="Search by name or building..." style="padding-left: 38px;">
                <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--cr-slate);"></i>
            </div>
        </div>
        <div class="form-group" style="margin: 0;">
            <label for="filter-capacity">Capacity</label>
            <select id="filter-capacity" class="form-select">
                <option value="all">All Capacities</option>
                <option value="20">20+ seats</option>
                <option value="30">30+ seats</option>
                <option value="40">40+ seats</option>
                <option value="60">60+ seats</option>
            </select>
        </div>
        <div class="form-group" style="margin: 0;">
            <label for="filter-status">Status</label>
            <select id="filter-status" class="form-select">
                <option value="all">All Statuses</option>
                <option value="available">Available</option>
                <option value="blocked">Blocked</option>
                <option value="maintenance">Maintenance</option>
            </select>
        </div>
    </div>
</div>

<!-- Rooms Table Card -->
<div class="card" style="padding: 0; overflow-x: auto;">
    <table class="data-table" id="rooms-table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th style="padding: 14px 18px; text-align: left;">Room</th>
                <th style="padding: 14px 18px; text-align: left;">Type</th>
                <th style="padding: 14px 18px; text-align: left;">Capacity</th>
                <th style="padding: 14px 18px; text-align: left;">Facilities / Notes</th>
                <th style="padding: 14px 18px; text-align: left;">Status</th>
                <th style="padding: 14px 18px; text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody id="rooms-list-body">
            <tr>
                <td colspan="6" style="text-align: center; padding: 24px;">
                    <div class="spinner"></div>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Add Room Modal -->
<div class="modal-overlay" id="add-room-modal">
    <div class="modal" style="max-width: 480px;">
        <h2 style="margin-top: 0;">Add New Room</h2>
        <form id="add-room-form">
            <div class="form-group">
                <label>Room Name</label>
                <input type="text" id="add-room-name" class="form-input" required placeholder="e.g. A105">
            </div>
            <div class="form-group">
                <label>Building</label>
                <input type="text" id="add-room-building" class="form-input" required placeholder="e.g. Science Block">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Floor</label>
                    <input type="number" id="add-room-floor" class="form-input" required value="1" min="-2">
                </div>
                <div class="form-group">
                    <label>Capacity</label>
                    <input type="number" id="add-room-capacity" class="form-input" required value="30" min="1">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Room Type</label>
                    <select id="add-room-type" class="form-select">
                        <option value="Lecture">Lecture</option>
                        <option value="Lab">Lab</option>
                        <option value="Seminar">Seminar</option>
                        <option value="Auditorium">Auditorium</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="add-room-status" class="form-select">
                        <option value="available">Available</option>
                        <option value="blocked">Blocked</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="disabled">Disabled</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Facilities (Comma separated list)</label>
                <input type="text" id="add-room-notes" class="form-input" placeholder="e.g. Projector, AC, Whiteboard">
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Create Room</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Room Modal -->
<div class="modal-overlay" id="edit-room-modal">
    <div class="modal" style="max-width: 480px;">
        <h2 style="margin-top: 0;">Edit Room</h2>
        <form id="edit-room-form">
            <input type="hidden" id="edit-room-id">
            <div class="form-group">
                <label>Room Name</label>
                <input type="text" id="edit-room-name" class="form-input" required>
            </div>
            <div class="form-group">
                <label>Building</label>
                <input type="text" id="edit-room-building" class="form-input" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Floor</label>
                    <input type="number" id="edit-room-floor" class="form-input" required min="-2">
                </div>
                <div class="form-group">
                    <label>Capacity</label>
                    <input type="number" id="edit-room-capacity" class="form-input" required min="1">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Room Type</label>
                    <select id="edit-room-type" class="form-select">
                        <option value="Lecture">Lecture</option>
                        <option value="Lab">Lab</option>
                        <option value="Seminar">Seminar</option>
                        <option value="Auditorium">Auditorium</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="edit-room-status" class="form-select">
                        <option value="available">Available</option>
                        <option value="blocked">Blocked</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="disabled">Disabled</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Facilities (Comma separated list)</label>
                <input type="text" id="edit-room-notes" class="form-input">
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
let allRooms = [];

async function loadRooms() {
    try {
        const res = await ClassReserve.api('/api/rooms.php?action=list');
        allRooms = res.rooms || [];
        renderRooms();
    } catch (err) {
        console.error(err);
        document.getElementById('rooms-list-body').innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 24px; color: var(--cr-slate);">Failed to load rooms</td></tr>';
    }
}

function getStatusBadge(status) {
    const label = status.charAt(0).toUpperCase() + status.slice(1);
    let badgeClass = '';
    if (status === 'available') badgeClass = 'status-approved';
    else if (status === 'maintenance') badgeClass = 'status-rejected';
    else if (status === 'blocked') badgeClass = 'status-pending';
    else badgeClass = 'status-cancelled';
    return `<span class="status-pill ${badgeClass}">${label}</span>`;
}

function renderRooms() {
    const listBody = document.getElementById('rooms-list-body');
    const searchQuery = document.getElementById('room-search').value.toLowerCase();
    const capacityFilter = document.getElementById('filter-capacity').value;
    const statusFilter = document.getElementById('filter-status').value;
    
    const filtered = allRooms.filter(r => {
        if (statusFilter !== 'all' && r.status !== statusFilter) return false;
        if (capacityFilter !== 'all' && r.capacity < parseInt(capacityFilter)) return false;
        
        if (searchQuery) {
            const nameMatch = r.name && r.name.toLowerCase().includes(searchQuery);
            const bldMatch = r.building && r.building.toLowerCase().includes(searchQuery);
            return nameMatch || bldMatch;
        }
        return true;
    });
    
    if (filtered.length === 0) {
        listBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 32px; color: var(--cr-slate);">No rooms match current criteria.</td></tr>';
        return;
    }
    
    listBody.innerHTML = filtered.map(r => {
        const facilities = r.notes ? r.notes.split(',').map(f => `<span class="badge" style="background: var(--cr-glass); font-size: 0.72rem; padding: 2px 6px; border: 1px solid rgba(241,230,210,0.06);">${ClassReserve.escapeHtml(f.trim())}</span>`).join(' ') : '<em style="color: var(--cr-slate); font-size: 0.8rem;">None</em>';
        
        return `
            <tr style="border-bottom: 1px solid rgba(241, 230, 210, 0.06);">
                <td style="padding: 14px 18px;">
                    <strong style="color: var(--cr-beige); font-size: 0.95rem; display: block;">${ClassReserve.escapeHtml(r.name)}</strong>
                    <span style="font-size: 0.8rem; color: var(--cr-slate);">${ClassReserve.escapeHtml(r.building)} · Floor ${r.floor}</span>
                </td>
                <td style="padding: 14px 18px; color: var(--cr-beige); font-size: 0.9rem;">${ClassReserve.escapeHtml(r.type)}</td>
                <td style="padding: 14px 18px; font-size: 0.9rem;">
                    <div style="display: flex; align-items: center; gap: 6px; color: var(--cr-beige);">
                        <i data-lucide="users" style="width: 14px; height: 14px; color: var(--cr-slate);"></i> ${r.capacity}
                    </div>
                </td>
                <td style="padding: 14px 18px;">
                    <div style="display: flex; flex-wrap: wrap; gap: 4px;">${facilities}</div>
                </td>
                <td style="padding: 14px 18px;">${getStatusBadge(r.status)}</td>
                <td style="padding: 14px 18px; text-align: right;">
                    <div style="display: inline-flex; gap: 6px;">
                        <button class="btn btn-secondary btn-sm" onclick="openEditModal(${r.id})">
                            <i data-lucide="edit" style="width: 12px; height: 12px;"></i> Edit
                        </button>
                        <button class="btn btn-ghost btn-sm" onclick="deleteRoom(${r.id})" style="color: #c07070;">
                            <i data-lucide="trash-2" style="width: 12px; height: 12px;"></i> Delete
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
    
    lucide.createIcons();
}

async function deleteRoom(id, force = false) {
    if (!force && !confirm('Are you sure you want to delete this room? This action cannot be undone and will delete all associated bookings.')) return;
    try {
        const url = `/api/rooms.php?action=delete&id=${id}` + (force ? '&force=true' : '');
        await ClassReserve.api(url, {
            method: 'DELETE',
            headers: { 'X-CSRF-Token': ClassReserve.csrfToken }
        });
        await loadRooms();
    } catch (err) {
        if (err.warning === 'active_bookings') {
            if (confirm(err.message)) {
                await deleteRoom(id, true);
            }
        } else {
            alert(err.message || 'An error occurred.');
        }
    }
}

function openEditModal(id) {
    const r = allRooms.find(item => item.id === id);
    if (!r) return;
    
    document.getElementById('edit-room-id').value = r.id;
    document.getElementById('edit-room-name').value = r.name || '';
    document.getElementById('edit-room-building').value = r.building || '';
    document.getElementById('edit-room-floor').value = r.floor ?? 1;
    document.getElementById('edit-room-capacity').value = r.capacity ?? 30;
    document.getElementById('edit-room-type').value = r.type || 'Lecture';
    document.getElementById('edit-room-status').value = r.status || 'available';
    document.getElementById('edit-room-notes').value = r.notes || '';
    
    document.getElementById('edit-room-modal').classList.add('open');
}

document.addEventListener('DOMContentLoaded', () => {
    loadRooms();
    
    document.getElementById('room-search').addEventListener('input', renderRooms);
    document.getElementById('filter-capacity').addEventListener('change', renderRooms);
    document.getElementById('filter-status').addEventListener('change', renderRooms);
    
    document.getElementById('add-room-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            await ClassReserve.api('/api/rooms.php?action=create', {
                method: 'POST',
                body: JSON.stringify({
                    name: document.getElementById('add-room-name').value,
                    building: document.getElementById('add-room-building').value,
                    floor: parseInt(document.getElementById('add-room-floor').value),
                    capacity: parseInt(document.getElementById('add-room-capacity').value),
                    type: document.getElementById('add-room-type').value,
                    status: document.getElementById('add-room-status').value,
                    notes: document.getElementById('add-room-notes').value
                })
            });
            document.getElementById('add-room-modal').classList.remove('open');
            document.getElementById('add-room-form').reset();
            await loadRooms();
        } catch (err) {
            alert(err.message);
        }
    });

    document.getElementById('edit-room-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('edit-room-id').value;
        try {
            await ClassReserve.api(`/api/rooms.php?action=update&id=${id}`, {
                method: 'PUT',
                body: JSON.stringify({
                    name: document.getElementById('edit-room-name').value,
                    building: document.getElementById('edit-room-building').value,
                    floor: parseInt(document.getElementById('edit-room-floor').value),
                    capacity: parseInt(document.getElementById('edit-room-capacity').value),
                    type: document.getElementById('edit-room-type').value,
                    status: document.getElementById('edit-room-status').value,
                    notes: document.getElementById('edit-room-notes').value
                })
            });
            document.getElementById('edit-room-modal').classList.remove('open');
            await loadRooms();
        } catch (err) {
            alert(err.message);
        }
    });
});
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/includes/layout.php';
