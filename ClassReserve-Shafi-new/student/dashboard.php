<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['student', 'club']);

$user = currentUser();
$db = getDb();

$stmt = $db->prepare('SELECT COUNT(*) FROM bookings WHERE user_id = ?');
$stmt->execute([$user['id']]);
$totalBookings = (int) $stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = "pending"');
$stmt->execute([$user['id']]);
$pendingBookings = (int) $stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = "approved"');
$stmt->execute([$user['id']]);
$approvedBookings = (int) $stmt->fetchColumn();

$stmt = $db->prepare(
    'SELECT b.*, r.name AS room_name, r.building
     FROM bookings b JOIN rooms r ON r.id = b.room_id
     WHERE b.user_id = ? ORDER BY b.start_time DESC LIMIT 5'
);
$stmt->execute([$user['id']]);
$recent = $stmt->fetchAll();

$pageTitle = 'Student Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Welcome, <?= sanitize($user['name']) ?></h1>
    <a href="/student/search.php" class="btn btn-primary">Search Available Rooms</a>
</div>

<div class="grid-3" style="margin-bottom:1.5rem">
    <div class="stat-card">
        <div class="stat-value"><?= $totalBookings ?></div>
        <div class="stat-label">Total Bookings</div>
    </div>
    <div class="stat-card" style="background:linear-gradient(135deg,#d97706,#b45309)">
        <div class="stat-value"><?= $pendingBookings ?></div>
        <div class="stat-label">Pending Approval</div>
    </div>
    <div class="stat-card" style="background:linear-gradient(135deg,#16a34a,#15803d)">
        <div class="stat-value"><?= $approvedBookings ?></div>
        <div class="stat-label">Approved</div>
    </div>
</div>

<div class="card">
    <h2>Recent Bookings</h2>
    <?php if (empty($recent)): ?>
        <div class="empty-state"><p>No bookings yet. <a href="/student/search.php">Search for a room</a></p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Room</th><th>Event</th><th>Time</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $b): ?>
                    <tr>
                        <td><?= sanitize($b['room_name']) ?> (<?= sanitize($b['building']) ?>)</td>
                        <td><?= sanitize($b['title']) ?></td>
                        <td><?= sanitize(formatDateTime($b['start_time'])) ?></td>
                        <td><span class="badge <?= statusBadgeClass($b['status']) ?>"><?= sanitize($b['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <a href="/student/bookings.php" style="margin-top:1rem;display:inline-block">View all bookings &rarr;</a>
    <?php endif; ?>
</div>

<div class="alert alert-info">
    <strong>Booking priority:</strong> Faculty bookings take precedence, followed by Club, then Student requests.
    Your requests require faculty/admin approval.
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
