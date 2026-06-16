<?php

require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $db = getDb();
        $building = $_GET['building'] ?? '';
        $type = $_GET['type'] ?? '';
        $status = $_GET['status'] ?? '';
        $minCap = (int)($_GET['min_capacity'] ?? 0);

        $sql = 'SELECT * FROM rooms WHERE 1=1';
        $params = [];

        if ($building) {
            $sql .= ' AND building = ?';
            $params[] = $building;
        }
        if ($type) {
            $sql .= ' AND type = ?';
            $params[] = $type;
        }
        if ($status) {
            $sql .= ' AND status = ?';
            $params[] = $status;
        }
        if ($minCap) {
            $sql .= ' AND capacity >= ?';
            $params[] = $minCap;
        }

        $sql .= ' ORDER BY building, floor, name';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        jsonResponse(['rooms' => $stmt->fetchAll()]);
        break;

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = getDb()->prepare('SELECT * FROM rooms WHERE id = ?');
        $stmt->execute([$id]);
        $room = $stmt->fetch();
        if (!$room) jsonError('Room not found', 404);
        jsonResponse(['room' => $room]);
        break;

    case 'create':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $user = requireRole(['admin']);
        verifyCsrf();
        $data = getJsonInput();

        $stmt = getDb()->prepare("
            INSERT INTO rooms (name, building, floor, capacity, type, status, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['name'] ?? '',
            $data['building'] ?? '',
            (int)($data['floor'] ?? 1),
            (int)($data['capacity'] ?? 30),
            $data['type'] ?? 'Lecture',
            $data['status'] ?? 'available',
            $data['notes'] ?? null,
        ]);
        $roomId = (int)getDb()->lastInsertId();
        auditLog((int)$user['id'], 'create_room', 'room', $roomId);
        jsonResponse(['success' => true, 'room_id' => $roomId], 201);
        break;

    case 'update':
        if ($method !== 'PUT') jsonError('Method not allowed', 405);
        $user = requireRole(['admin']);
        verifyCsrf();
        $data = getJsonInput();
        $id = (int)($data['id'] ?? 0);

        $stmt = getDb()->prepare("
            UPDATE rooms SET name=?, building=?, floor=?, capacity=?, type=?, status=?, notes=?
            WHERE id=?
        ");
        $stmt->execute([
            $data['name'] ?? '',
            $data['building'] ?? '',
            (int)($data['floor'] ?? 1),
            (int)($data['capacity'] ?? 30),
            $data['type'] ?? 'Lecture',
            $data['status'] ?? 'available',
            $data['notes'] ?? null,
            $id,
        ]);
        auditLog((int)$user['id'], 'update_room', 'room', $id);
        jsonResponse(['success' => true]);
        break;

    case 'delete':
        if ($method !== 'DELETE') jsonError('Method not allowed', 405);
        $user = requireRole(['admin']);
        verifyCsrf();
        $id = (int)($_GET['id'] ?? 0);
        $stmt = getDb()->prepare('DELETE FROM rooms WHERE id = ?');
        $stmt->execute([$id]);
        auditLog((int)$user['id'], 'delete_room', 'room', $id);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonError('Unknown action', 404);
}
