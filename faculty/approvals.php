<?php
<<<<<<< HEAD

declare(strict_types=1);

require_once __DIR__ . '/includes/faculty_helpers.php';

$user = requireFacultyUser();
$db = getDb();
$pageTitle = 'Approvals';

$tabs = [
    'pending' => 'Pending Requests',
    'history' => 'Approval History',
];
$tab = (string) ($_GET['tab'] ?? 'pending');
if (!isset($tabs[$tab])) {
    $tab = 'pending';
}

$hasReviewedColumns = facultyHasColumn($db, 'bookings', 'reviewed_by')
    && facultyHasColumn($db, 'bookings', 'reviewed_at');
$hasAuditLogs = facultyTableExists($db, 'audit_logs')
    && facultyHasColumn($db, 'audit_logs', 'user_id')
    && facultyHasColumn($db, 'audit_logs', 'target_id')
    && facultyHasColumn($db, 'audit_logs', 'target_type')
    && facultyHasColumn($db, 'audit_logs', 'action')
    && facultyHasColumn($db, 'audit_logs', 'created_at');

$history = [];
$trackingSource = '';
if ($tab === 'history' && $hasReviewedColumns) {
    $sql = facultyBookingSelectSql($db) . '
        WHERE b.reviewed_by = ?
          AND b.status IN (\'approved\', \'rejected\')
          AND u.role IN (\'student\', \'club\')
        ORDER BY b.reviewed_at DESC';
    $stmt = $db->prepare($sql);
    $stmt->execute([(int) $user['id']]);
    $history = $stmt->fetchAll();
    $trackingSource = 'review columns';
} elseif ($tab === 'history' && $hasAuditLogs) {
    $sql = facultyBookingSelectSql($db) . '
        JOIN audit_logs a ON a.target_id = b.id AND a.target_type = \'booking\'
        WHERE a.user_id = ?
          AND a.action IN (\'booking_approved\', \'booking_rejected\')
          AND u.role IN (\'student\', \'club\')
        ORDER BY a.created_at DESC';
    $stmt = $db->prepare($sql);
    $stmt->execute([(int) $user['id']]);
    $history = $stmt->fetchAll();
    $trackingSource = 'audit logs';
}

ob_start();
?>
<div class="page-header">
    <div>
        <h1>Approvals</h1>
        <p>Review student and club requests, then track your approval history.</p>
    </div>
</div>

<div class="filter-pills" style="margin-bottom:20px">
    <?php foreach ($tabs as $key => $label): ?>
        <a class="filter-pill <?= $tab === $key ? 'active' : '' ?>" style="text-decoration:none" href="/faculty/approvals.php?tab=<?= sanitize($key) ?>"><?= sanitize($label) ?></a>
    <?php endforeach; ?>
</div>

<?php if ($tab === 'pending'): ?>
<div class="card">
    <h3 class="card-title">Pending Requests</h3>
    <div id="approval-error" class="alert alert-error hidden"></div>
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Requester</th>
                    <th>Event</th>
                    <th>Room</th>
                    <th>Date & Time</th>
                    <th>Priority</th>
                    <th>Review Note</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="approvals-body">
                <tr><td colspan="7"><div class="spinner"></div></td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const body = document.getElementById('approvals-body');
    const errorBox = document.getElementById('approval-error');

    function roleLabel(role) {
        if (role === 'club') return 'Club';
        if (role === 'faculty') return 'Faculty';
        return 'Student';
    }

    function roleClass(role) {
        if (role === 'club') return 'role-club';
        if (role === 'faculty') return 'role-faculty';
        return 'role-student';
    }

    function emptyRow(message) {
        return `<tr><td colspan="7" class="empty-state">${message}</td></tr>`;
    }

    function showError(message) {
        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
    }

    function clearError() {
        errorBox.textContent = '';
        errorBox.classList.add('hidden');
    }

    function roomText(booking) {
        const room = ClassReserve.escapeHtml(booking.room_name || 'Room');
        const building = ClassReserve.escapeHtml(booking.building || '');
        return `<strong>${room}</strong>${building ? `<br><span style="font-size:.75rem;color:var(--cr-slate)">${building}</span>` : ''}`;
    }

    async function loadApprovals() {
        clearError();
        body.innerHTML = '<tr><td colspan="7"><div class="spinner"></div></td></tr>';
        try {
            const data = await ClassReserve.api('/api/bookings.php?action=pending');
            const bookings = data.bookings || [];
            if (bookings.length === 0) {
                body.innerHTML = emptyRow('No pending student or club requests.');
                return;
            }

            body.innerHTML = bookings.map(booking => {
                const startsAt = booking.start_datetime || booking.start_time;
                const endsAt = booking.end_datetime || booking.end_time;
                return `
                <tr id="booking-${booking.id}">
                    <td>
                        <strong>${ClassReserve.escapeHtml(booking.user_name || '')}</strong><br>
                        <span class="role-badge ${roleClass(booking.user_role)}">${roleLabel(booking.user_role)}</span>
                    </td>
                    <td>
                        <strong>${ClassReserve.escapeHtml(booking.title || '')}</strong>
                        ${booking.description ? `<br><span style="font-size:.75rem;color:var(--cr-slate)">${ClassReserve.escapeHtml(booking.description)}</span>` : ''}
                    </td>
                    <td>${roomText(booking)}</td>
                    <td>
                        ${ClassReserve.formatDate(startsAt)}<br>
                        <span style="font-size:.75rem;color:var(--cr-slate)">${ClassReserve.formatTime(startsAt)} - ${ClassReserve.formatTime(endsAt)}</span>
                    </td>
                    <td><span class="role-badge ${roleClass(booking.user_role)}">Tier ${Number(booking.priority || 0)}</span></td>
                    <td><input type="text" id="note-${booking.id}" class="form-input" placeholder="Optional note"></td>
                    <td>
                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                            <button type="button" class="btn btn-sm btn-primary" data-action="approved" data-id="${booking.id}">Approve</button>
                            <button type="button" class="btn btn-sm btn-ghost" data-action="rejected" data-id="${booking.id}">Reject</button>
                        </div>
                    </td>
                </tr>
            `;
            }).join('');
            lucide.createIcons();
        } catch (err) {
            body.innerHTML = emptyRow('Could not load pending requests.');
            showError(err.message);
        }
    }

    async function reviewBooking(id, status) {
        clearError();
        const note = document.getElementById(`note-${id}`)?.value.trim() || '';
        try {
            await ClassReserve.api('/api/bookings.php?action=update', {
                method: 'PUT',
                body: JSON.stringify({ id: Number(id), status, note })
            });
            await loadApprovals();
        } catch (err) {
            showError(err.message);
        }
    }

    body.addEventListener('click', (event) => {
        const button = event.target.closest('[data-action][data-id]');
        if (!button) return;
        reviewBooking(button.dataset.id, button.dataset.action);
    });

    loadApprovals();
});
</script>
<?php else: ?>
    <?php if (!$hasReviewedColumns && !$hasAuditLogs): ?>
        <div class="card">
            <div class="empty-state">
                <i data-lucide="badge-check"></i>
                <p>Approval history requires reviewer tracking in the database, such as reviewed_by/reviewed_at columns or audit log entries.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <h3 class="card-title">Approval History</h3>
            <p style="color:var(--cr-slate);font-size:.85rem;margin-top:-8px;margin-bottom:16px">Tracking source: <?= sanitize($trackingSource) ?></p>
            <div style="overflow-x:auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Requester</th>
                            <th>Event</th>
                            <th>Room</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$history): ?>
                            <tr><td colspan="5" class="empty-state">No approval history found for this faculty account.</td></tr>
                        <?php else: ?>
                            <?php foreach ($history as $booking): ?>
                                <tr>
                                    <td><strong><?= sanitize((string) $booking['user_name']) ?></strong><br><?= facultyRoleBadge((string) $booking['user_role']) ?></td>
                                    <td><?= sanitize((string) $booking['title']) ?></td>
                                    <td><?= sanitize((string) $booking['room_name']) ?></td>
                                    <td><?= sanitize(facultyDateTime((string) $booking['start_at'])) ?></td>
                                    <td><?= facultyStatusPill((string) $booking['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../public/includes/layout.php';
=======
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
>>>>>>> origin/Riche01
