<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

if (currentUser()['role'] !== 'admin') {
    header('Location: /public/dashboard.php');
    exit;
}

$pageTitle = 'Admin Panel';
ob_start();
?>
<div class="page-header">
    <h1>Admin Panel</h1>
    <p>Manage rooms, bookings, and system overview</p>
</div>

<div class="stat-grid" id="stat-grid">
    <div class="stat-card stat-rooms">
        <div class="stat-label">Available Rooms</div>
        <div class="stat-value" id="stat-rooms">—</div>
    </div>
    <div class="stat-card stat-bookings">
        <div class="stat-label">Total Bookings</div>
        <div class="stat-value" id="stat-bookings">—</div>
    </div>
    <div class="stat-card stat-pending">
        <div class="stat-label">Pending Approval</div>
        <div class="stat-value" id="stat-pending">—</div>
    </div>
    <div class="stat-card stat-notices">
        <div class="stat-label">Open Issues</div>
        <div class="stat-value" id="stat-issues">—</div>
    </div>
</div>

<div class="card" style="margin-bottom:20px">
    <h3 class="card-title">Pending Bookings</h3>
    <div style="overflow-x:auto">
        <table class="data-table" id="pending-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Room</th>
                    <th>User</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="pending-body">
                <tr><td colspan="6"><div class="spinner"></div></td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h3 class="card-title">All Rooms</h3>
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Building</th>
                    <th>Floor</th>
                    <th>Capacity</th>
                    <th>Type</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="rooms-body">
                <tr><td colspan="6"><div class="spinner"></div></td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const dash = await ClassReserve.api('/api/dashboard.php');
        document.getElementById('stat-rooms').textContent = dash.stats.rooms;
        document.getElementById('stat-bookings').textContent = dash.stats.bookings;
        document.getElementById('stat-pending').textContent = dash.stats.pending;
        document.getElementById('stat-issues').textContent = dash.stats.issues;

        const bookings = await ClassReserve.api('/api/bookings.php?action=list');
        const pending = bookings.bookings.filter(b => b.status === 'pending');

        const tbody = document.getElementById('pending-body');
        if (pending.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="empty-state">No pending bookings</td></tr>';
        } else {
            tbody.innerHTML = pending.map(b => `
                <tr>
                    <td>${b.title}</td>
                    <td>${b.room_name}</td>
                    <td>${b.user_name}</td>
                    <td>${ClassReserve.formatDate(b.start_datetime)}</td>
                    <td>${ClassReserve.statusBadge(b.status)}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="updateBooking(${b.id},'approved')">Approve</button>
                        <button class="btn btn-sm btn-ghost" onclick="updateBooking(${b.id},'rejected')">Reject</button>
                    </td>
                </tr>
            `).join('');
        }

        const rooms = await ClassReserve.api('/api/rooms.php?action=list');
        document.getElementById('rooms-body').innerHTML = rooms.rooms.map(r => `
            <tr>
                <td><strong>${r.name}</strong></td>
                <td>${r.building}</td>
                <td>${r.floor}</td>
                <td>${r.capacity}</td>
                <td>${r.type}</td>
                <td><span class="status-pill status-${r.status === 'available' ? 'approved' : 'pending'}">${r.status}</span></td>
            </tr>
        `).join('');
    } catch (err) { console.error(err); }
});

async function updateBooking(id, status) {
    try {
        await ClassReserve.api('/api/bookings.php?action=update', {
            method: 'PUT',
            body: JSON.stringify({ id, status })
        });
        location.reload();
    } catch (err) { alert(err.message); }
}
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/includes/layout.php';
