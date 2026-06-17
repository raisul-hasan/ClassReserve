<?php

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
