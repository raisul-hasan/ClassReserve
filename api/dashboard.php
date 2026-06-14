<?php
require_once __DIR__ . '/helpers.php';

$user = require_role(['admin', 'faculty']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

$stmt = $pdo->query("SELECT COUNT(*) AS total,
    SUM(status = 'available') AS available,
    SUM(status = 'booked') AS booked,
    SUM(status = 'maintenance') AS maintenance
    FROM rooms WHERE status <> 'disabled'");
$roomRow = $stmt->fetch() ?: [];
$rooms = [
    'total' => (int) ($roomRow['total'] ?? 0),
    'available' => (int) ($roomRow['available'] ?? 0),
    'booked' => (int) ($roomRow['booked'] ?? 0),
    'maintenance' => (int) ($roomRow['maintenance'] ?? 0),
];

$stmt = $pdo->query("SELECT COUNT(*) AS total,
    SUM(status = 'pending') AS pending,
    SUM(status = 'approved') AS approved,
    SUM(status = 'rejected') AS rejected,
    SUM(status = 'cancelled') AS cancelled
    FROM bookings");
$bookingRow = $stmt->fetch() ?: [];
$bookings = [
    'total' => (int) ($bookingRow['total'] ?? 0),
    'pending' => (int) ($bookingRow['pending'] ?? 0),
    'approved' => (int) ($bookingRow['approved'] ?? 0),
    'rejected' => (int) ($bookingRow['rejected'] ?? 0),
    'cancelled' => (int) ($bookingRow['cancelled'] ?? 0),
];

$stmt = $pdo->query('SELECT status, COUNT(*) AS count FROM issues GROUP BY status ORDER BY status');
$issuesByStatus = array_map(function ($row) {
    return ['status' => $row['status'], 'count' => (int) $row['count']];
}, $stmt->fetchAll());

$stmt = $pdo->query("SELECT r.id AS room_id, r.name AS room_name, COUNT(b.id) AS booking_count
    FROM rooms r JOIN bookings b ON b.room_id = r.id
    WHERE b.status = 'approved'
    GROUP BY r.id, r.name ORDER BY booking_count DESC, r.name LIMIT 5");
$mostUsedRooms = array_map(function ($row) {
    return ['room_id' => (int) $row['room_id'], 'room_name' => $row['room_name'], 'booking_count' => (int) $row['booking_count']];
}, $stmt->fetchAll());

$stmt = $pdo->query('SELECT u.role, COUNT(b.id) AS count FROM bookings b JOIN users u ON b.user_id = u.id GROUP BY u.role ORDER BY u.role');
$bookingsByRole = array_map(function ($row) {
    return ['role' => $row['role'], 'count' => (int) $row['count']];
}, $stmt->fetchAll());

$stmt = $pdo->query('SELECT a.id, a.action, a.target_type, a.target_id, a.details, a.created_at, u.name AS user_name FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC, a.id DESC LIMIT 6');

json_response([
    'rooms' => $rooms,
    'bookings' => $bookings,
    'issues_by_status' => $issuesByStatus,
    'most_used_rooms' => $mostUsedRooms,
    'bookings_by_role' => $bookingsByRole,
    'recent_activity' => $stmt->fetchAll(),
    'viewer_role' => $user['role'],
]);
