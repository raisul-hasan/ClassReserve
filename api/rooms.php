<?php
// Room endpoints: list for users; create/update/disable/delete for admins.
require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$allowedStatuses = ['available', 'booked', 'maintenance', 'disabled'];
$parsedInput = null;

function room_input($input, $existing = [])
{
    return [
        'name' => clean_string($input['name'] ?? $existing['name'] ?? ''),
        'capacity' => (int) ($input['capacity'] ?? $existing['capacity'] ?? 0),
        'type' => clean_string($input['type'] ?? $existing['type'] ?? ''),
        'building' => clean_string($input['building'] ?? $existing['building'] ?? ''),
        'floor' => clean_string($input['floor'] ?? $existing['floor'] ?? ''),
        'equipment' => clean_string($input['equipment'] ?? $existing['equipment'] ?? ''),
        'status' => clean_string($input['status'] ?? $existing['status'] ?? 'available'),
        'notes' => clean_string($input['notes'] ?? $existing['notes'] ?? ''),
    ];
}

function validate_room_input($room, $allowedStatuses)
{
    if ($room['name'] === '' || $room['building'] === '' || $room['type'] === '') {
        json_response(['error' => 'Room name, building, and type are required.'], 400);
    }
    if ($room['capacity'] <= 0) {
        json_response(['error' => 'Capacity must be a positive number.'], 400);
    }
    if (!in_array($room['status'], $allowedStatuses, true)) {
        json_response(['error' => 'Invalid room status.'], 400);
    }
}

if ($method === 'GET') {
    $sql = 'SELECT r.*, (
        SELECT GROUP_CONCAT(e.name ORDER BY e.name SEPARATOR ", ")
        FROM room_equipment re
        JOIN equipment e ON re.equipment_id = e.id
        WHERE re.room_id = r.id
    ) AS equipment FROM rooms r';
    
    $where = [];
    $params = [];

    if (!empty($_GET['id'])) {
        $where[] = 'r.id = ?';
        $params[] = (int) $_GET['id'];
    }
    if (isset($_GET['capacity_min'])) { 
        $where[] = 'r.capacity >= ?'; 
        $params[] = (int) $_GET['capacity_min']; 
    }
    if (isset($_GET['capacity_max'])) { 
        $where[] = 'r.capacity <= ?'; 
        $params[] = (int) $_GET['capacity_max']; 
    }
    if (!empty($_GET['type'])) { 
        $where[] = 'r.type = ?'; 
        $params[] = $_GET['type']; 
    }
    if (!empty($_GET['building'])) { 
        $where[] = 'r.building = ?'; 
        $params[] = $_GET['building']; 
    }
    if (!empty($_GET['status'])) { 
        $where[] = 'r.status = ?'; 
        $params[] = $_GET['status']; 
    }
    if (!empty($_GET['equipment'])) { 
        $where[] = 'r.id IN (
            SELECT re.room_id 
            FROM room_equipment re 
            JOIN equipment e ON re.equipment_id = e.id 
            WHERE e.name = ?
        )'; 
        $params[] = $_GET['equipment']; 
    }
    if (!empty($_GET['q'])) { 
        $where[] = '(r.name LIKE ? OR r.building LIKE ? OR EXISTS (
            SELECT 1 FROM room_equipment re 
            JOIN equipment e ON re.equipment_id = e.id 
            WHERE re.room_id = r.id AND e.name LIKE ?
        ))'; 
        $params[] = '%' . $_GET['q'] . '%'; 
        $params[] = '%' . $_GET['q'] . '%'; 
        $params[] = '%' . $_GET['q'] . '%'; 
    }

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY r.name';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rooms = $stmt->fetchAll();

    if (!empty($_GET['id'])) {
        json_response($rooms[0] ?? null);
    }
    json_response($rooms);
}

