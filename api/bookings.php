<?php
// Simple booking endpoints: list and create with conflict check
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    $stmt = $pdo->query('SELECT b.*, u.name as user_name, r.name as room_name FROM bookings b LEFT JOIN users u ON b.user_id = u.id LEFT JOIN rooms r ON b.room_id = r.id ORDER BY b.start_datetime');
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    // basic conflict detection: check approved bookings and maintenance
    $start = $input['start_datetime'];
    $end = $input['end_datetime'];
    $room_id = $input['room_id'];

    $conflictStmt = $pdo->prepare("SELECT id FROM bookings WHERE room_id = ? AND status = 'approved' AND NOT (end_datetime <= ? OR start_datetime >= ?)");
    $conflictStmt->execute([$room_id, $start, $end]);
    if ($conflictStmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Time conflict with existing booking']);
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO bookings (user_id, room_id, start_datetime, end_datetime, status, priority, title, description, uploaded_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $input['user_id'] ?? null,
        $room_id,
        $start,
        $end,
        'pending',
        $input['priority'] ?? 1,
        $input['title'] ?? null,
        $input['description'] ?? null,
        $input['uploaded_path'] ?? null
    ]);
    echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
