<?php
require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$user = require_login();

function profile_user_response($pdo, $userId)
{
    $stmt = $pdo->prepare('SELECT id, name, email, role, is_active FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!$row) {
        json_response(['error' => 'User not found.'], 404);
    }

    return [
        'id' => (int) $row['id'],
        'name' => $row['name'],
        'email' => $row['email'],
        'role' => $row['role'],
        'is_active' => (bool) $row['is_active'],
    ];
}

if ($method === 'GET') {
    json_response(['ok' => true, 'user' => profile_user_response($pdo, $user['id'])]);
}

if ($method !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$input = get_json_input();
$action = $input['action'] ?? 'update_profile';

if ($action === 'update_profile') {
    $name = clean_string($input['name'] ?? '');
    if ($name === '') {
        json_response(['error' => 'Name is required.'], 422);
    }

    $stmt = $pdo->prepare('UPDATE users SET name = ? WHERE id = ?');
    $stmt->execute([$name, $user['id']]);

    $_SESSION['name'] = $name;
    json_response(['ok' => true, 'message' => 'Profile updated.', 'user' => profile_user_response($pdo, $user['id'])]);
}

if ($action === 'change_password') {
    $currentPassword = (string) ($input['current_password'] ?? '');
    $newPassword = (string) ($input['new_password'] ?? '');

    if ($currentPassword === '' || $newPassword === '') {
        json_response(['error' => 'Current password and new password are required.'], 422);
    }
    if (strlen($newPassword) < 6) {
        json_response(['error' => 'New password must be at least 6 characters.'], 422);
    }

    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $row = $stmt->fetch();
    if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
        json_response(['error' => 'Current password is incorrect.'], 403);
    }

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->execute([$hash, $user['id']]);

    json_response(['ok' => true, 'message' => 'Password updated.']);
}

json_response(['error' => 'Unknown action.'], 400);
