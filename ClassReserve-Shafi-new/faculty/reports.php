<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/faculty_helpers.php';

$user = requireFacultyUser();
$db = getDb();
[$startColumn, $endColumn] = facultyBookingTimeColumns($db);

$monthStart = date('Y-m-01 00:00:00');
$monthEnd = date('Y-m-01 00:00:00', strtotime('first day of next month'));

$statsSql = '
    SELECT
        COUNT(*) AS total_bookings,
        SUM(CASE WHEN status = \'approved\' THEN 1 ELSE 0 END) AS approved_bookings,
        SUM(CASE WHEN status = \'pending\' THEN 1 ELSE 0 END) AS pending_bookings,
        SUM(CASE WHEN status = \'rejected\' THEN 1 ELSE 0 END) AS rejected_bookings,
        ROUND(COALESCE(SUM(TIMESTAMPDIFF(MINUTE, ' . facultyIdentifier($startColumn) . ', ' . facultyIdentifier($endColumn) . ')), 0) / 60, 2) AS total_hours
    FROM bookings
    WHERE user_id = ?
      AND ' . facultyIdentifier($startColumn) . ' >= ?
      AND ' . facultyIdentifier($startColumn) . ' < ?';
$stmt = $db->prepare($statsSql);
$stmt->execute([(int) $user['id'], $monthStart, $monthEnd]);
$stats = $stmt->fetch() ?: [];

$stmt = $db->prepare('
    SELECT r.name, COUNT(*) AS booking_count
    FROM bookings b
    JOIN rooms r ON r.id = b.room_id
    WHERE b.user_id = ?
      AND b.' . facultyIdentifier($startColumn) . ' >= ?
      AND b.' . facultyIdentifier($startColumn) . ' < ?
    GROUP BY r.id, r.name
    ORDER BY booking_count DESC, r.name ASC
    LIMIT 1
');
$stmt->execute([(int) $user['id'], $monthStart, $monthEnd]);
$mostUsedRoom = $stmt->fetch();

$mostCommonType = null;
if (facultyHasColumn($db, 'bookings', 'purpose')) {
    $stmt = $db->prepare('
        SELECT COALESCE(NULLIF(purpose, \'\'), \'Other\') AS purpose_label, COUNT(*) AS booking_count
        FROM bookings
        WHERE user_id = ?
          AND ' . facultyIdentifier($startColumn) . ' >= ?
          AND ' . facultyIdentifier($startColumn) . ' < ?
        GROUP BY purpose_label
        ORDER BY booking_count DESC, purpose_label ASC
        LIMIT 1
    ');
    $stmt->execute([(int) $user['id'], $monthStart, $monthEnd]);
    $mostCommonType = $stmt->fetch();
}

$buildingField = facultyHasColumn($db, 'rooms', 'building') ? 'MAX(r.building) AS building' : "'' AS building";
$typeField = facultyHasColumn($db, 'rooms', 'type') ? 'MAX(r.type) AS room_type' : "'' AS room_type";
$capacityField = facultyHasColumn($db, 'rooms', 'capacity') ? 'MAX(r.capacity) AS capacity' : '0 AS capacity';
$stmt = $db->prepare('
    SELECT
        r.id,
        r.name AS room_name,
        ' . $buildingField . ',
        ' . $typeField . ',
        ' . $capacityField . ',
        COUNT(*) AS booking_count,
        SUM(CASE WHEN b.status = \'approved\' THEN 1 ELSE 0 END) AS approved_count,
        SUM(CASE WHEN b.status = \'pending\' THEN 1 ELSE 0 END) AS pending_count,
        ROUND(COALESCE(SUM(TIMESTAMPDIFF(MINUTE, b.' . facultyIdentifier($startColumn) . ', b.' . facultyIdentifier($endColumn) . ')), 0) / 60, 2) AS total_hours,
        MAX(b.' . facultyIdentifier($startColumn) . ') AS last_used
    FROM bookings b
    JOIN rooms r ON r.id = b.room_id
    WHERE b.user_id = ?
    GROUP BY r.id, r.name
    ORDER BY booking_count DESC, total_hours DESC, r.name ASC
');
$stmt->execute([(int) $user['id']]);
$rooms = $stmt->fetchAll();

$pageTitle = 'Reports';
ob_start();
?>
<div class="page-header">
    <div>
        <h1>Reports</h1>
        <p>Monthly faculty booking statistics and room usage from real reservation data.</p>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card stat-bookings"><div class="stat-icon"><i data-lucide="calendar-days"></i></div><div class="stat-label">Total Bookings</div><div class="stat-value"><?= (int) ($stats['total_bookings'] ?? 0) ?></div></div>
    <div class="stat-card stat-rooms"><div class="stat-icon"><i data-lucide="check-circle"></i></div><div class="stat-label">Approved</div><div class="stat-value"><?= (int) ($stats['approved_bookings'] ?? 0) ?></div></div>
    <div class="stat-card stat-pending"><div class="stat-icon"><i data-lucide="clock"></i></div><div class="stat-label">Pending</div><div class="stat-value"><?= (int) ($stats['pending_bookings'] ?? 0) ?></div></div>
    <div class="stat-card stat-notices"><div class="stat-icon"><i data-lucide="x-circle"></i></div><div class="stat-label">Rejected</div><div class="stat-value"><?= (int) ($stats['rejected_bookings'] ?? 0) ?></div></div>
</div>

<div class="split-grid" style="margin-bottom:20px">
    <div class="card">
        <h3 class="card-title">Booking Hours</h3>
        <div style="font-size:2.4rem;font-weight:700;color:var(--cr-beige)"><?= sanitize((string) ($stats['total_hours'] ?? '0')) ?></div>
        <p style="color:var(--cr-slate);margin-top:8px">Total booked hours in <?= sanitize(date('F Y')) ?></p>
    </div>
    <div class="card">
        <h3 class="card-title">Most Used Room</h3>
        <?php if ($mostUsedRoom): ?>
            <div style="font-size:1.6rem;font-weight:700;color:var(--cr-beige)"><?= sanitize((string) $mostUsedRoom['name']) ?></div>
            <p style="color:var(--cr-slate);margin-top:8px"><?= (int) $mostUsedRoom['booking_count'] ?> bookings this month</p>
        <?php else: ?>
            <div class="empty-state" style="padding:24px 0">No room usage this month.</div>
        <?php endif; ?>
    </div>
    <div class="card">
        <h3 class="card-title">Most Common Booking Type</h3>
        <?php if ($mostCommonType): ?>
            <div style="font-size:1.6rem;font-weight:700;color:var(--cr-beige)"><?= sanitize((string) $mostCommonType['purpose_label']) ?></div>
            <p style="color:var(--cr-slate);margin-top:8px"><?= (int) $mostCommonType['booking_count'] ?> bookings this month</p>
        <?php else: ?>
            <div class="empty-state" style="padding:24px 0">No booking type data this month.</div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h3 class="card-title">Room Usage</h3>
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead><tr><th>Room</th><th>Type</th><th>Capacity</th><th>Total Bookings</th><th>Approved</th><th>Pending</th><th>Total Hours</th><th>Last Used</th></tr></thead>
            <tbody>
                <?php if (!$rooms): ?>
                    <tr><td colspan="8" class="empty-state">No room usage found yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td><strong><?= sanitize((string) $room['room_name']) ?></strong><br><span style="font-size:.75rem;color:var(--cr-slate)"><?= sanitize((string) $room['building']) ?></span></td>
                            <td><?= sanitize((string) $room['room_type']) ?></td>
                            <td><?= (int) $room['capacity'] ?></td>
                            <td><?= (int) $room['booking_count'] ?></td>
                            <td><?= (int) $room['approved_count'] ?></td>
                            <td><?= (int) $room['pending_count'] ?></td>
                            <td><?= sanitize((string) $room['total_hours']) ?></td>
                            <td><?= sanitize(facultyDateOnly((string) $room['last_used'])) ?></td>
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
