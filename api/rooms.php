<?php
// GET: list rooms; POST: create room (admin)
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    $stmt = $pdo->query('SELECT * FROM rooms ORDER BY name');
    $rooms = $stmt->fetchAll();
    echo json_encode($rooms);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare('INSERT INTO rooms (name, capacity, type, building, notes) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$input['name'], $input['capacity'] ?? 0, $input['type'] ?? null, $input['building'] ?? null, $input['notes'] ?? null]);
    echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
