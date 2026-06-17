<?php
<<<<<<< HEAD

declare(strict_types=1);

require_once __DIR__ . '/includes/faculty_helpers.php';

requireFacultyUser();
header('Location: /faculty');
exit;
=======
require_once __DIR__ . '/../includes/auth.php';
requireRole(['faculty']);

$user = currentUser();
$db = getDb();

$stmt = $db->query('SELECT COUNT(*) FROM bookings WHERE status = "pending"');
$pendingCount = (int) $stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) FROM bookings WHERE user_id = ?');
$stmt->execute([$user['id']]);
$myBookings = (int) $stmt->fetchColumn();

$stmt = $db->query(
    'SELECT b.*, r.name AS room_name, u.name AS user_name, u.role AS user_role
     FROM bookings b
     JOIN rooms r ON r.id = b.room_id
     JOIN users u ON u.id = b.user_id
     WHERE b.status = "pending"
     ORDER BY b.priority DESC, b.created_at ASC LIMIT 5'
);
$pending = $stmt->fetchAll();

$pageTitle = 'Faculty Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Faculty Dashboard</h1>
    <div class="btn-group">
        <a href="/faculty/reserve.php" class="btn btn-primary">Reserve Room</a>
        <a href="/faculty/approvals.php" class="btn btn-outline">View Approvals</a>
    </div>
</div>

<div class="grid-2" style="margin-bottom:1.5rem">
    <div class="stat-card">
        <div class="stat-value"><?= $pendingCount ?></div>
        <div class="stat-label">Pending Approvals</div>
    </div>
    <div class="stat-card" style="background:linear-gradient(135deg,#16a34a,#15803d)">
        <div class="stat-value"><?= $myBookings ?></div>
        <div class="stat-label">My Reservations</div>
    </div>
</div>

<div class="card">
    <h2>Pending Booking Requests</h2>
    <?php if (empty($pending)): ?>
        <div class="empty-state"><p>No pending requests.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Requester</th><th>Room</th><th>Event</th><th>Time</th><th>Priority</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($pending as $b): ?>
                    <tr>
                        <td><?= sanitize($b['user_name']) ?> <span class="badge badge-primary"><?= sanitize(roleLabel($b['user_role'])) ?></span></td>
                        <td><?= sanitize($b['room_name']) ?></td>
                        <td><?= sanitize($b['title']) ?></td>
                        <td><?= sanitize(formatDateTime($b['start_time'])) ?></td>
                        <td><?= (int) $b['priority'] ?></td>
                        <td><a href="/faculty/approvals.php#booking-<?= (int) $b['id'] ?>" class="btn btn-sm btn-primary">Review</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="alert alert-info">
    Faculty bookings are <strong>auto-approved</strong> and have the highest priority (3).
    Club requests = 2, Student requests = 1.
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
>>>>>>> origin/Riche01
