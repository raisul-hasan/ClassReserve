<?php
// Admin endpoints for maintenance blocks (GET/POST)
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    $stmt = $pdo->query('SELECT m.*, r.name as room_name FROM maintenance m JOIN rooms r ON m.room_id = r.id ORDER BY m.start_datetime');
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare('INSERT INTO maintenance (room_id, start_datetime, end_datetime, reason) VALUES (?, ?, ?, ?)');
    $stmt->execute([$input['room_id'], $input['start_datetime'], $input['end_datetime'], $input['reason'] ?? null]);
    echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
