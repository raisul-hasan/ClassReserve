<?php
require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$user = require_login();

if ($method === 'GET') {
    $stmt = $pdo->prepare('SELECT id, type, title, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 100');
    $stmt->execute([$user['id']]);
    json_response($stmt->fetchAll());
}

if ($method !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$input = get_json_input();
$action = $input['action'] ?? 'read';

if ($action === 'read_all') {
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
    $stmt->execute([$user['id']]);
    json_response(['ok' => true]);
}

$id = (int) ($input['id'] ?? 0);
if ($id <= 0) {
    json_response(['error' => 'Notification id is required.'], 422);
}

$stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $user['id']]);
json_response(['ok' => true]);