if ($method === 'POST') {
    $admin = require_role('admin');
    $input = get_json_input();
    $action = $input['action'] ?? 'create';

    if ($action === 'update') {
        $parsedInput = $input;
        $method = 'PUT';
    } elseif ($action === 'disable') {
        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0 || !find_room($pdo, $id)) {
            json_response(['error' => 'Room not found.'], 404);
        }

        $stmt = $pdo->prepare("UPDATE rooms SET status = 'disabled' WHERE id = ?");
        $stmt->execute([$id]);
        create_audit_log($pdo, $admin['id'], 'room_updated', 'room', $id, ['status' => 'disabled']);
        json_response(['ok' => true, 'id' => $id, 'status' => 'disabled']);
    } elseif ($action === 'delete') {
        $_GET['id'] = $input['id'] ?? null;
        $method = 'DELETE';
    } elseif ($action !== 'create') {
        json_response(['error' => 'Unknown action.'], 400);
    } else {
        $room = room_input($input);
        validate_room_input($room, $allowedStatuses);

        $stmt = $pdo->prepare('INSERT INTO rooms (name, capacity, type, building, floor, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$room['name'], $room['capacity'], $room['type'], $room['building'], $room['floor'] ?: null, $room['status'], $room['notes'] ?: null]);
        $roomId = (int) $pdo->lastInsertId();

        // Save equipment relationally
        $eqInput = clean_string($input['equipment'] ?? '');
        $eqList = explode(',', $eqInput);
        
        $insEq = $pdo->prepare("INSERT IGNORE INTO equipment (name) VALUES (?)");
        $getEqId = $pdo->prepare("SELECT id FROM equipment WHERE name = ?");
        $insRoomEq = $pdo->prepare("INSERT IGNORE INTO room_equipment (room_id, equipment_id) VALUES (?, ?)");
        
        foreach ($eqList as $eqName) {
            $eqName = trim($eqName);
            if ($eqName === '') continue;
            
            $insEq->execute([$eqName]);
            $getEqId->execute([$eqName]);
            $eqId = $getEqId->fetchColumn();
            if ($eqId) {
                $insRoomEq->execute([$roomId, $eqId]);
            }
        }

        create_audit_log($pdo, $admin['id'], 'room_created', 'room', $roomId, ['name' => $room['name'], 'status' => $room['status']]);
        json_response(['ok' => true, 'id' => $roomId], 201);
    }
}

if ($method === 'PUT') {
    $admin = require_role('admin');
    $input = $parsedInput ?? get_json_input();
    $id = (int) ($input['id'] ?? $_GET['id'] ?? 0);
    $existing = $id > 0 ? find_room($pdo, $id) : null;
    if (!$existing) {
        json_response(['error' => 'Room not found.'], 404);
    }

    $room = room_input($input, $existing);
    validate_room_input($room, $allowedStatuses);

    $stmt = $pdo->prepare('UPDATE rooms SET name = ?, capacity = ?, type = ?, building = ?, floor = ?, status = ?, notes = ? WHERE id = ?');
    $stmt->execute([$room['name'], $room['capacity'], $room['type'], $room['building'], $room['floor'] ?: null, $room['status'], $room['notes'] ?: null, $id]);

    // Save equipment relationally
    $delRoomEq = $pdo->prepare("DELETE FROM room_equipment WHERE room_id = ?");
    $delRoomEq->execute([$id]);

    $eqInput = clean_string($input['equipment'] ?? '');
    $eqList = explode(',', $eqInput);
    
    $insEq = $pdo->prepare("INSERT IGNORE INTO equipment (name) VALUES (?)");
    $getEqId = $pdo->prepare("SELECT id FROM equipment WHERE name = ?");
    $insRoomEq = $pdo->prepare("INSERT IGNORE INTO room_equipment (room_id, equipment_id) VALUES (?, ?)");
    
    foreach ($eqList as $eqName) {
        $eqName = trim($eqName);
        if ($eqName === '') continue;
        
        $insEq->execute([$eqName]);
        $getEqId->execute([$eqName]);
        $eqId = $getEqId->fetchColumn();
        if ($eqId) {
            $insRoomEq->execute([$id, $eqId]);
        }
    }

    create_audit_log($pdo, $admin['id'], 'room_updated', 'room', $id, ['name' => $room['name'], 'status' => $room['status']]);
    json_response(['ok' => true, 'id' => $id]);
}

if ($method === 'DELETE') {
    require_role('admin');
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0 || !find_room($pdo, $id)) {
        json_response(['error' => 'Room not found.'], 404);
    }

    if (room_has_active_bookings($pdo, $id)) {
        $stmt = $pdo->prepare("UPDATE rooms SET status = 'disabled' WHERE id = ?");
        $stmt->execute([$id]);
        json_response(['ok' => true, 'id' => $id, 'status' => 'disabled', 'message' => 'Room has active bookings, so it was disabled instead of deleted.']);
    }

    $stmt = $pdo->prepare('DELETE FROM rooms WHERE id = ?');
    $stmt->execute([$id]);
    json_response(['ok' => true, 'id' => $id, 'deleted' => true]);
}

json_response(['error' => 'Method not allowed'], 405);
