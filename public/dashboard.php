<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$pageTitle = 'Dashboard';
$user = currentUser();
ob_start();
?>
<div class="page-header">
    <h1>Dashboard</h1>
    <p>Welcome back, <?= sanitize($user['name']) ?> — <?= sanitize(rolePortal($user['role'])) ?></p>
</div>

<div class="stat-grid" id="stat-grid">
    <div class="stat-card stat-rooms">
        <div class="stat-icon"><i data-lucide="door-open"></i></div>
        <div class="stat-label">Available Rooms</div>
        <div class="stat-value" id="stat-rooms">—</div>
    </div>
    <div class="stat-card stat-bookings">
        <div class="stat-icon"><i data-lucide="calendar"></i></div>
        <div class="stat-label">My Bookings</div>
        <div class="stat-value" id="stat-bookings">—</div>
    </div>
    <div class="stat-card stat-pending">
        <div class="stat-icon"><i data-lucide="clock"></i></div>
        <div class="stat-label">Pending</div>
        <div class="stat-value" id="stat-pending">—</div>
    </div>
    <div class="stat-card stat-notices">
        <div class="stat-icon"><i data-lucide="bell"></i></div>
        <div class="stat-label">Notices</div>
        <div class="stat-value" id="stat-notices">—</div>
    </div>
</div>

<div class="split-grid">
    <div class="card">
        <h3 class="card-title"><i data-lucide="calendar-days" style="width:16px;display:inline;vertical-align:-2px"></i> Recent Bookings</h3>
        <ul class="activity-list" id="recent-bookings">
            <li class="activity-item"><div class="spinner"></div></li>
        </ul>
    </div>
    <div class="card">
        <h3 class="card-title"><i data-lucide="bell-ring" style="width:16px;display:inline;vertical-align:-2px"></i> Notifications</h3>
        <ul class="activity-list" id="recent-notifications">
            <li class="activity-item"><div class="spinner"></div></li>
        </ul>
    </div>
</div>

<div style="margin-top:24px;display:flex;gap:12px">
    <a href="/public/new-booking.php" class="btn btn-primary"><i data-lucide="plus"></i> New Booking</a>
    <a href="/public/calendar.php" class="btn btn-secondary"><i data-lucide="calendar"></i> View Calendar</a>
    <a href="/public/forum.php" class="btn btn-secondary"><i data-lucide="messages-square"></i> Forum</a>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const data = await ClassReserve.api('/api/dashboard.php');
        const s = data.stats;
        document.getElementById('stat-rooms').textContent = s.rooms ?? 0;
        document.getElementById('stat-bookings').textContent = s.bookings ?? 0;
        document.getElementById('stat-pending').textContent = s.pending ?? 0;
        document.getElementById('stat-notices').textContent = s.notices ?? s.issues ?? 0;

        const bookingsEl = document.getElementById('recent-bookings');
        if (data.recent_bookings.length === 0) {
            bookingsEl.innerHTML = '<li class="empty-state">No bookings yet</li>';
        } else {
            bookingsEl.innerHTML = data.recent_bookings.map(b => `
                <li class="activity-item">
                    <span class="activity-dot dot-${b.status === 'approved' ? 'success' : b.status === 'pending' ? 'pending' : 'error'}"></span>
                    <div style="flex:1">
                        <strong>${b.title}</strong> — ${b.room_name}<br>
                        <span style="font-size:.75rem;color:var(--cr-slate)">${ClassReserve.formatDate(b.start_datetime)} ${ClassReserve.formatTime(b.start_datetime)}</span>
                    </div>
                    ${ClassReserve.statusBadge(b.status)}
                </li>
            `).join('');
        }

        const notifEl = document.getElementById('recent-notifications');
        if (data.recent_notifications.length === 0) {
            notifEl.innerHTML = '<li class="empty-state">No notifications</li>';
        } else {
            notifEl.innerHTML = data.recent_notifications.map(n => `
                <li class="activity-item">
                    <span class="activity-dot dot-${n.type === 'success' ? 'success' : n.type === 'pending' ? 'pending' : n.type === 'error' ? 'error' : 'info'}"></span>
                    <div>
                        <strong>${n.title}</strong><br>
                        <span style="font-size:.8rem;color:var(--cr-slate)">${n.message}</span>
                    </div>
                </li>
            `).join('');
        }
        lucide.createIcons();
    } catch (err) {
        console.error(err);
    }
});
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/includes/layout.php';
