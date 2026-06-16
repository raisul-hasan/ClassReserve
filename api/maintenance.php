<?php

require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $db = getDb();
        $roomId = (int)($_GET['room_id'] ?? 0);

        $sql = "
            SELECT m.*, r.name AS room_name, r.building, u.name AS created_by_name
            FROM maintenance m
            JOIN rooms r ON r.id = m.room_id
            JOIN users u ON u.id = m.created_by
        ";
        $params = [];
        if ($roomId) {
            $sql .= ' WHERE m.room_id = ?';
            $params[] = $roomId;
        }
        $sql .= ' ORDER BY m.start_datetime DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        jsonResponse(['maintenance' => $stmt->fetchAll()]);
        break;

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

        $stmt = getDb()->prepare("
            INSERT INTO maintenance (room_id, start_datetime, end_datetime, reason, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$roomId, $start, $end, $reason, $user['id']]);

        getDb()->prepare("UPDATE rooms SET status = 'maintenance' WHERE id = ?")->execute([$roomId]);

        $maintId = (int)getDb()->lastInsertId();
        auditLog((int)$user['id'], 'create_maintenance', 'maintenance', $maintId);
        jsonResponse(['success' => true, 'id' => $maintId], 201);
        break;

    case 'delete':
        if ($method !== 'DELETE') jsonError('Method not allowed', 405);
        $user = requireRole(['admin']);
        verifyCsrf();
        $id = (int)($_GET['id'] ?? 0);

        $stmt = getDb()->prepare('SELECT room_id FROM maintenance WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) jsonError('Not found', 404);

        getDb()->prepare('DELETE FROM maintenance WHERE id = ?')->execute([$id]);
        getDb()->prepare("UPDATE rooms SET status = 'available' WHERE id = ?")->execute([$row['room_id']]);

        auditLog((int)$user['id'], 'delete_maintenance', 'maintenance', $id);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonError('Unknown action', 404);
}
