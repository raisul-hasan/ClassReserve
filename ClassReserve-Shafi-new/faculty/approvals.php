<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['faculty', 'admin']);

$user = currentUser();
$db = getDb();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingId = (int) ($_POST['booking_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $note = trim($_POST['review_note'] ?? '');

    $stmt = $db->prepare('SELECT b.*, u.role AS user_role FROM bookings b JOIN users u ON u.id = b.user_id WHERE b.id = ? AND b.status = "pending"');
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();

    if ($booking && in_array($action, ['approve', 'reject'], true)) {
        if ($action === 'approve') {
            $check = checkRoomAvailability(
                (int) $booking['room_id'],
                $booking['start_time'],
                $booking['end_time'],
                (int) $booking['priority'],
                $bookingId
            );
            if (!$check['available']) {
                flash('error', 'Cannot approve — conflict with existing booking or maintenance.');
            } else {
                $stmt = $db->prepare(
                    'UPDATE bookings SET status = "approved", reviewed_by = ?, reviewed_at = NOW(), review_note = ? WHERE id = ?'
                );
                $stmt->execute([$user['id'], $note ?: null, $bookingId]);
                flash('success', 'Booking approved.');
            }
        } else {
            $stmt = $db->prepare(
                'UPDATE bookings SET status = "rejected", reviewed_by = ?, reviewed_at = NOW(), review_note = ? WHERE id = ?'
            );
            $stmt->execute([$user['id'], $note ?: 'Rejected', $bookingId]);
            flash('success', 'Booking rejected.');
        }
    }
    header('Location: /faculty/approvals.php');
    exit;
}

$stmt = $db->query(
    'SELECT b.*, r.name AS room_name, r.building, u.name AS user_name, u.role AS user_role, u.club_name
     FROM bookings b
     JOIN rooms r ON r.id = b.room_id
     JOIN users u ON u.id = b.user_id
     WHERE b.status = "pending"
     ORDER BY b.priority DESC, b.created_at ASC'
);
$pending = $stmt->fetchAll();

$pageTitle = 'Approve Bookings';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Pending Booking Approvals</h1>
</div>

<?php if (empty($pending)): ?>
    <div class="card empty-state"><p>No pending requests to review.</p></div>
<?php else: ?>
    <?php foreach ($pending as $b): ?>
    <div class="card" id="booking-<?= (int) $b['id'] ?>">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem">
            <div>
                <h3><?= sanitize($b['title']) ?></h3>
                <p class="room-meta">
                    <?= sanitize($b['user_name']) ?>
                    <?php if ($b['user_role'] === 'club' && $b['club_name']): ?>
                        (<?= sanitize($b['club_name']) ?>)
                    <?php endif; ?>
                    &middot; <span class="badge badge-primary"><?= sanitize(roleLabel($b['user_role'])) ?></span>
                    &middot; Priority <?= (int) $b['priority'] ?>
                </p>
            </div>
            <span class="badge badge-warning">Pending</span>
        </div>

        <p style="margin:.75rem 0"><strong>Room:</strong> <?= sanitize($b['room_name']) ?> (<?= sanitize($b['building']) ?>)</p>
        <p><strong>Time:</strong> <?= sanitize(formatDateTime($b['start_time'])) ?> &mdash; <?= sanitize(formatDateTime($b['end_time'])) ?></p>
        <p><strong>Attendees:</strong> <?= (int) $b['attendees'] ?></p>
        <?php if ($b['description']): ?>
            <p><strong>Details:</strong> <?= sanitize($b['description']) ?></p>
        <?php endif; ?>
        <?php if ($b['event_file']): ?>
            <p><a href="/<?= sanitize($b['event_file']) ?>" target="_blank">View uploaded event file</a></p>
        <?php endif; ?>

        <?php
        $conflictCheck = checkRoomAvailability(
            (int) $b['room_id'], $b['start_time'], $b['end_time'],
            (int) $b['priority'], (int) $b['id']
        );
        if (!$conflictCheck['available']): ?>
            <div class="alert alert-error" style="margin-top:1rem">
                <strong>Conflict detected:</strong> Cannot approve until conflicts are resolved.
                <ul style="margin-top:.5rem;padding-left:1.25rem">
                    <?php foreach ($conflictCheck['conflicts'] as $c): ?>
                        <li><?= sanitize($c['type']) ?>: <?= sanitize($c['title'] ?? $c['reason'] ?? '') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" style="margin-top:1rem">
            <input type="hidden" name="booking_id" value="<?= (int) $b['id'] ?>">
            <div class="form-group">
                <label for="note-<?= (int) $b['id'] ?>">Review Note (optional)</label>
                <input type="text" id="note-<?= (int) $b['id'] ?>" name="review_note" placeholder="Reason for approval/rejection">
            </div>
            <div class="btn-group">
                <button type="submit" name="action" value="approve" class="btn btn-success"
                    <?= !$conflictCheck['available'] ? 'disabled' : '' ?>>Approve</button>
                <button type="submit" name="action" value="reject" class="btn btn-danger">Reject</button>
            </div>
        </form>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
