<?php

<<<<<<< HEAD
if (session_status() === PHP_SESSION_NONE) {
    $savePath = session_save_path();
    if (empty($savePath) || strpos($savePath, 'Program Files') !== false || !is_writable($savePath)) {
        session_save_path(sys_get_temp_dir());
    }
    session_start();
}
=======
session_start();
>>>>>>> origin/Riche01

require_once __DIR__ . '/db.php';

const PRIORITY_FACULTY = 3;
const PRIORITY_CLUB    = 2;
const PRIORITY_STUDENT = 1;

function priorityForRole(string $role): int
{
    return match ($role) {
        'faculty' => PRIORITY_FACULTY,
        'club'    => PRIORITY_CLUB,
        default   => PRIORITY_STUDENT,
    };
}

function roleLabel(string $role): string
{
    return match ($role) {
        'student' => 'Student',
        'club'    => 'Club',
        'faculty' => 'Faculty',
        'admin'   => 'Admin',
        default   => ucfirst($role),
    };
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $stmt = getDb()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function requireRole(array $roles): void
{
    requireLogin();
    $user = currentUser();
    if (!$user || !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        echo 'Access denied.';
        exit;
    }
}

function loginUser(array $user): void
{
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['name'];
}

function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

function redirectByRole(string $role): void
{
    $map = [
        'student' => '/student/dashboard.php',
        'club'    => '/student/dashboard.php',
        'faculty' => '/faculty/dashboard.php',
        'admin'   => '/admin/dashboard.php',
    ];
    header('Location: ' . ($map[$role] ?? '/login.php'));
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function sanitize(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function formatDateTime(string $dt): string
{
    return date('M j, Y g:i A', strtotime($dt));
}

<<<<<<< HEAD
function normalizeDateTimeForDatabase(string $value): ?string
{
    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return null;
    }

    return date('Y-m-d H:i:s', $timestamp);
}

=======
>>>>>>> origin/Riche01
function timesOverlap(string $start1, string $end1, string $start2, string $end2): bool
{
    return $start1 < $end2 && $end1 > $start2;
}

/**
 * Check if a room has maintenance or booking conflicts.
 * Returns array with 'available' bool and 'conflicts' list.
 */
function checkRoomAvailability(
    int $roomId,
    string $startTime,
    string $endTime,
    int $requestPriority,
    ?int $excludeBookingId = null
): array {
    $db = getDb();
    $conflicts = [];
<<<<<<< HEAD
    $normalizedStart = normalizeDateTimeForDatabase($startTime);
    $normalizedEnd = normalizeDateTimeForDatabase($endTime);

    if ($normalizedStart === null || $normalizedEnd === null || $normalizedStart >= $normalizedEnd) {
        return [
            'available' => false,
            'conflicts' => [[
                'type' => 'invalid_time',
            ]],
        ];
    }
=======
>>>>>>> origin/Riche01

    $stmt = $db->prepare(
        'SELECT * FROM maintenance_blocks
         WHERE room_id = ? AND start_time < ? AND end_time > ?'
    );
<<<<<<< HEAD
    $stmt->execute([$roomId, $normalizedEnd, $normalizedStart]);
=======
    $stmt->execute([$roomId, $endTime, $startTime]);
>>>>>>> origin/Riche01
    foreach ($stmt->fetchAll() as $block) {
        $conflicts[] = [
            'type'   => 'maintenance',
            'reason' => $block['reason'],
            'start'  => $block['start_time'],
            'end'    => $block['end_time'],
        ];
    }

    $sql = 'SELECT b.*, u.name AS user_name, u.role AS user_role
            FROM bookings b
            JOIN users u ON u.id = b.user_id
            WHERE b.room_id = ?
              AND b.status IN ("approved", "pending")
              AND b.start_time < ? AND b.end_time > ?';
<<<<<<< HEAD
    $params = [$roomId, $normalizedEnd, $normalizedStart];
=======
    $params = [$roomId, $endTime, $startTime];
>>>>>>> origin/Riche01

    if ($excludeBookingId) {
        $sql .= ' AND b.id != ?';
        $params[] = $excludeBookingId;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    foreach ($stmt->fetchAll() as $booking) {
<<<<<<< HEAD
        $conflicts[] = [
            'type'     => 'booking',
            'id'       => $booking['id'],
            'title'    => $booking['title'],
            'user'     => $booking['user_name'],
            'role'     => $booking['user_role'],
            'priority' => $booking['priority'],
            'status'   => $booking['status'],
            'start'    => $booking['start_time'],
            'end'      => $booking['end_time'],
        ];
=======
        if ((int) $booking['priority'] >= $requestPriority) {
            $conflicts[] = [
                'type'     => 'booking',
                'id'       => $booking['id'],
                'title'    => $booking['title'],
                'user'     => $booking['user_name'],
                'role'     => $booking['user_role'],
                'priority' => $booking['priority'],
                'status'   => $booking['status'],
                'start'    => $booking['start_time'],
                'end'      => $booking['end_time'],
            ];
        }
>>>>>>> origin/Riche01
    }

    return [
        'available' => count($conflicts) === 0,
        'conflicts' => $conflicts,
    ];
}

function searchAvailableRooms(
    string $startTime,
    string $endTime,
    int $minCapacity,
    int $requestPriority
): array {
    $db = getDb();
<<<<<<< HEAD
    $normalizedStart = normalizeDateTimeForDatabase($startTime);
    $normalizedEnd = normalizeDateTimeForDatabase($endTime);

    if ($normalizedStart === null || $normalizedEnd === null || $normalizedStart >= $normalizedEnd) {
        return [];
    }

=======
>>>>>>> origin/Riche01
    $stmt = $db->prepare(
        'SELECT * FROM rooms WHERE status = "available" AND capacity >= ? ORDER BY capacity, name'
    );
    $stmt->execute([$minCapacity]);
    $rooms = $stmt->fetchAll();
    $available = [];

    foreach ($rooms as $room) {
<<<<<<< HEAD
        $check = checkRoomAvailability((int) $room['id'], $normalizedStart, $normalizedEnd, $requestPriority);
=======
        $check = checkRoomAvailability((int) $room['id'], $startTime, $endTime, $requestPriority);
>>>>>>> origin/Riche01
        if ($check['available']) {
            $room['conflicts'] = [];
            $available[] = $room;
        }
    }

    return $available;
}

function getBookingsForCalendar(?string $start = null, ?string $end = null): array
{
    $db = getDb();
    $sql = 'SELECT b.*, r.name AS room_name, r.building, u.name AS user_name, u.role AS user_role
            FROM bookings b
            JOIN rooms r ON r.id = b.room_id
            JOIN users u ON u.id = b.user_id
            WHERE b.status IN ("approved", "pending")';
    $params = [];

    if ($start) {
        $sql .= ' AND b.end_time >= ?';
        $params[] = $start;
    }
    if ($end) {
        $sql .= ' AND b.start_time <= ?';
        $params[] = $end;
    }

    $sql .= ' ORDER BY b.start_time';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function handleFileUpload(array $file): ?string
{
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload failed.');
    }

    $allowed = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException('Invalid file type. Allowed: PDF, DOC, DOCX, PNG, JPG.');
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('File too large (max 5MB).');
    }

    $uploadDir = __DIR__ . '/../uploads/events/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = uniqid('event_', true) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        throw new RuntimeException('Could not save uploaded file.');
    }

    return 'uploads/events/' . $filename;
}

function statusBadgeClass(string $status): string
{
    return match ($status) {
        'approved'  => 'badge-success',
        'pending'   => 'badge-warning',
        'rejected'  => 'badge-danger',
        'cancelled' => 'badge-muted',
        default     => 'badge-muted',
    };
}
