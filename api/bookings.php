<?php
// Simple booking endpoints: list and create with conflict check
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sql = 'SELECT b.*, u.name as user_name, r.name as room_name FROM bookings b LEFT JOIN users u ON b.user_id = u.id LEFT JOIN rooms r ON b.room_id = r.id';
    $where = [];
    $params = [];
    if (!empty($_GET['user_id'])) { $where[] = 'b.user_id = ?'; $params[] = $_GET['user_id']; }
    if (!empty($_GET['room_id'])) { $where[] = 'b.room_id = ?'; $params[] = $_GET['room_id']; }
    if (!empty($_GET['status'])) { $where[] = 'b.status = ?'; $params[] = $_GET['status']; }
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' ORDER BY b.start_datetime';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($method === 'POST') {
    // support JSON body or multipart/form-data (for file uploads)
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $uploaded_path = null;
    if (stripos($contentType, 'multipart/form-data') !== false) {
        $input = $_POST;
        $action = $input['action'] ?? null;
        // handle uploaded file named 'attachment'
        if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $uploadsDir = __DIR__ . '/../public/uploads';
            if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
            $orig = basename($_FILES['attachment']['name']);
            $ext = pathinfo($orig, PATHINFO_EXTENSION);
            $newName = uniqid('att_', true) . ($ext ? '.' . $ext : '');
            $target = $uploadsDir . '/' . $newName;
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target)) {
                $uploaded_path = 'uploads/' . $newName;
            }
        }
    } else {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $action = $input['action'] ?? null;
        $uploaded_path = $input['uploaded_path'] ?? null;
    }

    // action-based updates: approve/reject/cancel
    if (!empty($action)) {
        if (empty($input['id'])) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }
        $id = $input['id'];
        if (!in_array($action, ['approve','reject','cancel'])) { http_response_code(400); echo json_encode(['error' => 'Invalid action']); exit; }
        $newStatus = $action === 'approve' ? 'approved' : ($action === 'reject' ? 'rejected' : 'cancelled');
        $stmt = $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?');
        $stmt->execute([$newStatus, $id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    // create booking (JSON or form)
    $start = $input['start_datetime'] ?? null;
    $end = $input['end_datetime'] ?? null;
    $room_id = $input['room_id'] ?? null;
    if (!$start || !$end || !$room_id) { http_response_code(400); echo json_encode(['error' => 'Missing required fields']); exit; }

    // basic conflict detection: check approved bookings
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
        $uploaded_path
    ]);
    echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

if ($method === 'DELETE') {
    // cancel by id: /api/bookings.php?id=123
    $id = $_GET['id'] ?? null;
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }
    $stmt = $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?');
    $stmt->execute(['cancelled', $id]);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
