<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$config = require __DIR__ . '/../../config/database.php';

function getDb(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $config = require __DIR__ . '/../../config/database.php';
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function isLoggedIn(): bool
{
    if (empty($_SESSION['user']) && !empty($_SESSION['user_id'])) {
        $stmt = getDb()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([(int)$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if ($user) {
            unset($user['password'], $user['password_hash']);
            $_SESSION['user'] = $user;
        }
    }
    return !empty($_SESSION['user']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . getBaseUrl() . '/public/login.php');
        exit;
    }
}

function requireUserRole(array $roles): array
{
    requireLogin();
    $user = currentUser();
    if (!$user || !in_array($user['role'], $roles, true)) {
        header('Location: ' . getBaseUrl() . '/public/dashboard.php');
        exit;
    }

    return $user;
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function sanitize(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function getCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function apiUrl(string $endpoint): string
{
    return '/api/' . ltrim($endpoint, '/');
}

function roleLabel(string $role): string
{
    return match ($role) {
        'admin'   => 'Administrator',
        'faculty' => 'Faculty',
        'club'    => 'Club',
        default   => 'Student',
    };
}

function rolePortal(string $role): string
{
    return match ($role) {
        'admin'   => 'Admin Portal',
        'faculty' => 'Faculty Portal',
        'club'    => 'Club Portal',
        default   => 'Student Portal',
    };
}

function navItems(string $role): array
{
    if ($role === 'admin') {
        return [
            ['href' => '/public/admin.php', 'icon' => 'layout-dashboard', 'label' => 'Dashboard'],
            ['href' => '/public/admin-approvals.php', 'icon' => 'check-square', 'label' => 'Manage Requests'],
            ['href' => '/public/admin-rooms.php', 'icon' => 'door-open', 'label' => 'Manage Rooms'],
            ['href' => '/public/admin-users.php', 'icon' => 'users', 'label' => 'Manage Users'],
            ['href' => '/public/admin-maintenance.php', 'icon' => 'wrench', 'label' => 'Maintenance'],
            ['href' => '/public/forum.php', 'icon' => 'messages-square', 'label' => 'Issue Reports'],
            ['href' => '/public/admin-audit-logs.php', 'icon' => 'list', 'label' => 'Audit Logs'],
            ['href' => '/public/notices.php', 'icon' => 'bell', 'label' => 'Notifications'],
            ['href' => '/public/profile.php', 'icon' => 'user', 'label' => 'Profile'],
            ['href' => '/public/settings.php', 'icon' => 'settings', 'label' => 'Settings'],
        ];
    }

    if (in_array($role, ['student', 'club'], true)) {
        return [
            ['href' => '/public/dashboard.php', 'icon' => 'layout-dashboard', 'label' => 'Dashboard'],
            ['href' => '/public/new-booking.php', 'icon' => 'calendar-plus', 'label' => 'New Booking'],
            ['href' => '/public/search-rooms.php', 'icon' => 'search', 'label' => 'Search Rooms'],
            ['href' => '/public/calendar.php', 'icon' => 'calendar', 'label' => 'Calendar'],
            ['href' => '/public/my-bookings.php', 'icon' => 'calendar-days', 'label' => 'My Bookings'],
            ['href' => '/public/notices.php', 'icon' => 'messages-square', 'label' => 'Notices'],
            ['href' => '/public/forum.php', 'icon' => 'message-square', 'label' => 'Forum'],
            ['href' => '/public/profile.php', 'icon' => 'user', 'label' => 'Profile'],
            ['href' => '/public/settings.php', 'icon' => 'settings', 'label' => 'Settings'],
        ];
    }

    if ($role === 'faculty') {
        return [
            ['href' => '/public/dashboard.php', 'icon' => 'layout-dashboard', 'label' => 'Dashboard'],
            ['href' => '/faculty/rooms.php', 'icon' => 'door-open', 'label' => 'Rooms', 'matches' => ['/faculty/rooms', '/faculty/recommendations.php', '/faculty/favorite-rooms.php', '/faculty/room-usage.php']],
            ['href' => '/faculty/reservations.php', 'icon' => 'clipboard-list', 'label' => 'Reservations', 'matches' => ['/faculty/reservations', '/faculty/reserve.php', '/faculty/my-reservations.php', '/faculty/schedule.php', '/faculty/print-schedule.php', '/faculty/booking-history.php']],
            ['href' => '/faculty/approvals.php', 'icon' => 'check-square', 'label' => 'Approvals', 'matches' => ['/faculty/approvals', '/faculty/approval-history.php']],
            ['href' => '/faculty/calendar.php', 'icon' => 'calendar', 'label' => 'Calendar'],
            ['href' => '/faculty/reports.php', 'icon' => 'bar-chart-3', 'label' => 'Reports', 'matches' => ['/faculty/reports', '/faculty/monthly-report.php']],
            ['href' => '/faculty/forum.php', 'icon' => 'messages-square', 'label' => 'Classroom Forum'],
            ['href' => '/faculty/notifications.php', 'icon' => 'bell', 'label' => 'Notifications'],
            ['href' => '/faculty/profile.php', 'icon' => 'user', 'label' => 'Profile', 'matches' => ['/faculty/profile', '/faculty/settings.php']],
        ];
    }

    return [
        ['href' => '/public/dashboard.php', 'icon' => 'layout-dashboard', 'label' => 'Dashboard'],
    ];
}

function getBaseUrl(): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $publicPos = strpos($script, '/public/');
    if ($publicPos !== false) {
        return substr($script, 0, $publicPos);
    }
    return '';
}
