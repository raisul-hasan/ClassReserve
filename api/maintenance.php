<?php

require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

switch ($action) {
    // ─── LIST ────────────────────────────────────────────────────────────────
    case 'list':
        $db = getDb();
        $roomId = (int)($_GET['room_id'] ?? 0);
        $filter = $_GET['filter'] ?? 'all'; // all, active, upcoming, past

        $sql = "
            SELECT m.*, r.name AS room_name, r.building, u.name AS created_by_name
            FROM maintenance m
            JOIN rooms r ON r.id = m.room_id
            JOIN users u ON u.id = m.created_by
            WHERE 1=1
        ";
        $params = [];

        if ($roomId) {
            $sql .= ' AND m.room_id = ?';
            $params[] = $roomId;
        }

        if ($filter === 'active') {
            $sql .= ' AND m.start_datetime <= NOW() AND m.end_datetime >= NOW()';
        } elseif ($filter === 'upcoming') {
            $sql .= ' AND m.start_datetime > NOW()';
        } elseif ($filter === 'past') {
            $sql .= ' AND m.end_datetime < NOW()';
        }

        $sql .= ' ORDER BY m.start_datetime DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        jsonResponse(['maintenance' => $stmt->fetchAll()]);
        break;

    // ─── CREATE ──────────────────────────────────────────────────────────────
    case 'create':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $user = requireRole(['admin']);
        verifyCsrf();
        $data = getJsonInput();

        $roomId = (int)($data['room_id'] ?? 0);
        $start = $data['start_datetime'] ?? '';
        $end = $data['end_datetime'] ?? '';
        $reason = trim($data['reason'] ?? '');

        if (!$roomId || !$start || !$end || !$reason) {
            jsonError('All fields are required');
        }

        if (strtotime($end) <= strtotime($start)) {
            jsonError('End datetime must be after start datetime');
        }

        $db = getDb();

        // Check if room exists
        $stmt = $db->prepare('SELECT id, name FROM rooms WHERE id = ?');
        $stmt->execute([$roomId]);
        $room = $stmt->fetch();
        if (!$room) jsonError('Room not found', 404);

        $stmt = $db->prepare("
            INSERT INTO maintenance (room_id, start_datetime, end_datetime, reason, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$roomId, $start, $end, $reason, $user['id']]);

        $db->prepare("UPDATE rooms SET status = 'maintenance' WHERE id = ?")->execute([$roomId]);

        $maintId = (int)$db->lastInsertId();
        auditLog((int)$user['id'], 'create_maintenance', 'maintenance', $maintId,
            "Room: {$room['name']}, Reason: $reason");

        // Notify users with approved bookings during this maintenance window
        $affectedStmt = $db->prepare("
            SELECT DISTINCT b.user_id, b.id AS booking_id, b.title
            FROM bookings b
            WHERE b.room_id = ?
              AND b.status = 'approved'
              AND b.start_datetime < ?
              AND b.end_datetime > ?
        ");
        $affectedStmt->execute([$roomId, $end, $start]);
        $affected = $affectedStmt->fetchAll();
        foreach ($affected as $ab) {
            createNotification(
                (int)$ab['user_id'],
                'warning',
                'Room Maintenance Notice',
                "Room \"{$room['name']}\" has been scheduled for maintenance overlapping your booking \"{$ab['title']}\". Please contact admin for rescheduling.",
                "/public/my-bookings.php?highlight={$ab['booking_id']}"
            );
        }

        jsonResponse(['success' => true, 'id' => $maintId,
            'affected_bookings' => count($affected)], 201);
        break;

    // ─── UPDATE ──────────────────────────────────────────────────────────────
    case 'update':
        if ($method !== 'PUT') jsonError('Method not allowed', 405);
        $user = requireRole(['admin']);
        verifyCsrf();
        $data = getJsonInput();
        $id = (int)($data['id'] ?? 0);

        if (!$id) jsonError('Maintenance ID is required');

        $db = getDb();
        $stmt = $db->prepare('SELECT * FROM maintenance WHERE id = ?');
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        if (!$existing) jsonError('Maintenance record not found', 404);

        $roomId = (int)($data['room_id'] ?? $existing['room_id']);
        $start  = $data['start_datetime'] ?? $existing['start_datetime'];
        $end    = $data['end_datetime']   ?? $existing['end_datetime'];
        $reason = trim($data['reason']    ?? $existing['reason']);

        if (strtotime($end) <= strtotime($start)) {
            jsonError('End datetime must be after start datetime');
        }

        $db->prepare("
            UPDATE maintenance SET room_id=?, start_datetime=?, end_datetime=?, reason=?
            WHERE id=?
        ")->execute([$roomId, $start, $end, $reason, $id]);

        auditLog((int)$user['id'], 'update_maintenance', 'maintenance', $id);
        jsonResponse(['success' => true]);
        break;

    // ─── DELETE ──────────────────────────────────────────────────────────────
    case 'delete':
        if ($method !== 'DELETE') jsonError('Method not allowed', 405);
        $user = requireRole(['admin']);
        verifyCsrf();
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) jsonError('Maintenance ID is required');

        $db = getDb();
        $stmt = $db->prepare('SELECT room_id FROM maintenance WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) jsonError('Not found', 404);

        $roomId = (int)$row['room_id'];
        $db->prepare('DELETE FROM maintenance WHERE id = ?')->execute([$id]);

        // Only restore room to 'available' if no other maintenance blocks exist for it
        $remainStmt = $db->prepare("
            SELECT COUNT(*) FROM maintenance WHERE room_id = ? AND end_datetime >= NOW()
        ");
        $remainStmt->execute([$roomId]);
        if ((int)$remainStmt->fetchColumn() === 0) {
            $db->prepare("UPDATE rooms SET status = 'available' WHERE id = ? AND status = 'maintenance'")
               ->execute([$roomId]);
        }

        auditLog((int)$user['id'], 'delete_maintenance', 'maintenance', $id);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonError('Unknown action', 404);
}
