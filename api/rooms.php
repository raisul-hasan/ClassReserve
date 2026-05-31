<?php
// GET: list rooms; POST: create room (admin)
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    // support optional filters: capacity_min, capacity_max, type, building, q (search name)
    $sql = 'SELECT * FROM rooms';
    $where = [];
    $params = [];
    if (isset($_GET['capacity_min'])) { $where[] = 'capacity >= ?'; $params[] = (int)$_GET['capacity_min']; }
    if (isset($_GET['capacity_max'])) { $where[] = 'capacity <= ?'; $params[] = (int)$_GET['capacity_max']; }
    if (!empty($_GET['type'])) { $where[] = 'type = ?'; $params[] = $_GET['type']; }
    if (!empty($_GET['building'])) { $where[] = 'building = ?'; $params[] = $_GET['building']; }
    if (!empty($_GET['q'])) { $where[] = 'name LIKE ?'; $params[] = '%' . $_GET['q'] . '%'; }
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' ORDER BY name';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
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
