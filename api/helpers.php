<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
    ];
}

function require_login()
{
    $user = current_user();
    if (!$user) {
        json_response(['error' => 'Please log in first.'], 401);
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

function valid_datetime($value)
{
    if (!$value) {
        return false;
    }

    $timestamp = strtotime($value);
    return $timestamp !== false;
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
    $stmt = $pdo->prepare('SELECT id FROM users WHERE role IN (' . implode(',', array_fill(0, count((array) $roles), '?')) . ')');
    $stmt->execute((array) $roles);
    foreach ($stmt->fetchAll() as $row) {
        create_notification($pdo, $row['id'], $type, $title, $message);
    }
}
