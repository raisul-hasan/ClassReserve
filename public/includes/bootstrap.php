<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    $savePath = session_save_path();
    if (empty($savePath) || strpos($savePath, 'Program Files') !== false || !is_writable($savePath)) {
        session_save_path(sys_get_temp_dir());
    }
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
    return !empty($_SESSION['user']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: /public/login.php');
        exit;
    }
}

function requireUserRole(array $roles): array
{
    requireLogin();
    $user = currentUser();
    if (!$user || !in_array($user['role'], $roles, true)) {
        header('Location: /public/dashboard.php');
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
    $dashboardUrl = $role === 'admin' ? '/public/admin.php' : '/public/dashboard.php';

    if ($role === 'faculty') {
        return [
            ['href' => '/faculty', 'icon' => 'layout-dashboard', 'label' => 'Dashboard'],
            ['href' => '/faculty/rooms', 'icon' => 'door-open', 'label' => 'Rooms', 'matches' => ['/faculty/rooms.php', '/faculty/recommendations.php', '/faculty/favorite-rooms.php', '/faculty/room-usage.php']],
            ['href' => '/faculty/reservations', 'icon' => 'clipboard-list', 'label' => 'Reservations', 'matches' => ['/faculty/reservations.php', '/faculty/reserve.php', '/faculty/my-reservations.php', '/faculty/schedule.php', '/faculty/print-schedule.php', '/faculty/booking-history.php']],
            ['href' => '/faculty/approvals', 'icon' => 'check-square', 'label' => 'Approvals', 'matches' => ['/faculty/approvals.php', '/faculty/approval-history.php']],
            ['href' => '/faculty/calendar', 'icon' => 'calendar', 'label' => 'Calendar'],
            ['href' => '/faculty/reports', 'icon' => 'bar-chart-3', 'label' => 'Reports', 'matches' => ['/faculty/reports.php', '/faculty/monthly-report.php']],
            ['href' => '/faculty/forum', 'icon' => 'messages-square', 'label' => 'Classroom Forum'],
            ['href' => '/faculty/notifications', 'icon' => 'bell', 'label' => 'Notifications'],
            ['href' => '/faculty/profile', 'icon' => 'user', 'label' => 'Profile', 'matches' => ['/faculty/profile.php', '/faculty/settings.php']],
        ];
    }

    $common = [
        ['href' => $dashboardUrl, 'icon' => 'layout-dashboard', 'label' => 'Dashboard'],
        ['href' => '/public/new-booking.php', 'icon' => 'calendar-plus', 'label' => 'New Booking'],
        ['href' => '/public/calendar.php', 'icon' => 'calendar', 'label' => 'Calendar'],
        ['href' => '/public/forum.php', 'icon' => 'messages-square', 'label' => 'Forum'],
    ];

    if ($role === 'admin') {
        $common[] = ['href' => '/public/admin.php', 'icon' => 'shield', 'label' => 'Admin Panel'];
    }

    return $common;
}
