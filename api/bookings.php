<?php
// Simple booking endpoints: list and create with conflict check
require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$user = require_login();

if ($method === 'GET') {
    $sql = 'SELECT b.*, u.name as user_name, u.email as user_email, u.role as user_role, r.name as room_name, r.building, reviewer.name as reviewer_name FROM bookings b LEFT JOIN users u ON b.user_id = u.id LEFT JOIN rooms r ON b.room_id = r.id LEFT JOIN users reviewer ON b.reviewed_by = reviewer.id';
    $where = [];
    $params = [];
    if (!in_array($user['role'], ['admin', 'faculty'], true)) {
        $where[] = '(b.status = ? OR b.user_id = ?)';
        $params[] = 'approved';
        $params[] = $user['id'];
    } elseif (!empty($_GET['user_id'])) {
        $where[] = 'b.user_id = ?';
        $params[] = $_GET['user_id'];
    }
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
        $uploaded_path = save_uploaded_attachment('attachment', 'booking_');
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
        if (in_array($action, ['approve', 'reject'], true) && !in_array($user['role'], ['admin', 'faculty'], true)) {
            json_response(['error' => 'Only faculty or admin can approve or reject bookings.'], 403);
        }
        $detailsStmt = $pdo->prepare('SELECT b.user_id, b.room_id, b.title, b.start_datetime, b.status, r.name AS room_name FROM bookings b LEFT JOIN rooms r ON b.room_id = r.id WHERE b.id = ?');
        $detailsStmt->execute([$id]);
        $bookingDetails = $detailsStmt->fetch();
        if (!$bookingDetails) {
            json_response(['error' => 'Booking not found.'], 404);
        }
        if ($action === 'cancel' && !in_array($user['role'], ['admin', 'faculty'], true) && (int) $bookingDetails['user_id'] !== (int) $user['id']) {
            json_response(['error' => 'You can only cancel your own bookings.'], 403);
        }
        if (in_array($bookingDetails['status'], ['rejected', 'cancelled'], true)) {
            json_response(['error' => 'This booking can no longer be updated.'], 409);
        }

        $newStatus = $action === 'approve' ? 'approved' : ($action === 'reject' ? 'rejected' : 'cancelled');
        $reason = clean_string($input['reason'] ?? '');
        if ($action === 'reject' && $reason === '') {
            json_response(['error' => 'A rejection reason is required.'], 422);
        }
        if (in_array($action, ['approve', 'reject'], true)) {
            $stmt = $pdo->prepare('UPDATE bookings SET status = ?, reviewed_by = ?, reviewed_at = NOW(), rejection_reason = ? WHERE id = ?');
            $stmt->execute([$newStatus, $user['id'], $action === 'reject' ? $reason : null, $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?');
            $stmt->execute([$newStatus, $id]);
        }

        if ($bookingDetails && in_array($action, ['approve', 'reject'], true)) {
            $title = $action === 'approve' ? 'Booking Approved' : 'Booking Rejected';
            $type = $action === 'approve' ? 'success' : 'error';
            $roomName = $bookingDetails['room_name'] ?: 'your selected room';
            $bookingTitle = $bookingDetails['title'] ?: 'Your booking';
            $message = $bookingTitle . ' for ' . $roomName . ' on ' . $bookingDetails['start_datetime'] . ' has been ' . $newStatus . '.';
            create_notification($pdo, $bookingDetails['user_id'], $type, $title, $message);
        }

        create_audit_log($pdo, $user['id'], 'booking_' . ($action === 'cancel' ? 'cancelled' : ($action === 'approve' ? 'approved' : 'rejected')), 'booking', $id, [
            'title' => $bookingDetails['title'],
            'room_id' => (int) $bookingDetails['room_id'],
            'room_name' => $bookingDetails['room_name'],
            'reason' => $reason ?: null,
        ]);

        echo json_encode(['ok' => true]);
        exit;
    }

    // create booking (JSON or form)
    $start = $input['start_datetime'] ?? null;
    $end = $input['end_datetime'] ?? null;
    $room_id = $input['room_id'] ?? null;
    if (!$start || !$end || !$room_id) { json_response(['error' => 'Missing required fields'], 400); }
    if (!valid_datetime($start) || !valid_datetime($end)) { json_response(['error' => 'Invalid date/time'], 400); }
    if (strtotime($end) <= strtotime($start)) { json_response(['error' => 'End time must be after start time'], 400); }

    $room = find_room($pdo, $room_id);
    if (!$room) { json_response(['error' => 'Room not found'], 404); }
    if ($room['status'] !== 'available') { json_response(['error' => 'Room is not available for booking'], 409); }

    // Pending bookings also block duplicate requests while they wait for review.
    if (booking_conflict_exists($pdo, $room_id, $start, $end)) {
        json_response(['error' => 'Time conflict with an existing pending or approved booking'], 409);
    }

    if (maintenance_conflict_exists($pdo, $room_id, $start, $end)) {
        json_response(['error' => 'Room is blocked for maintenance during that time'], 409);
    }

    $priority = $user['role'] === 'faculty' ? 3 : ($user['role'] === 'club' ? 2 : 1);

    $stmt = $pdo->prepare('INSERT INTO bookings (user_id, room_id, start_datetime, end_datetime, status, priority, title, description, uploaded_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $user['id'],
        $room_id,
        $start,
        $end,
        'pending',
        $priority,
        $input['title'] ?? null,
        $input['description'] ?? null,
        $uploaded_path
    ]);
    $bookingId = $pdo->lastInsertId();
    create_audit_log($pdo, $user['id'], 'booking_created', 'booking', $bookingId, [
        'title' => $input['title'] ?? null,
        'room_id' => (int) $room_id,
        'start_datetime' => $start,
        'end_datetime' => $end,
    ]);
    create_notification(
        $pdo,
        $user['id'],
        'pending',
        'Booking Request Submitted',
        ($input['title'] ?? 'Your booking request') . ' is waiting for approval.'
    );
    create_role_notification(
        $pdo,
        ['admin', 'faculty'],
        'pending',
        'New Booking Request',
        ($input['title'] ?? 'A booking request') . ' is waiting for approval.'
    );
    echo json_encode(['ok' => true, 'id' => $bookingId]);
    exit;
}

if ($method === 'DELETE') {
    // cancel by id: /api/bookings.php?id=123
    $id = $_GET['id'] ?? null;
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }
    $detailsStmt = $pdo->prepare('SELECT id, user_id, room_id, title FROM bookings WHERE id = ?');
    $detailsStmt->execute([$id]);
    $bookingDetails = $detailsStmt->fetch();
    if (!$bookingDetails) { json_response(['error' => 'Booking not found.'], 404); }
    if (in_array($user['role'], ['admin', 'faculty'], true)) {
        $stmt = $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?');
        $stmt->execute(['cancelled', $id]);
    } else {
        $stmt = $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ? AND user_id = ?');
        $stmt->execute(['cancelled', $id, $user['id']]);
    }
    if ($stmt->rowCount() === 0) { json_response(['error' => 'You can only cancel your own bookings.'], 403); }
    create_audit_log($pdo, $user['id'], 'booking_cancelled', 'booking', $id, [
        'title' => $bookingDetails['title'],
        'room_id' => (int) $bookingDetails['room_id'],
    ]);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
