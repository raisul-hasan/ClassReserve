<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['student', 'club']);

$user = currentUser();
$db = getDb();

if (isset($_GET['cancel'])) {
    $id = (int) $_GET['cancel'];
    $stmt = $db->prepare('UPDATE bookings SET status = "cancelled" WHERE id = ? AND user_id = ? AND status IN ("pending","approved")');
    $stmt->execute([$id, $user['id']]);
    flash('success', 'Booking cancelled.');
    header('Location: /student/bookings.php');
    exit;
}

$stmt = $db->prepare(
    'SELECT b.*, r.name AS room_name, r.building
     FROM bookings b JOIN rooms r ON r.id = b.room_id
     WHERE b.user_id = ? ORDER BY b.start_time DESC'
);
$stmt->execute([$user['id']]);
$bookings = $stmt->fetchAll();

$pageTitle = 'My Bookings';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>My Bookings</h1>
    <a href="/student/search.php" class="btn btn-primary">New Booking</a>
</div>

<div class="card">
    <?php if (empty($bookings)): ?>
        <div class="empty-state"><p>No bookings yet.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Room</th><th>Event</th><th>Start</th><th>End</th>
                        <th>Attendees</th><th>Status</th><th>File</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td><?= sanitize($b['room_name']) ?></td>
                        <td>
                            <strong><?= sanitize($b['title']) ?></strong>
                            <?php if ($b['description']): ?>
                                <br><small><?= sanitize($b['description']) ?></small>
                            <?php endif; ?>
                            <?php if ($b['review_note']): ?>
                                <br><small style="color:var(--danger)">Note: <?= sanitize($b['review_note']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= sanitize(formatDateTime($b['start_time'])) ?></td>
                        <td><?= sanitize(formatDateTime($b['end_time'])) ?></td>
                        <td><?= (int) $b['attendees'] ?></td>
                        <td><span class="badge <?= statusBadgeClass($b['status']) ?>"><?= sanitize($b['status']) ?></span></td>
                        <td>
                            <?php if ($b['event_file']): ?>
                                <a href="/<?= sanitize($b['event_file']) ?>" target="_blank">View</a>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td>
                            <?php if (in_array($b['status'], ['pending', 'approved'], true)): ?>
                                <a href="?cancel=<?= (int) $b['id'] ?>" class="btn btn-sm btn-danger"
                                   data-confirm="Cancel this booking?">Cancel</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
