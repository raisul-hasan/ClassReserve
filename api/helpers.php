<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mail.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global exception handler
set_exception_handler(function ($exception) {
    error_log($exception->getMessage() . "\n" . $exception->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'An unexpected server error occurred. Please try again later.']);
    exit;
});

// Generate CSRF token and set cookie
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
if (!headers_sent() && (!isset($_COOKIE['csrf_token']) || $_COOKIE['csrf_token'] !== $_SESSION['csrf_token'])) {
    setcookie('csrf_token', $_SESSION['csrf_token'], [
        'expires' => 0,
        'path' => '/',
        'secure' => false,
        'httponly' => false,
        'samesite' => 'Lax'
    ]);
}

function json_response($data, $status = 200)
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function get_json_input()
{
    $input = json_decode(file_get_contents('php://input'), true);
    return is_array($input) ? $input : [];
}

function current_user()
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    return [
        'id' => (int) $_SESSION['user_id'],
        'name' => $_SESSION['name'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? 'student',
        'is_active' => (bool) ($_SESSION['is_active'] ?? true),
    ];
}

function require_login()
{
    global $pdo;

    $user = current_user();
    if (!$user) {
        json_response(['error' => 'Please log in first.'], 401);
    }

    // Verify CSRF for modifying methods
    $method = $_SERVER['REQUEST_METHOD'];
    if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($_SESSION['csrf_token']) || !$token || $token !== $_SESSION['csrf_token']) {
            json_response(['error' => 'CSRF verification failed.'], 403);
        }
    }

    $stmt = $pdo->prepare('SELECT is_active FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $row = $stmt->fetch();
    if (!$row || !(bool) $row['is_active']) {
        $_SESSION = [];
        session_destroy();
        json_response(['error' => 'This account has been deactivated.'], 403);
    }

    return $user;
}

function require_role($roles)
{
    $user = require_login();
    if (!in_array($user['role'], (array) $roles, true)) {
        json_response(['error' => 'You do not have permission to do this.'], 403);
    }

    return $user;
}

function clean_string($value)
{
    return trim((string) ($value ?? ''));
}

function is_strong_password($password)
{
    if (strlen($password) < 8) {
        return false;
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        return false;
    }
    return true;
}

function valid_datetime($value)
{
    if (!$value) {
        return false;
    }

    $timestamp = strtotime($value);
    return $timestamp !== false;
}

function find_room($pdo, $roomId)
{
    $stmt = $pdo->prepare('SELECT * FROM rooms WHERE id = ?');
    $stmt->execute([(int) $roomId]);
    return $stmt->fetch();
}

function booking_conflict_exists($pdo, $roomId, $start, $end, $excludeBookingId = null)
{
    $sql = "SELECT id FROM bookings WHERE room_id = ? AND status IN ('pending', 'approved') AND NOT (end_datetime <= ? OR start_datetime >= ?)";
    $params = [(int) $roomId, $start, $end];

    if ($excludeBookingId) {
        $sql .= ' AND id <> ?';
        $params[] = (int) $excludeBookingId;
    }

    $stmt = $pdo->prepare($sql . ' LIMIT 1');
    $stmt->execute($params);
    return (bool) $stmt->fetch();
}

function room_has_active_bookings($pdo, $roomId)
{
    $stmt = $pdo->prepare("SELECT id FROM bookings WHERE room_id = ? AND status IN ('pending', 'approved') LIMIT 1");
    $stmt->execute([(int) $roomId]);
    return (bool) $stmt->fetch();
}

function maintenance_conflict_exists($pdo, $roomId, $start, $end, $excludeMaintenanceId = null)
{
    $sql = 'SELECT id FROM maintenance WHERE room_id = ? AND NOT (end_datetime <= ? OR start_datetime >= ?)';
    $params = [(int) $roomId, $start, $end];

    if ($excludeMaintenanceId) {
        $sql .= ' AND id <> ?';
        $params[] = (int) $excludeMaintenanceId;
    }

    $stmt = $pdo->prepare($sql . ' LIMIT 1');
    $stmt->execute($params);
    return (bool) $stmt->fetch();
}

function save_uploaded_attachment($fieldName = 'attachment', $prefix = 'att_')
{
    if (empty($_FILES[$fieldName])) {
        return null;
    }

    $file = $_FILES[$fieldName];
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        json_response(['error' => 'Attachment upload failed.'], 400);
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        json_response(['error' => 'Attachment must be 5 MB or smaller.'], 413);
    }

    // Scan content for PHP/Script tags to prevent remote code execution
    $tmpContent = file_get_contents($file['tmp_name']);
    if (preg_match('/<\?php/i', $tmpContent) || preg_match('/<script/i', $tmpContent)) {
        json_response(['error' => 'Uploaded file contains invalid or unsafe content.'], 415);
    }

    $allowedExtensions = [
        'pdf' => ['application/pdf'],
        'png' => ['image/png'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'txt' => ['text/plain'],
        'doc' => ['application/msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    ];
    $originalName = basename((string) ($file['name'] ?? ''));
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!isset($allowedExtensions[$extension])) {
        json_response(['error' => 'Attachment type is not allowed.'], 415);
    }

    $mimeType = null;
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        }
    }
    if ($mimeType && !in_array($mimeType, $allowedExtensions[$extension], true)) {
        json_response(['error' => 'Attachment content does not match its file type.'], 415);
    }

    $uploadsDir = __DIR__ . '/../public/uploads';
    if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true)) {
        json_response(['error' => 'Could not prepare upload directory.'], 500);
    }

    $safePrefix = preg_replace('/[^a-z0-9_]/i', '', $prefix) ?: 'att_';
    $newName = $safePrefix . bin2hex(random_bytes(16)) . '.' . $extension;
    $target = $uploadsDir . '/' . $newName;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        json_response(['error' => 'Could not save attachment.'], 500);
    }

    return 'uploads/' . $newName;
}

function create_notification($pdo, $userId, $type, $title, $message)
{
    if (!$userId) {
        return;
    }

    $allowedTypes = ['success', 'error', 'warning', 'pending', 'info'];
    if (!in_array($type, $allowedTypes, true)) {
        $type = 'info';
    }

    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, type, title, message) VALUES (?, ?, ?, ?)');
    $stmt->execute([(int) $userId, $type, $title, $message]);
}

function create_role_notification($pdo, $roles, $type, $title, $message)
{
    $stmt = $pdo->prepare('SELECT id FROM users WHERE is_active = 1 AND role IN (' . implode(',', array_fill(0, count((array) $roles), '?')) . ')');
    $stmt->execute((array) $roles);
    foreach ($stmt->fetchAll() as $row) {
        create_notification($pdo, $row['id'], $type, $title, $message);
    }
}

function create_audit_log($pdo, $userId, $action, $targetType = null, $targetId = null, $details = null)
{
    if (is_array($details) || is_object($details)) {
        $details = json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, target_type, target_id, details) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([
        $userId ? (int) $userId : null,
        clean_string($action),
        $targetType ? clean_string($targetType) : null,
        $targetId ? (int) $targetId : null,
        $details !== null ? (string) $details : null,
    ]);
}
