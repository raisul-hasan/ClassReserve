<?php

require_once __DIR__ . '/bootstrap.php';

requireAuth();
updateNoShows();

$db = getDb();
$user = $_SESSION['user'];

$stats = [];

$stmt = $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'available'");
$stats['rooms'] = (int)$stmt->fetchColumn();

if ($user['role'] === 'admin') {
    $stmt = $db->query("SELECT COUNT(*) FROM bookings");
    $stats['bookings'] = (int)$stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
    $stats['pending'] = (int)$stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM issues WHERE status NOT IN ('Resolved', 'Rejected')");
    $stats['issues'] = (int)$stmt->fetchColumn();
} else {
    $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $stats['bookings'] = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$user['id']]);
    $stats['pending'] = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user['id']]);
    $stats['notices'] = (int)$stmt->fetchColumn();
}

$stmt = $db->prepare("
    SELECT b.*, r.name AS room_name, u.name AS user_name
    FROM bookings b
    JOIN rooms r ON r.id = b.room_id
    JOIN users u ON u.id = b.user_id
    " . ($user['role'] === 'admin' ? '' : 'WHERE b.user_id = ?') . "
    ORDER BY b.created_at DESC LIMIT 5
");
if ($user['role'] !== 'admin') {
    $stmt->execute([$user['id']]);
} else {
    $stmt->execute();
}
$recentBookings = $stmt->fetchAll();

$stmt = $db->prepare("
    SELECT * FROM notifications WHERE user_id = ?
    ORDER BY created_at DESC LIMIT 5
");
$stmt->execute([$user['id']]);
$recentNotifications = $stmt->fetchAll();

jsonResponse([
    'stats' => $stats,
    'recent_bookings' => $recentBookings,
    'recent_notifications' => $recentNotifications,
]);
