<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/faculty_helpers.php';

$user = requireFacultyUser();
$db = getDb();
[$startColumn] = facultyBookingTimeColumns($db);

$bookings = facultyFetchOwnBookings(
    $db,
    (int) $user['id'],
    'AND b.status IN (\'pending\', \'approved\') AND b.' . facultyIdentifier($startColumn) . ' >= NOW()',
    [],
    'ASC'
);

$pageTitle = 'Print Schedule';
ob_start();
?>
<style>
@media print {
    .sidebar,
    .topbar,
    .print-actions {
        display: none !important;
    }
    .main-content {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    body {
        background: #fff !important;
        color: #111 !important;
    }
    .card {
        border: 0 !important;
        box-shadow: none !important;
    }
    .data-table th,
    .data-table td {
        color: #111 !important;
        border-color: #ccc !important;
    }
}
</style>

<div class="page-header">
    <div>
        <h1>Print Schedule</h1>
        <p>Printable upcoming schedule for <?= sanitize((string) $user['name']) ?>.</p>
    </div>
    <div class="print-actions" style="display:flex;gap:12px;flex-wrap:wrap">
        <button type="button" class="btn btn-primary" onclick="window.print()"><i data-lucide="printer"></i> Print</button>
        <a href="/faculty/schedule.php" class="btn btn-secondary"><i data-lucide="calendar-clock"></i> My Schedule</a>
    </div>
</div>

<div class="card">
    <h3 class="card-title">Upcoming Faculty Bookings</h3>
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Event</th>
                    <th>Room</th>
                    <th>Type</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$bookings): ?>
                    <tr><td colspan="6" class="empty-state">No upcoming bookings to print.</td></tr>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><?= sanitize(facultyDateOnly((string) $booking['start_at'])) ?></td>
                            <td><?= sanitize(facultyTimeRange((string) $booking['start_at'], (string) $booking['end_at'])) ?></td>
                            <td><?= sanitize((string) $booking['title']) ?></td>
                            <td><?= sanitize((string) $booking['room_name']) ?></td>
                            <td><?= sanitize(facultyBookingType($booking)) ?></td>
                            <td><?= facultyStatusPill((string) $booking['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../public/includes/layout.php';
