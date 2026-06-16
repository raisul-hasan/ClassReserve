<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

if (currentUser()['role'] !== 'admin') {
    header('Location: ' . getBaseUrl() . '/public/dashboard.php');
    exit;
}

// Check for overlapping scheduling conflicts today
$db = getDb();
$conflictsQuery = $db->query("
    SELECT b1.id AS id1, b1.title AS title1, b2.id AS id2, b2.title AS title2, r.name AS room_name, b1.start_datetime, b1.end_datetime
    FROM bookings b1
    JOIN bookings b2 ON b1.room_id = b2.room_id AND b1.id < b2.id
    JOIN rooms r ON r.id = b1.room_id
    WHERE b1.status IN ('pending', 'approved')
      AND b2.status IN ('pending', 'approved')
      AND b1.start_datetime < b2.end_datetime
      AND b1.end_datetime > b2.start_datetime
      AND DATE(b1.start_datetime) = CURDATE()
");
$conflicts = $conflictsQuery->fetchAll();
$conflictCount = count($conflicts);

$pageTitle = 'Admin Dashboard';
ob_start();
?>
<div class="page-header">
    <h1>Admin Dashboard</h1>
    <p>System-wide overview of classroom booking system</p>
</div>

<!-- Conflict Alerts -->
<?php if ($conflictCount > 0): ?>
    <div class="card" style="border: 1px solid #ef4444; background: rgba(239, 68, 68, 0.06); border-radius: var(--cr-radius); padding: 18px; margin-bottom: 24px;">
        <div style="display: flex; align-items: flex-start; gap: 14px;">
            <i data-lucide="alert-triangle" style="color: #ef4444; width: 22px; height: 22px; flex-shrink: 0; margin-top: 2px;"></i>
            <div style="flex: 1;">
                <h4 style="color: #ef4444; margin: 0 0 6px; font-weight: 700; font-family: var(--font-body); font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.05em;">
                    <?= $conflictCount ?> Scheduling Conflict<?= $conflictCount > 1 ? 's' : '' ?> Detected Today
                </h4>
                <div style="color: var(--cr-text); font-size: 0.88rem; line-height: 1.6;">
                    <?php foreach ($conflicts as $conflict): ?>
                        <p style="margin: 4px 0;">
                            Room <strong><?= sanitize($conflict['room_name']) ?></strong> has overlapping bookings today: 
                            "<strong><?= sanitize($conflict['title1']) ?></strong>" and "<strong><?= sanitize($conflict['title2']) ?></strong>" 
                            (<?= date('h:i A', strtotime($conflict['start_datetime'])) ?> - <?= date('h:i A', strtotime($conflict['end_datetime'])) ?>). Immediate action required.
                        </p>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="stat-grid">
    <!-- Total Rooms -->
    <div class="stat-card" style="--stat-color: var(--cr-slate); cursor: pointer;" onclick="window.location.href='<?= getBaseUrl() ?>/public/admin-rooms.php'">
        <div class="stat-icon"><i data-lucide="door-open"></i></div>
        <div class="stat-label">Total Rooms</div>
        <div class="stat-value" id="stat-rooms-total">—</div>
    </div>

    <!-- Approved Bookings -->
    <div class="stat-card" style="--stat-color: #508C64;">
        <div class="stat-icon"><i data-lucide="check-circle"></i></div>
        <div class="stat-label">Approved Bookings</div>
        <div class="stat-value" id="stat-bookings-approved">—</div>
    </div>

    <!-- Pending Requests -->
    <div class="stat-card" style="--stat-color: #C07828; cursor: pointer;" onclick="window.location.href='<?= getBaseUrl() ?>/public/admin-approvals.php'">
        <div class="stat-icon"><i data-lucide="clock"></i></div>
        <div class="stat-label">Pending Requests</div>
        <div class="stat-value" id="stat-bookings-pending">—</div>
    </div>

    <!-- Total Users -->
    <div class="stat-card" style="--stat-color: #8b5cf6; cursor: pointer;" onclick="window.location.href='<?= getBaseUrl() ?>/public/admin-users.php'">
        <div class="stat-icon"><i data-lucide="users"></i></div>
        <div class="stat-label">Total Users</div>
        <div class="stat-value" id="stat-active-users">—</div>
    </div>

    <!-- Active Maintenance -->
    <div class="stat-card" style="--stat-color: #f97316; cursor: pointer;" onclick="window.location.href='<?= getBaseUrl() ?>/public/admin-maintenance.php'">
        <div class="stat-icon"><i data-lucide="wrench"></i></div>
        <div class="stat-label">Active Maintenance</div>
        <div class="stat-value" id="stat-maintenance-blocks">—</div>
    </div>

    <!-- Issue Reports -->
    <div class="stat-card" style="--stat-color: #ef4444; cursor: pointer;" onclick="window.location.href='<?= getBaseUrl() ?>/public/forum.php'">
        <div class="stat-icon"><i data-lucide="alert-triangle"></i></div>
        <div class="stat-label">Issue Reports</div>
        <div class="stat-value" id="stat-issues-count">—</div>
    </div>
</div>


<div class="split-grid" style="margin-top: 24px;">
    <!-- Left Column: Bookings and Issues -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <!-- Recent Booking Requests -->
        <div class="card">
            <h3 class="card-title">
                <i data-lucide="activity"></i> Recent Booking Requests
            </h3>
            <div id="recent-bookings-list">
                <div class="spinner"></div>
            </div>
        </div>

        <!-- Issue Reports Summary -->
        <div class="card">
            <h3 class="card-title">
                <i data-lucide="alert-circle"></i> Issue Reports Summary
            </h3>
            <div id="recent-issues-list">
                <div class="spinner"></div>
            </div>
        </div>
    </div>

    <!-- Right Column: Room Availability Summary & Quick Actions -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <div class="card">
            <h3 class="card-title">
                <i data-lucide="door-open"></i> Room Availability Summary
            </h3>
            <div id="room-availability-list">
                <div class="spinner"></div>
            </div>
        </div>

        <div class="card">
            <h3 class="card-title">
                <i data-lucide="settings"></i> Quick Admin Actions
            </h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 16px;">
                <a href="<?= getBaseUrl() ?>/public/admin-approvals.php" class="btn btn-primary" style="justify-content: flex-start;">
                    <i data-lucide="check-square"></i> Review Requests
                </a>
                <a href="<?= getBaseUrl() ?>/public/admin-rooms.php" class="btn btn-secondary" style="justify-content: flex-start;">
                    <i data-lucide="door-open"></i> Manage Rooms
                </a>
                <a href="<?= getBaseUrl() ?>/public/admin-users.php" class="btn btn-secondary" style="justify-content: flex-start;">
                    <i data-lucide="users"></i> Manage Users
                </a>
                <a href="<?= getBaseUrl() ?>/public/admin-maintenance.php" class="btn btn-secondary" style="justify-content: flex-start;">
                    <i data-lucide="wrench"></i> Block Rooms
                </a>
                <a href="<?= getBaseUrl() ?>/public/forum.php" class="btn btn-secondary" style="justify-content: flex-start;">
                    <i data-lucide="messages-square"></i> Issue Reports
                </a>
                <a href="<?= getBaseUrl() ?>/public/admin-audit-logs.php" class="btn btn-secondary" style="justify-content: flex-start;">
                    <i data-lucide="list"></i> Audit Logs
                </a>
            </div>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const [dash, roomsData, issuesData] = await Promise.all([
            ClassReserve.api('/api/dashboard.php'),
            ClassReserve.api('/api/rooms.php?action=list'),
            ClassReserve.api('/api/issues.php?action=list')
        ]);

        // 1. Render Stats
        document.getElementById('stat-rooms-total').textContent = dash.stats.total_rooms ?? 0;
        document.getElementById('stat-bookings-approved').textContent = dash.stats.approved ?? 0;
        document.getElementById('stat-bookings-pending').textContent = dash.stats.pending ?? 0;
        document.getElementById('stat-active-users').textContent = (dash.stats.total_users ?? dash.stats.active_users) ?? 0;
        document.getElementById('stat-maintenance-blocks').textContent = dash.stats.maintenance_blocks ?? 0;
        document.getElementById('stat-issues-count').textContent = dash.stats.issues ?? 0;

        // 2. Render Recent Bookings
        const recentBookingsList = document.getElementById('recent-bookings-list');
        if (!dash.recent_bookings || dash.recent_bookings.length === 0) {
            recentBookingsList.innerHTML = '<div class="empty-state">No recent booking requests</div>';
        } else {
            recentBookingsList.innerHTML = dash.recent_bookings.map(b => {
                const timeStr = ClassReserve.formatDate(b.created_at) + ' ' + ClassReserve.formatTime(b.created_at);
                const userInitial = b.user_name ? b.user_name.charAt(0).toUpperCase() : 'U';
                const actionText = b.status === 'approved' ? 'booked' : b.status === 'pending' ? 'requested' : b.status;
                
                let badgeClass = '';
                if (b.user_role === 'faculty') badgeClass = 'role-faculty';
                else if (b.user_role === 'club') badgeClass = 'role-club';
                else badgeClass = 'role-student';
                
                const roleLabel = b.user_role ? b.user_role.charAt(0).toUpperCase() + b.user_role.slice(1) : 'Student';

                return `
                    <div style="display: flex; align-items: start; gap: 16px; padding-bottom: 16px; margin-bottom: 16px; border-bottom: 1px solid var(--cr-card-border);">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--cr-muted-bg); border: 1px solid var(--cr-card-border); display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--cr-text); flex-shrink: 0;">
                            ${userInitial}
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                <span style="font-weight: 600; color: var(--cr-text);">${ClassReserve.escapeHtml(b.user_name)}</span>
                                <span style="color: var(--cr-muted-fg); font-size: 0.9rem;">${actionText}</span>
                                <span style="font-weight: 600; color: var(--cr-text);">${ClassReserve.escapeHtml(b.room_name)}</span>
                                <span class="role-badge ${badgeClass}" style="margin: 0;">${roleLabel}</span>
                                ${ClassReserve.statusBadge(b.status)}
                            </div>
                            <p style="font-size: 0.8rem; color: var(--cr-muted-fg); margin: 6px 0 0;">${timeStr} — "${ClassReserve.escapeHtml(b.title)}"</p>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // 3. Render Issue Reports Summary
        const recentIssuesList = document.getElementById('recent-issues-list');
        const activeIssues = (issuesData.issues || []).filter(i => i.status !== 'Resolved' && i.status !== 'Rejected').slice(0, 5);
        if (activeIssues.length === 0) {
            recentIssuesList.innerHTML = '<div class="empty-state">No active issue reports</div>';
        } else {
            recentIssuesList.innerHTML = activeIssues.map(i => {
                const createdStr = ClassReserve.formatDate(i.created_at);
                const priorityBadge = ClassReserve.priorityBadge(i.priority);
                const statusBadge = ClassReserve.issueStatusBadge(i.status);

                return `
                    <div style="display: flex; align-items: start; gap: 16px; padding-bottom: 16px; margin-bottom: 16px; border-bottom: 1px solid var(--cr-card-border);">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--cr-muted-bg); border: 1px solid var(--cr-card-border); display: flex; align-items: center; justify-content: center; font-weight: 700; color: #f59e0b; flex-shrink: 0;">
                            <i data-lucide="alert-triangle" style="width: 20px; height: 20px;"></i>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                <strong style="color: var(--cr-text);">${ClassReserve.escapeHtml(i.title)}</strong>
                                <span style="color: var(--cr-muted-fg); font-size: 0.85rem;">on Room ${ClassReserve.escapeHtml(i.room_name)}</span>
                                ${priorityBadge}
                                ${statusBadge}
                            </div>
                            <p style="font-size: 0.8rem; color: var(--cr-muted-fg); margin: 6px 0 0;">Reported by ${ClassReserve.escapeHtml(i.user_name)} on ${createdStr} — ${ClassReserve.escapeHtml(i.description)}</p>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // 4. Render Room Availability Summary
        const roomAvailabilityList = document.getElementById('room-availability-list');
        const roomsList = roomsData.rooms || [];
        if (roomsList.length === 0) {
            roomAvailabilityList.innerHTML = '<div class="empty-state">No rooms configured</div>';
        } else {
            roomAvailabilityList.innerHTML = roomsList.slice(0, 10).map(r => {
                let statusLabel = r.status.charAt(0).toUpperCase() + r.status.slice(1);
                let badgeClass = '';
                if (r.status === 'available') badgeClass = 'status-approved';
                else if (r.status === 'maintenance') badgeClass = 'status-rejected';
                else if (r.status === 'blocked') badgeClass = 'status-pending';
                else badgeClass = 'status-cancelled';

                return `
                    <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px; margin-bottom: 12px; border-bottom: 1px solid var(--cr-card-border);">
                        <div>
                            <strong style="color: var(--cr-text); font-size: 0.95rem; display: block;">${ClassReserve.escapeHtml(r.name)}</strong>
                            <span style="font-size: 0.8rem; color: var(--cr-muted-fg);">${ClassReserve.escapeHtml(r.building)} · Floor ${r.floor} · ${r.capacity} seats</span>
                        </div>
                        <span class="status-pill ${badgeClass}">${statusLabel}</span>
                    </div>
                `;
            }).join('');
        }

        lucide.createIcons();
    } catch (err) {
        console.error(err);
        document.getElementById('recent-bookings-list').innerHTML = '<div class="empty-state">Failed to load dashboard data</div>';
        document.getElementById('recent-issues-list').innerHTML = '<div class="empty-state">Failed to load dashboard data</div>';
        document.getElementById('room-availability-list').innerHTML = '<div class="empty-state">Failed to load dashboard data</div>';
    }
});
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/includes/layout.php';
