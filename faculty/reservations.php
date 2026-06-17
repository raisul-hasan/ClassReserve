<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/faculty_helpers.php';

$user = requireFacultyUser();
$db = getDb();
[$startColumn, $endColumn] = facultyBookingTimeColumns($db);

$tabs = [
    'active' => 'My Reservations',
    'schedule' => 'My Schedule',
    'history' => 'Booking History',
];
$tab = (string) ($_GET['tab'] ?? 'active');
if (!isset($tabs[$tab])) {
    $tab = 'active';
}

$todayStart = date('Y-m-d 00:00:00');
$todayEnd = date('Y-m-d 23:59:59');

$activeBookings = facultyFetchOwnBookings(
    $db,
    (int) $user['id'],
    'AND b.status IN (\'pending\', \'approved\') AND b.' . facultyIdentifier($endColumn) . ' >= NOW()',
    [],
    'ASC'
);

$todayBookings = facultyFetchOwnBookings(
    $db,
    (int) $user['id'],
    'AND b.status IN (\'pending\', \'approved\') AND b.' . facultyIdentifier($startColumn) . ' < ? AND b.' . facultyIdentifier($endColumn) . ' > ?',
    [$todayEnd, $todayStart],
    'ASC'
);

$upcomingBookings = facultyFetchOwnBookings(
    $db,
    (int) $user['id'],
    'AND b.status IN (\'pending\', \'approved\') AND b.' . facultyIdentifier($startColumn) . ' > ?',
    [$todayEnd],
    'ASC',
    20
);

$historyFilters = [
    'all' => 'All',
    'pending' => 'Pending',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
    'cancelled' => 'Cancelled',
    'today' => 'Today',
    'week' => 'This Week',
    'month' => 'This Month',
];
$filter = (string) ($_GET['filter'] ?? 'all');
if (!isset($historyFilters[$filter])) {
    $filter = 'all';
}

