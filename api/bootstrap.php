<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

set_exception_handler(function (Throwable $e): void {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

session_start();

$config = require __DIR__ . '/../config/database.php';

function getDb(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $config = require __DIR__ . '/../config/database.php';
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function jsonResponse(mixed $data, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError(string $message, int $code = 400): never
{
    jsonResponse(['success' => false, 'error' => $message], $code);
}

function requireAuth(): array
{
    if (empty($_SESSION['user'])) {
        jsonError('Authentication required', 401);
    }
    return $_SESSION['user'];
}

function requireRole(array $roles): array
{
    $user = requireAuth();
    if (!in_array($user['role'], $roles, true)) {
        jsonError('Insufficient permissions', 403);
    }
    return $user;
}

function getCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (!hash_equals(getCsrfToken(), $token)) {
        jsonError('Invalid CSRF token', 403);
    }
}

function getJsonInput(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);
    return is_array($data) ? $data : [];
}

function tableColumnExists(string $table, string $column): bool
{
    static $cache = [];
    $key = $table . '.' . $column;
    if (!array_key_exists($key, $cache)) {
        $stmt = getDb()->prepare("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        $cache[$key] = (bool) $stmt->fetchColumn();
    }
    return $cache[$key];
}

function tableExists(string $table): bool
{
    static $cache = [];
    if (!array_key_exists($table, $cache)) {
        $stmt = getDb()->prepare("
            SELECT COUNT(*)
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
        ");
        $stmt->execute([$table]);
        $cache[$table] = (bool) $stmt->fetchColumn();
    }
    return $cache[$table];
}

function auditLog(?int $userId, string $action, ?string $targetType = null, ?int $targetId = null, ?string $details = null): void
{
    if (!tableExists('audit_logs')) {
        return;
    }

    $stmt = getDb()->prepare(
        'INSERT INTO audit_logs (user_id, action, target_type, target_id, details) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $action, $targetType, $targetId, $details]);
}

function createNotification(int $userId, string $type, string $title, string $message): void
{
    if (!tableExists('notifications')) {
        return;
    }

    $stmt = getDb()->prepare(
        'INSERT INTO notifications (user_id, type, title, message) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $type, $title, $message]);
}

function rolePriority(string $role): int
{
    return match ($role) {
        'faculty' => 3,
        'club'    => 2,
        default   => 1,
    };
}

function generateCheckinCode(): string
{
    return strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
}

function updateNoShows(): void
{
    $db = getDb();
    $db->exec("
        UPDATE bookings
        SET no_show = 1
        WHERE checked_in_at IS NULL
          AND no_show = 0
          AND status = 'approved'
          AND start_datetime < DATE_SUB(NOW(), INTERVAL 15 MINUTE)
          AND end_datetime > NOW()
    ");
}

function checkBookingConflict(int $roomId, string $start, string $end, ?int $excludeId = null): ?string
{
    $db = getDb();
    $params = [$roomId, $end, $start];
    $sql = "
        SELECT id FROM bookings
        WHERE room_id = ? AND status IN ('pending', 'approved')
          AND start_datetime < ? AND end_datetime > ?
    ";
    if ($excludeId) {
        $sql .= ' AND id != ?';
        $params[] = $excludeId;
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    if ($stmt->fetch()) {
        return 'Room has a conflicting booking';
    }

    $stmt = $db->prepare("
        SELECT id FROM maintenance
        WHERE room_id = ? AND start_datetime < ? AND end_datetime > ?
    ");
    $stmt->execute([$roomId, $end, $start]);
    if ($stmt->fetch()) {
        return 'Room is under maintenance during this period';
    }

    $stmt = $db->prepare('SELECT status FROM rooms WHERE id = ?');
    $stmt->execute([$roomId]);
    $room = $stmt->fetch();
    if (!$room || !in_array($room['status'], ['available'], true)) {
        return 'Room is not available for booking';
    }

    return null;
}
