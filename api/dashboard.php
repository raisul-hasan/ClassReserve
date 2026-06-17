<?php

require_once __DIR__ . '/bootstrap.php';

requireAuth();
updateNoShows();

$db = getDb();
$user = $_SESSION['user'];

$stats = [];

$todayStart = date('Y-m-d 00:00:00');
$todayEnd = date('Y-m-d 23:59:59');

$stmt = $db->prepare(
    "SELECT COUNT(*) FROM rooms r
     WHERE r.status = 'available'
       AND r.id NOT IN (
           SELECT room_id FROM bookings
           WHERE status IN ('pending', 'approved')
             AND start_datetime < ? AND end_datetime > ?
       )
       AND r.id NOT IN (
           SELECT room_id FROM maintenance
           WHERE start_datetime < ? AND end_datetime > ?
       )"
);
$stmt->execute([$todayEnd, $todayStart, $todayEnd, $todayStart]);
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
} elseif ($user['role'] === 'faculty') {
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM bookings
        WHERE user_id = ?
          AND status IN ('pending', 'approved')
          AND start_datetime >= NOW()
    ");
    $stmt->execute([$user['id']]);
    $stats['bookings'] = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT COUNT(*) FROM bookings
        WHERE user_id = ?
          AND status IN ('pending', 'approved')
          AND start_datetime < ?
          AND end_datetime > ?
    ");
    $stmt->execute([$user['id'], $todayEnd, $todayStart]);
    $stats['today'] = (int)$stmt->fetchColumn();

    $stmt = $db->query("
        SELECT COUNT(*) FROM bookings b
        JOIN users u ON u.id = b.user_id
        WHERE b.status = 'pending'
          AND u.role IN ('student', 'club')
    ");
    $stats['pending'] = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user['id']]);
    $stats['notices'] = (int)$stmt->fetchColumn();
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

$response = [
    'stats' => $stats,
    'recent_bookings' => $recentBookings,
    'recent_notifications' => $recentNotifications,
];

if ($user['role'] === 'faculty') {
    $response['faculty'] = [
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];

    $stmt = $db->prepare("
        SELECT b.*, r.name AS room_name, r.building, u.name AS user_name, u.role AS user_role
        FROM bookings b
        JOIN rooms r ON r.id = b.room_id
        JOIN users u ON u.id = b.user_id
        WHERE b.user_id = ?
          AND b.status IN ('pending', 'approved')
          AND b.start_datetime < ?
          AND b.end_datetime > ?
        ORDER BY b.start_datetime ASC
    ");
    $stmt->execute([$user['id'], $todayEnd, $todayStart]);
    $response['today_schedule'] = $stmt->fetchAll();

    $stmt = $db->prepare("
        SELECT b.*, r.name AS room_name, r.building, u.name AS user_name, u.role AS user_role
        FROM bookings b
        JOIN rooms r ON r.id = b.room_id
        JOIN users u ON u.id = b.user_id
        WHERE b.user_id = ?
          AND b.status IN ('pending', 'approved')
          AND b.start_datetime >= NOW()
        ORDER BY b.start_datetime ASC
        LIMIT 12
    ");
    $stmt->execute([$user['id']]);
    $upcomingBookings = $stmt->fetchAll();
    $response['upcoming_bookings'] = $upcomingBookings;
    $response['my_bookings'] = $upcomingBookings;

    $stmt = $db->prepare("
        SELECT b.*, r.name AS room_name, r.building, u.name AS user_name, u.role AS user_role
        FROM bookings b
        JOIN rooms r ON r.id = b.room_id
        JOIN users u ON u.id = b.user_id
        WHERE b.status = 'pending'
          AND u.role IN ('student', 'club')
        ORDER BY b.priority DESC, b.start_datetime ASC, b.created_at ASC
    ");
    $stmt->execute();
    $response['pending_requests'] = $stmt->fetchAll();
}

jsonResponse($response);