$where = '';
$params = [];
if (in_array($filter, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
    $where = 'AND b.status = ?';
    $params[] = $filter;
} elseif ($filter === 'today') {
    $where = 'AND b.' . facultyIdentifier($startColumn) . ' < ? AND b.' . facultyIdentifier($endColumn) . ' > ?';
    $params[] = $todayEnd;
    $params[] = $todayStart;
} elseif ($filter === 'week') {
    $where = 'AND b.' . facultyIdentifier($startColumn) . ' >= ? AND b.' . facultyIdentifier($startColumn) . ' < ?';
    $params[] = date('Y-m-d 00:00:00', strtotime('monday this week'));
    $params[] = date('Y-m-d 00:00:00', strtotime('monday next week'));
} elseif ($filter === 'month') {
    $where = 'AND b.' . facultyIdentifier($startColumn) . ' >= ? AND b.' . facultyIdentifier($startColumn) . ' < ?';
    $params[] = date('Y-m-01 00:00:00');
    $params[] = date('Y-m-01 00:00:00', strtotime('first day of next month'));
}
$historyBookings = facultyFetchOwnBookings($db, (int) $user['id'], $where, $params, 'DESC');

$pageTitle = 'Reservations';
ob_start();
?>
<div class="page-header">
    <div>
        <h1>Reservations</h1>
        <p>Manage your reservations, schedule, booking history, and printable schedule.</p>
    </div>
    <div style="display:flex;gap:12px;flex-wrap:wrap">
        <a href="/faculty/reserve.php" class="btn btn-primary"><i data-lucide="calendar-plus"></i> Reserve Room</a>
        <a href="/faculty/print-schedule.php" class="btn btn-secondary"><i data-lucide="printer"></i> Print Schedule</a>
    </div>
</div>

<div id="reservation-error" class="alert alert-error hidden"></div>

<div class="filter-pills" style="margin-bottom:20px">
    <?php foreach ($tabs as $key => $label): ?>
        <a class="filter-pill <?= $tab === $key ? 'active' : '' ?>" style="text-decoration:none" href="/faculty/reservations.php?tab=<?= sanitize($key) ?>"><?= sanitize($label) ?></a>
    <?php endforeach; ?>
</div>

<?php if ($tab === 'active'): ?>
    <div class="card">
        <h3 class="card-title">My Reservations</h3>
        <div style="overflow-x:auto">
            <table class="data-table">
                <thead><tr><th>Event</th><th>Room</th><th>Date</th><th>Time</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody id="active-reservations-body">
                    <?php if (!$activeBookings): ?>
                        <tr><td colspan="7" class="empty-state">No active faculty reservations.</td></tr>
                    <?php else: ?>
                        <?php foreach ($activeBookings as $booking): ?>
                            <tr id="booking-<?= (int) $booking['id'] ?>">
                                <td><strong><?= sanitize((string) $booking['title']) ?></strong></td>
                                <td><?= sanitize((string) $booking['room_name']) ?><br><span style="font-size:.75rem;color:var(--cr-slate)"><?= sanitize((string) ($booking['building'] ?? '')) ?></span></td>
                                <td><?= sanitize(facultyDateOnly((string) $booking['start_at'])) ?></td>
                                <td><?= sanitize(facultyTimeRange((string) $booking['start_at'], (string) $booking['end_at'])) ?></td>
                                <td><?= sanitize(facultyBookingType($booking)) ?></td>
                                <td><?= facultyStatusPill((string) $booking['status']) ?></td>
                                <td>
                                    <?php if (($booking['status'] ?? '') === 'pending'): ?>
                                        <button type="button" class="btn btn-sm btn-ghost cancel-booking" data-id="<?= (int) $booking['id'] ?>">Cancel</button>
                                    <?php else: ?>
                                        <span style="color:var(--cr-slate)">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php elseif ($tab === 'schedule'): ?>
    <div class="card" style="margin-bottom:24px">
        <h3 class="card-title">Today</h3>
        <div style="overflow-x:auto">
            <table class="data-table">
                <thead><tr><th>Event</th><th>Room</th><th>Time</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if (!$todayBookings): ?>
                        <tr><td colspan="4" class="empty-state">No faculty bookings scheduled today.</td></tr>
                    <?php else: ?>
                        <?php foreach ($todayBookings as $booking): ?>
                            <tr>
                                <td><strong><?= sanitize((string) $booking['title']) ?></strong><br><span style="font-size:.75rem;color:var(--cr-slate)"><?= sanitize(facultyBookingType($booking)) ?></span></td>
                                <td><?= sanitize((string) $booking['room_name']) ?></td>
                                <td><?= sanitize(facultyTimeRange((string) $booking['start_at'], (string) $booking['end_at'])) ?></td>
                                <td><?= facultyStatusPill((string) $booking['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h3 class="card-title">Upcoming</h3>
        <div style="overflow-x:auto">
            <table class="data-table">
                <thead><tr><th>Event</th><th>Room</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if (!$upcomingBookings): ?>
                        <tr><td colspan="5" class="empty-state">No upcoming faculty bookings.</td></tr>
                    <?php else: ?>
                        <?php foreach ($upcomingBookings as $booking): ?>
                            <tr>
                                <td><strong><?= sanitize((string) $booking['title']) ?></strong></td>
                                <td><?= sanitize((string) $booking['room_name']) ?></td>
                                <td><?= sanitize(facultyDateOnly((string) $booking['start_at'])) ?></td>
                                <td><?= sanitize(facultyTimeRange((string) $booking['start_at'], (string) $booking['end_at'])) ?></td>
                                <td><?= facultyStatusPill((string) $booking['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="filter-pills" style="margin-bottom:20px">
        <?php foreach ($historyFilters as $key => $label): ?>
            <a class="filter-pill <?= $filter === $key ? 'active' : '' ?>" style="text-decoration:none" href="/faculty/reservations.php?tab=history&filter=<?= sanitize($key) ?>"><?= sanitize($label) ?></a>
        <?php endforeach; ?>
    </div>
    <div class="card">
        <h3 class="card-title"><?= sanitize($historyFilters[$filter]) ?> Bookings</h3>
        <div style="overflow-x:auto">
            <table class="data-table">
                <thead><tr><th>Event</th><th>Room</th><th>Date & Time</th><th>Type</th><th>Hours</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if (!$historyBookings): ?>
                        <tr><td colspan="6" class="empty-state">No bookings found for this filter.</td></tr>
                    <?php else: ?>
                        <?php foreach ($historyBookings as $booking): ?>
                            <tr>
                                <td><strong><?= sanitize((string) $booking['title']) ?></strong></td>
                                <td><?= sanitize((string) $booking['room_name']) ?></td>
                                <td><?= sanitize(facultyDateTime((string) $booking['start_at'])) ?><br><span style="font-size:.75rem;color:var(--cr-slate)"><?= sanitize(facultyTimeRange((string) $booking['start_at'], (string) $booking['end_at'])) ?></span></td>
                                <td><?= sanitize(facultyBookingType($booking)) ?></td>
                                <td><?= sanitize((string) facultyDurationHours((string) $booking['start_at'], (string) $booking['end_at'])) ?></td>
                                <td><?= facultyStatusPill((string) $booking['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const errorBox = document.getElementById('reservation-error');
    const showError = (message) => {
        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
    };

    document.querySelectorAll('.cancel-booking').forEach(button => {
        button.addEventListener('click', async () => {
            if (!confirm('Cancel this pending booking?')) return;
            try {
                await ClassReserve.api('/api/bookings.php?action=update', {
                    method: 'PUT',
                    body: JSON.stringify({ id: Number(button.dataset.id), status: 'cancelled' })
                });
                location.reload();
            } catch (err) {
                showError(err.message);
            }
        });
    });
});
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../public/includes/layout.php';
