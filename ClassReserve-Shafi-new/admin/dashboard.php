<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

$user = currentUser();
$db = getDb();

$stats = [
    'total'    => (int) $db->query('SELECT COUNT(*) FROM bookings')->fetchColumn(),
    'pending'  => (int) $db->query('SELECT COUNT(*) FROM bookings WHERE status = "pending"')->fetchColumn(),
    'rooms'    => (int) $db->query('SELECT COUNT(*) FROM rooms')->fetchColumn(),
    'maint'    => (int) $db->query('SELECT COUNT(*) FROM maintenance_blocks WHERE end_time > NOW()')->fetchColumn(),
];

$stmt = $db->query(
    'SELECT b.*, r.name AS room_name, u.name AS user_name, u.role AS user_role
     FROM bookings b
     JOIN rooms r ON r.id = b.room_id
     JOIN users u ON u.id = b.user_id
     ORDER BY b.created_at DESC LIMIT 8'
);
$recent = $stmt->fetchAll();

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Admin Dashboard</h1>
</div>

<div class="grid-3" style="margin-bottom:1.5rem">
    <div class="stat-card"><div class="stat-value"><?= $stats['total'] ?></div><div class="stat-label">Total Bookings</div></div>
    <div class="stat-card" style="background:linear-gradient(135deg,#d97706,#b45309)"><div class="stat-value"><?= $stats['pending'] ?></div><div class="stat-label">Pending</div></div>
    <div class="stat-card" style="background:linear-gradient(135deg,#16a34a,#15803d)"><div class="stat-value"><?= $stats['rooms'] ?></div><div class="stat-label">Rooms</div></div>
</div>

<div class="grid-2">
    <div class="card">
        <h2>Quick Actions</h2>
        <div class="btn-group" style="flex-direction:column;align-items:flex-start">
            <a href="/admin/bookings.php" class="btn btn-primary">Manage All Bookings</a>
            <a href="/admin/rooms.php" class="btn btn-outline">Manage Rooms</a>
            <a href="/admin/maintenance.php" class="btn btn-outline">Block Rooms (Maintenance)</a>
            <a href="/faculty/approvals.php" class="btn btn-outline">Review Pending Requests</a>
            <a href="/calendar.php" class="btn btn-outline">Event Calendar</a>
        </div>
    </div>
    <div class="card">
        <h2>System Status</h2>
        <p>Active maintenance blocks: <strong><?= $stats['maint'] ?></strong></p>
        <p>Priority order: <strong>Faculty (3) &gt; Club (2) &gt; Student (1)</strong></p>
        <p>Conflict detection runs automatically on every booking and approval.</p>
    </div>
</div>

<div class="card">
    <h2>Recent Activity</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>User</th><th>Room</th><th>Event</th><th>Status</th><th>Created</th></tr></thead>
            <tbody>
                <?php foreach ($recent as $b): ?>
                <tr>
                    <td><?= sanitize($b['user_name']) ?></td>
                    <td><?= sanitize($b['room_name']) ?></td>
                    <td><?= sanitize($b['title']) ?></td>
                    <td><span class="badge <?= statusBadgeClass($b['status']) ?>"><?= sanitize($b['status']) ?></span></td>
                    <td><?= sanitize(formatDateTime($b['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
