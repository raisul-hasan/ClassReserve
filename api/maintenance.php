<?php
// Maintenance blocks are visible to logged-in users. Only admins can create them.
require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    require_login();
    $stmt = $pdo->query('SELECT m.*, r.name as room_name FROM maintenance m JOIN rooms r ON m.room_id = r.id ORDER BY m.start_datetime');
    json_response($stmt->fetchAll());
}

if ($method === 'POST') {
    require_role('admin');
    $input = get_json_input();
    $roomId = (int) ($input['room_id'] ?? 0);
    $start = clean_string($input['start_datetime'] ?? '');
    $end = clean_string($input['end_datetime'] ?? '');
    $reason = clean_string($input['reason'] ?? '');

    if ($roomId <= 0 || !valid_datetime($start) || !valid_datetime($end) || strtotime($end) <= strtotime($start)) {
        json_response(['error' => 'Valid room, start time, and end time are required.'], 422);
    }

    $stmt = $pdo->prepare('INSERT INTO maintenance (room_id, start_datetime, end_datetime, reason) VALUES (?, ?, ?, ?)');
    $stmt->execute([$roomId, $start, $end, $reason ?: null]);
    json_response(['ok' => true, 'id' => (int) $pdo->lastInsertId()], 201);
}

json_response(['error' => 'Method not allowed'], 405);
