<?php

require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

updateNoShows();

switch ($action) {
    // ─── LIST ────────────────────────────────────────────────────────────────
    case 'list':
        $user = requireAuth();
        $db = getDb();

        // Admin can filter by status
        $statusFilter = '';
        $filterParams = [];
        if ($user['role'] === 'admin') {
            $status = $_GET['status'] ?? '';
            if (in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
                $statusFilter = "AND b.status = ?";
                $filterParams[] = $status;
            }
            $search = trim($_GET['search'] ?? '');
            $searchSql = '';
            if ($search !== '') {
                $searchSql = "AND (b.title LIKE ? OR r.name LIKE ? OR u.name LIKE ?)";
                $filterParams[] = "%$search%";
                $filterParams[] = "%$search%";
                $filterParams[] = "%$search%";
            }
            $stmt = $db->prepare("
                SELECT b.*, r.name AS room_name, r.building, u.name AS user_name, u.role AS user_role
                FROM bookings b
                JOIN rooms r ON r.id = b.room_id
                JOIN users u ON u.id = b.user_id
                WHERE 1=1 $statusFilter $searchSql
                ORDER BY b.start_datetime DESC
            ");
            $stmt->execute($filterParams);
        } else {
            $stmt = $db->prepare("
                SELECT b.*, r.name AS room_name, r.building, u.name AS user_name, u.role AS user_role
                FROM bookings b
                JOIN rooms r ON r.id = b.room_id
                JOIN users u ON u.id = b.user_id
                WHERE b.user_id = ?
                ORDER BY b.start_datetime DESC
            ");
            $stmt->execute([$user['id']]);
        }
        jsonResponse(['bookings' => $stmt->fetchAll()]);
        break;

    // ─── CALENDAR ────────────────────────────────────────────────────────────
    case 'calendar':
        requireAuth();
        $start = $_GET['start'] ?? date('Y-m-01');
        $end = $_GET['end'] ?? date('Y-m-t');
        $filter = $_GET['filter'] ?? 'all';

        $db = getDb();
        $events = [];

        if ($filter !== 'maintenance') {
            $statusFilter = match ($filter) {
                'pending'  => "AND b.status = 'pending'",
                'approved' => "AND b.status = 'approved'",
                'student'  => "AND u.role = 'student'",
                'club'     => "AND u.role = 'club'",
                'faculty'  => "AND u.role = 'faculty'",
                default    => '',
            };

            $stmt = $db->prepare("
                SELECT b.*, r.name AS room_name, r.building, u.name AS user_name, u.role AS user_role
                FROM bookings b
                JOIN rooms r ON r.id = b.room_id
                JOIN users u ON u.id = b.user_id
                WHERE b.start_datetime < ? AND b.end_datetime > ?
                $statusFilter
            ");
            $stmt->execute([$end . ' 23:59:59', $start . ' 00:00:00']);
            foreach ($stmt->fetchAll() as $b) {
                $events[] = [
                    'id' => $b['id'],
                    'type' => 'booking',
                    'title' => $b['title'],
                    'start' => $b['start_datetime'],
                    'end' => $b['end_datetime'],
                    'status' => $b['status'],
                    'role' => $b['user_role'],
                    'room_name' => $b['room_name'],
                    'building' => $b['building'],
                    'user_name' => $b['user_name'],
                    'description' => $b['description'],
                    'priority' => $b['priority'],
                ];
            }
        }

        if ($filter === 'all' || $filter === 'maintenance') {
            $stmt = $db->prepare("
                SELECT m.*, r.name AS room_name, r.building
                FROM maintenance m
                JOIN rooms r ON r.id = m.room_id
                WHERE m.start_datetime < ? AND m.end_datetime > ?
            ");
            $stmt->execute([$end . ' 23:59:59', $start . ' 00:00:00']);
            foreach ($stmt->fetchAll() as $m) {
                $events[] = [
                    'id' => $m['id'],
                    'type' => 'maintenance',
                    'title' => 'Maintenance: ' . $m['room_name'],
                    'start' => $m['start_datetime'],
                    'end' => $m['end_datetime'],
                    'status' => 'maintenance',
                    'role' => 'maintenance',
                    'room_name' => $m['room_name'],
                    'building' => $m['building'],
                    'description' => $m['reason'],
                ];
            }
        }

        jsonResponse(['events' => $events]);
        break;

    // ─── SEARCH ──────────────────────────────────────────────────────────────
    case 'search':
        requireAuth();
        $date = $_GET['date'] ?? '';
        $startTime = $_GET['start_time'] ?? '';
        $endTime = $_GET['end_time'] ?? '';
        $minCap = (int)($_GET['min_capacity'] ?? 1);
        $maxCap = (int)($_GET['max_capacity'] ?? 500);
        $facilities = $_GET['facilities'] ?? [];
        if (is_string($facilities)) {
            $facilities = array_filter(array_map('trim', explode(',', $facilities)));
        }

        if (!$date || !$startTime || !$endTime) {
            jsonError('Date, start time, and end time are required');
        }

        $start = "$date $startTime:00";
        $end = "$date $endTime:00";

        if (strtotime($end) <= strtotime($start)) {
            jsonError('End time must be after start time');
        }

        $db = getDb();
        $facilitySql = '';
        $facilityParams = [];
        foreach ($facilities as $facility) {
            $facilitySql .= ' AND (r.notes LIKE ? OR r.type LIKE ?)';
            $facilityParams[] = '%' . $facility . '%';
            $facilityParams[] = '%' . $facility . '%';
        }

        $stmt = $db->prepare("
            SELECT r.* FROM rooms r
            WHERE r.status = 'available'
              AND r.capacity BETWEEN ? AND ?
              $facilitySql
              AND r.id NOT IN (
                  SELECT room_id FROM bookings
                  WHERE status IN ('pending', 'approved')
                    AND start_datetime < ? AND end_datetime > ?
              )
              AND r.id NOT IN (
                  SELECT room_id FROM maintenance
                  WHERE start_datetime < ? AND end_datetime > ?
              )
            ORDER BY r.capacity ASC
        ");
        $stmt->execute(array_merge([$minCap, $maxCap], $facilityParams, [$end, $start, $end, $start]));
        jsonResponse(['rooms' => $stmt->fetchAll()]);
        break;

    // ─── CREATE ──────────────────────────────────────────────────────────────
    case 'create':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $user = requireAuth();
        verifyCsrf();

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $roomId = (int)($_POST['room_id'] ?? 0);
        $startDatetime = $_POST['start_datetime'] ?? '';
        $endDatetime = $_POST['end_datetime'] ?? '';

        if (!$title || !$roomId || !$startDatetime || !$endDatetime) {
            jsonError('Missing required fields');
        }

        $conflict = checkBookingConflict($roomId, $startDatetime, $endDatetime);
        if ($conflict) {
            jsonError($conflict);
        }

        $uploadedPath = null;
        if (!empty($_FILES['attachment']['name'])) {
            $uploadDir = __DIR__ . '/../public/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('booking_') . '.' . $ext;
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . $filename)) {
                $uploadedPath = 'uploads/' . $filename;
            }
        }

        $priority = rolePriority($user['role']);
        $checkinCode = generateCheckinCode();

        $stmt = getDb()->prepare("
            INSERT INTO bookings (user_id, room_id, start_datetime, end_datetime, status, priority, title, description, uploaded_path, checkin_code)
            VALUES (?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user['id'], $roomId, $startDatetime, $endDatetime,
            $priority, $title, $description, $uploadedPath, $checkinCode
        ]);
        $bookingId = (int)getDb()->lastInsertId();

        createNotification((int)$user['id'], 'pending', 'Booking Submitted', "Your booking \"$title\" is pending approval.", "/public/my-bookings.php?highlight=$bookingId");

        $approverStmt = getDb()->prepare("
            SELECT id FROM users
            WHERE role IN ('admin', 'faculty')
              AND is_active = 1
              AND id <> ?
        ");
        $approverStmt->execute([$user['id']]);
        foreach ($approverStmt->fetchAll() as $approver) {
            createNotification(
                (int)$approver['id'],
                'pending',
                'Booking Request Pending',
                "{$user['name']} requested \"$title\" and is waiting for approval.",
                "/public/admin.php?highlight=$bookingId"
            );
        }
        auditLog((int)$user['id'], 'create_booking', 'booking', $bookingId);

        jsonResponse(['success' => true, 'booking_id' => $bookingId, 'checkin_code' => $checkinCode], 201);
        break;

    // ─── UPDATE (admin approve/reject, or admin override) ────────────────────
    case 'update':
        if ($method !== 'PUT') jsonError('Method not allowed', 405);
        $user = requireRole(['admin', 'faculty']);
        verifyCsrf();
        $data = getJsonInput();
        $id = (int)($data['id'] ?? 0);
        $status = $data['status'] ?? '';
        $adminNote = trim($data['admin_note'] ?? '');

        if (!$id || !in_array($status, ['approved', 'rejected', 'cancelled'], true)) {
            jsonError('Invalid update parameters');
        }

        $stmt = getDb()->prepare('SELECT * FROM bookings WHERE id = ?');
        $stmt->execute([$id]);
        $booking = $stmt->fetch();
        if (!$booking) jsonError('Booking not found', 404);

        // Only pending bookings can be approved/rejected; any status can be cancelled by admin
        if ($status !== 'cancelled' && $booking['status'] !== 'pending') {
            jsonError('Only pending bookings can be approved or rejected');
        }

        getDb()->prepare('UPDATE bookings SET status = ? WHERE id = ?')->execute([$status, $id]);

        $notifType = match ($status) {
            'approved' => 'success',
            'rejected' => 'error',
            default    => 'warning',
        };
        $notifMsg = "Your booking \"{$booking['title']}\" has been $status.";
        if ($adminNote) {
            $notifMsg .= " Note: $adminNote";
        }
        createNotification(
            (int)$booking['user_id'],
            $notifType,
            'Booking ' . ucfirst($status),
            $notifMsg,
            "/public/my-bookings.php?highlight=$id"
        );
        auditLog((int)$user['id'], "booking_$status", 'booking', $id,
            $adminNote ? "Admin note: $adminNote" : null);

        jsonResponse(['success' => true]);
        break;

    // ─── ADMIN CANCEL (for invalid/problematic bookings) ─────────────────────
    case 'admin_cancel':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $user = requireRole(['admin']);
        verifyCsrf();
        $data = getJsonInput();
        $id = (int)($data['id'] ?? 0);
        $reason = trim($data['reason'] ?? 'Cancelled by administrator.');

        if (!$id) jsonError('Booking ID is required');

        $stmt = getDb()->prepare('SELECT * FROM bookings WHERE id = ?');
        $stmt->execute([$id]);
        $booking = $stmt->fetch();
        if (!$booking) jsonError('Booking not found', 404);

        if ($booking['status'] === 'cancelled') {
            jsonError('Booking is already cancelled');
        }

        getDb()->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?")->execute([$id]);
        createNotification(
            (int)$booking['user_id'],
            'warning',
            'Booking Cancelled by Admin',
            "Your booking \"{$booking['title']}\" has been cancelled. Reason: $reason",
            "/public/my-bookings.php?highlight=$id"
        );
        auditLog((int)$user['id'], 'admin_cancel_booking', 'booking', $id,
            "Cancelled by admin. Reason: $reason");

        jsonResponse(['success' => true]);
        break;

    // ─── CANCEL (user self-cancel) ────────────────────────────────────────────
    case 'cancel':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $user = requireAuth();
        verifyCsrf();
        $data = getJsonInput();
        $id = (int)($data['id'] ?? 0);

        if (!$id) {
            jsonError('Booking id is required');
        }

        $stmt = getDb()->prepare("
            SELECT * FROM bookings
            WHERE id = ? AND user_id = ? AND status IN ('pending', 'approved')
        ");
        $stmt->execute([$id, $user['id']]);
        $booking = $stmt->fetch();

        if (!$booking) {
            jsonError('Booking cannot be cancelled', 404);
        }

        getDb()->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?")->execute([$id]);
        createNotification(
            (int)$user['id'],
            'warning',
            'Booking Cancelled',
            "Your booking \"{$booking['title']}\" has been cancelled.",
            "/public/my-bookings.php?highlight=$id"
        );
        auditLog((int)$user['id'], 'booking_cancelled', 'booking', $id);

        jsonResponse(['success' => true]);
        break;

    // ─── CHECKIN ─────────────────────────────────────────────────────────────
    case 'checkin':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $user = requireAuth();
        $data = getJsonInput();
        $code = strtoupper(trim($data['code'] ?? ''));

        $stmt = getDb()->prepare("
            SELECT * FROM bookings
            WHERE checkin_code = ? AND user_id = ? AND status = 'approved'
              AND start_datetime <= DATE_ADD(NOW(), INTERVAL 15 MINUTE)
              AND end_datetime > NOW()
        ");
        $stmt->execute([$code, $user['id']]);
        $booking = $stmt->fetch();

        if (!$booking) {
            jsonError('Invalid or expired check-in code');
        }

        getDb()->prepare('UPDATE bookings SET checked_in_at = NOW(), no_show = 0 WHERE id = ?')
               ->execute([$booking['id']]);

        jsonResponse(['success' => true, 'booking_id' => $booking['id']]);
        break;

    // ─── GET ─────────────────────────────────────────────────────────────────
    case 'get':
        requireAuth();
        $id = (int)($_GET['id'] ?? 0);
        $stmt = getDb()->prepare("
            SELECT b.*, r.name AS room_name, r.building, r.floor, u.name AS user_name, u.role AS user_role
            FROM bookings b
            JOIN rooms r ON r.id = b.room_id
            JOIN users u ON u.id = b.user_id
            WHERE b.id = ?
        ");
        $stmt->execute([$id]);
        $booking = $stmt->fetch();
        if (!$booking) jsonError('Booking not found', 404);
        jsonResponse(['booking' => $booking]);
        break;

    default:
        jsonError('Unknown action', 404);
}
