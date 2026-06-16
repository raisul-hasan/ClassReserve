<?php

require_once __DIR__ . '/bootstrap.php';

requireAuth();
updateNoShows();

$db = getDb();
$user = $_SESSION['user'];

$stats = [];

$stmt = $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'available'");
$stats['available_rooms'] = (int)$stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM rooms");
$stats['total_rooms'] = (int)$stmt->fetchColumn();

// Backwards compatibility for existing student/faculty dashboard.php
$stats['rooms'] = $stats['available_rooms'];

if ($user['role'] === 'admin') {
    $stmt = $db->query("SELECT COUNT(*) FROM bookings");
    $stats['bookings'] = (int)$stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
    $stats['pending'] = (int)$stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'approved'");
    $stats['approved'] = (int)$stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'rejected'");
    $stats['rejected'] = (int)$stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'cancelled'");
    $stats['cancelled'] = (int)$stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
    $stats['active_users'] = (int)$stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM users");
    $stats['total_users'] = (int)$stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM maintenance WHERE start_datetime <= NOW() AND end_datetime >= NOW()");
    $stats['maintenance_blocks'] = (int)$stmt->fetchColumn();

    // All upcoming/active maintenance
    $stmt = $db->query("SELECT COUNT(*) FROM maintenance WHERE end_datetime >= NOW()");
    $stats['upcoming_maintenance'] = (int)$stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM issues WHERE status NOT IN ('Resolved', 'Rejected')");
    $stats['issues'] = (int)$stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM issues");
    $stats['issues_total'] = (int)$stmt->fetchColumn();
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
    SELECT b.*, r.name AS room_name, u.name AS user_name, u.role AS user_role
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
