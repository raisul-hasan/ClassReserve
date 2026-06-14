<?php
// Maintenance endpoints: visible to logged-in users; managed by admins.
require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$parsedInput = null;

function find_maintenance($pdo, $id)
{
    $stmt = $pdo->prepare('SELECT * FROM maintenance WHERE id = ?');
    $stmt->execute([(int) $id]);
    return $stmt->fetch();
}

function maintenance_input($input, $existing = [])
{
    return [
        'room_id' => (int) ($input['room_id'] ?? $existing['room_id'] ?? 0),
        'start_datetime' => clean_string($input['start_datetime'] ?? $existing['start_datetime'] ?? ''),
        'end_datetime' => clean_string($input['end_datetime'] ?? $existing['end_datetime'] ?? ''),
        'reason' => clean_string($input['reason'] ?? $existing['reason'] ?? ''),
    ];
}

function validate_maintenance_input($pdo, $data, $excludeMaintenanceId = null)
{
    if ($data['room_id'] <= 0 || !valid_datetime($data['start_datetime']) || !valid_datetime($data['end_datetime']) || strtotime($data['end_datetime']) <= strtotime($data['start_datetime'])) {
        json_response(['error' => 'Valid room, start time, and end time are required.'], 422);
    }
    if (!find_room($pdo, $data['room_id'])) {
        json_response(['error' => 'Room not found.'], 404);
    }
    if (booking_conflict_exists($pdo, $data['room_id'], $data['start_datetime'], $data['end_datetime'])) {
        json_response(['error' => 'Cannot schedule maintenance over an existing pending or approved booking.'], 409);
    }
    if (maintenance_conflict_exists($pdo, $data['room_id'], $data['start_datetime'], $data['end_datetime'], $excludeMaintenanceId)) {
        json_response(['error' => 'Maintenance is already scheduled for this room during that time.'], 409);
    }
}

if ($method === 'GET') {
    require_login();

    $sql = 'SELECT m.*, r.name as room_name, r.building FROM maintenance m JOIN rooms r ON m.room_id = r.id';
    $params = [];
    if (!empty($_GET['id'])) {
        $sql .= ' WHERE m.id = ?';
        $params[] = (int) $_GET['id'];
    }
    $sql .= ' ORDER BY m.start_datetime';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    if (!empty($_GET['id'])) {
        json_response($rows[0] ?? null);
    }
    json_response($rows);
}

if ($method === 'POST') {
    $admin = require_role('admin');
    $input = get_json_input();
    $action = $input['action'] ?? 'create';

    if ($action === 'update') {
        $parsedInput = $input;
        $method = 'PUT';
    } elseif ($action === 'delete') {
        $_GET['id'] = $input['id'] ?? null;
        $method = 'DELETE';
    } elseif ($action !== 'create') {
        json_response(['error' => 'Unknown action.'], 400);
    } else {
        $data = maintenance_input($input);
        validate_maintenance_input($pdo, $data);

        $stmt = $pdo->prepare('INSERT INTO maintenance (room_id, start_datetime, end_datetime, reason) VALUES (?, ?, ?, ?)');
        $stmt->execute([$data['room_id'], $data['start_datetime'], $data['end_datetime'], $data['reason'] ?: null]);
        $maintenanceId = (int) $pdo->lastInsertId();
        create_audit_log($pdo, $admin['id'], 'maintenance_created', 'maintenance', $maintenanceId, $data);

        // Notify active admin and faculty users of new maintenance block
        $mRoom = find_room($pdo, $data['room_id']);
        $stmtAlert = $pdo->prepare("SELECT email, name FROM users WHERE role IN ('admin', 'faculty') AND is_active = 1");
        $stmtAlert->execute();
        foreach ($stmtAlert->fetchAll() as $alertUser) {
            $mSubject = "ClassReserve: Maintenance Block Scheduled";
            $mMessage = "<p>Hello " . htmlspecialchars($alertUser['name']) . ",</p>"
                     . "<p>A maintenance block has been scheduled for <strong>" . htmlspecialchars($mRoom['name'] ?? 'Room') . "</strong>.</p>"
                     . "<p><strong>Time:</strong> " . htmlspecialchars($data['start_datetime']) . " to " . htmlspecialchars($data['end_datetime']) . "<br>"
                     . "<strong>Reason:</strong> " . htmlspecialchars($data['reason']) . "</p>"
                     . "<p>Thank you,<br>ClassReserve Team</p>";
            send_email($alertUser['email'], $mSubject, $mMessage);
        }

        json_response(['ok' => true, 'id' => $maintenanceId], 201);
    }
}

if ($method === 'PUT') {
    require_role('admin');
    $input = $parsedInput ?? get_json_input();
    $id = (int) ($input['id'] ?? $_GET['id'] ?? 0);
    $existing = $id > 0 ? find_maintenance($pdo, $id) : null;
    if (!$existing) {
        json_response(['error' => 'Maintenance block not found.'], 404);
    }

    $data = maintenance_input($input, $existing);
    validate_maintenance_input($pdo, $data, $id);

    $stmt = $pdo->prepare('UPDATE maintenance SET room_id = ?, start_datetime = ?, end_datetime = ?, reason = ? WHERE id = ?');
    $stmt->execute([$data['room_id'], $data['start_datetime'], $data['end_datetime'], $data['reason'] ?: null, $id]);
    json_response(['ok' => true, 'id' => $id]);
}

if ($method === 'DELETE') {
    require_role('admin');
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0 || !find_maintenance($pdo, $id)) {
        json_response(['error' => 'Maintenance block not found.'], 404);
    }

    $stmt = $pdo->prepare('DELETE FROM maintenance WHERE id = ?');
    $stmt->execute([$id]);
    json_response(['ok' => true, 'id' => $id, 'deleted' => true]);
}

json_response(['error' => 'Method not allowed'], 405);
