<?php
// GET: list rooms; POST: create room (admin)
require_once __DIR__ . '/helpers.php';

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
    if (!empty($_GET['status'])) { $where[] = 'status = ?'; $params[] = $_GET['status']; }
    if (!empty($_GET['equipment'])) { $where[] = 'equipment LIKE ?'; $params[] = '%' . $_GET['equipment'] . '%'; }
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
    require_role('admin');
    $input = get_json_input();

    $name = clean_string($input['name'] ?? '');
    $capacity = (int) ($input['capacity'] ?? 0);
    $type = clean_string($input['type'] ?? '');
    $building = clean_string($input['building'] ?? '');
    $floor = clean_string($input['floor'] ?? '');
    $equipment = clean_string($input['equipment'] ?? '');
    $status = clean_string($input['status'] ?? 'available');
    $notes = clean_string($input['notes'] ?? '');

    if (!$name || !$building || !$type) {
        json_response(['error' => 'Room name, building, and type are required.'], 400);
    }

    if ($capacity <= 0) {
        json_response(['error' => 'Capacity must be a positive number.'], 400);
    }

    if (!in_array($status, ['available', 'booked', 'maintenance', 'disabled'], true)) {
        json_response(['error' => 'Invalid room status.'], 400);
    }

    $stmt = $pdo->prepare('INSERT INTO rooms (name, capacity, type, building, floor, equipment, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$name, $capacity, $type, $building, $floor ?: null, $equipment ?: null, $status, $notes ?: null]);
    json_response(['ok' => true, 'id' => $pdo->lastInsertId()]);
}

json_response(['error' => 'Method not allowed'], 405);
