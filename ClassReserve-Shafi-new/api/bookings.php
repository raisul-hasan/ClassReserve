<?php

require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

updateNoShows();

function bookingColumnNames(PDO $db): array
{
    static $columns = null;
    if ($columns !== null) {
        return $columns;
    }

    $columns = [];
    foreach ($db->query('SHOW COLUMNS FROM bookings') as $column) {
        $columns[$column['Field']] = true;
    }

    return $columns;
}

function missingFacultyAcademicBookingColumns(PDO $db): array
{
    $columns = bookingColumnNames($db);
    $required = ['course_code', 'section', 'batch', 'department'];

    return array_values(array_filter($required, static function (string $column) use ($columns): bool {
        return empty($columns[$column]);
    }));
}

switch ($action) {
    case 'list':
        $user = requireAuth();
        $db = getDb();

        if ($user['role'] === 'admin') {
            $stmt = $db->query("
                SELECT b.*, r.name AS room_name, r.building, u.name AS user_name, u.role AS user_role
                FROM bookings b
                JOIN rooms r ON r.id = b.room_id
                JOIN users u ON u.id = b.user_id
                ORDER BY b.start_datetime DESC
            ");
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
                  AND b.status IN ('pending', 'approved')
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
                    'user_id' => (int)$b['user_id'],
                    'purpose' => $b['purpose'] ?? '',
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

    case 'search':
        $user = requireAuth();
        $date = $_GET['date'] ?? '';
        $startTime = $_GET['start_time'] ?? '';
        $endTime = $_GET['end_time'] ?? '';
        $minCap = (int)($_GET['min_capacity'] ?? 1);
        $maxCap = (int)($_GET['max_capacity'] ?? 500);

        if (!$date || !$startTime || !$endTime) {
            jsonError('Date, start time, and end time are required');
        }

        $start = "$date $startTime:00";
        $end = "$date $endTime:00";
        $startTimestamp = strtotime($start);
        $endTimestamp = strtotime($end);

        if ($startTimestamp === false || $endTimestamp === false || $endTimestamp <= $startTimestamp) {
            jsonError('Invalid time');
        }

        $building = trim($_GET['building'] ?? '');
        $roomType = trim($_GET['type'] ?? '');
        $start = date('Y-m-d H:i:s', $startTimestamp);
        $end = date('Y-m-d H:i:s', $endTimestamp);

        $db = getDb();
        $sql = "SELECT r.* FROM rooms r WHERE r.status = 'available' AND r.capacity BETWEEN ? AND ?";
        $params = [$minCap, $maxCap];

        if ($building !== '') {
            $sql .= ' AND r.building = ?';
            $params[] = $building;
        }
        if ($roomType !== '') {
            $sql .= ' AND r.type = ?';
            $params[] = $roomType;
        }

        $sql .= " AND r.id NOT IN (
                  SELECT room_id FROM bookings
                  WHERE status IN ('pending', 'approved')
                    AND start_datetime < ? AND end_datetime > ?
              )
              AND r.id NOT IN (
                  SELECT room_id FROM maintenance
                  WHERE start_datetime < ? AND end_datetime > ?
              )
            ORDER BY r.capacity ASC";

        $params[] = $end;
        $params[] = $start;
        $params[] = $end;
        $params[] = $start;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        jsonResponse(['rooms' => $stmt->fetchAll()]);
        break;

    case 'create':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $user = requireAuth();
        verifyCsrf();

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $roomId = (int)($_POST['room_id'] ?? 0);
        $startDatetime = $_POST['start_datetime'] ?? '';
        $endDatetime = $_POST['end_datetime'] ?? '';
        $attendees = (int)($_POST['attendees'] ?? 1);
        $purpose = trim($_POST['purpose'] ?? '');
        $courseCode = trim($_POST['course_code'] ?? '');
        $section = trim($_POST['section'] ?? '');
        $batch = trim($_POST['batch'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $facultyClassTypes = [
            'Regular Class',
            'Extra Class',
            'Makeup Class',
            'Lab Class',
            'Exam',
            'Quiz',
            'Presentation',
            'Viva',
            'Seminar',
            'Workshop',
            'Faculty Meeting',
            'Department Meeting',
            'Consultation Hour',
            'Other',
        ];

        if (!$title || !$roomId || !$startDatetime || !$endDatetime || !$purpose) {
            jsonError('Missing required fields');
        }

        if ($attendees <= 0) {
            jsonError('Expected participants must be positive');
        }

        $startTimestamp = strtotime($startDatetime);
        $endTimestamp = strtotime($endDatetime);
        if ($startTimestamp === false || $endTimestamp === false || $endTimestamp <= $startTimestamp) {
            jsonError('Invalid time');
        }
        $startDatetime = date('Y-m-d H:i:s', $startTimestamp);
        $endDatetime = date('Y-m-d H:i:s', $endTimestamp);

        if ($user['role'] === 'faculty' && $description === '') {
            jsonError('Description is required');
        }

        if ($user['role'] === 'faculty' && !in_array($purpose, $facultyClassTypes, true)) {
            jsonError('Invalid class type');
        }

        if ($user['role'] === 'faculty' && (!$courseCode || !$section || !$batch || !$department)) {
            jsonError('Course code, section, batch, and department are required');
        }

        $db = getDb();
        $roomStmt = $db->prepare('SELECT id, capacity, status FROM rooms WHERE id = ?');
        $roomStmt->execute([$roomId]);
        $room = $roomStmt->fetch();

        if (!$room) {
            jsonError('Room not found', 404);
        }

        if ($room['status'] === 'maintenance') {
            jsonError('Room under maintenance');
        }

        if ($room['status'] !== 'available') {
            jsonError('Room is not available for booking');
        }

        if ((int)$room['capacity'] < $attendees) {
            jsonError('Capacity not enough');
        }

        $priority = rolePriority($user['role']);
        $status = $user['role'] === 'faculty' ? 'approved' : 'pending';
        $conflict = checkBookingConflict($roomId, $startDatetime, $endDatetime, $priority);
        if ($conflict) {
            jsonError($conflict);
        }

        $facultyAcademicValues = [];
        if ($user['role'] === 'faculty') {
            $missingColumns = missingFacultyAcademicBookingColumns($db);
            if ($missingColumns) {
                jsonError('Database migration required for faculty academic fields: ' . implode(', ', $missingColumns), 500);
            }

            $facultyAcademicValues = [
                'course_code' => $courseCode,
                'section' => $section,
                'batch' => $batch,
                'department' => $department,
            ];
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

        $checkinCode = generateCheckinCode();

        $insertColumns = [
            'user_id',
            'room_id',
            'start_datetime',
            'end_datetime',
            'status',
            'priority',
            'title',
            'description',
            'uploaded_path',
            'checkin_code',
            'attendees',
            'purpose',
        ];
        $insertValues = [
            $user['id'], $roomId, $startDatetime, $endDatetime,
            $status, $priority, $title, $description, $uploadedPath, $checkinCode,
            $attendees, $purpose
        ];

        foreach ($facultyAcademicValues as $column => $value) {
            $insertColumns[] = $column;
            $insertValues[] = $value;
        }

        $quotedColumns = array_map(static function (string $column): string {
            return "`$column`";
        }, $insertColumns);
        $placeholders = array_fill(0, count($insertColumns), '?');

        $stmt = $db->prepare(
            'INSERT INTO bookings (' . implode(', ', $quotedColumns) . ') VALUES (' . implode(', ', $placeholders) . ')'
        );
        $stmt->execute($insertValues);

        $bookingId = (int)$db->lastInsertId();
        $userNotificationType = $status === 'approved' ? 'success' : 'pending';
        $userNotificationTitle = $status === 'approved' ? 'Booking Approved' : 'Booking Submitted';
        $userNotificationMessage = $status === 'approved'
            ? "Your booking \"$title\" has been automatically approved."
            : "Your booking \"$title\" is pending approval.";

        createNotification((int)$user['id'], $userNotificationType, $userNotificationTitle, $userNotificationMessage);

        if ($status === 'pending') {
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
                    "{$user['name']} requested \"{$title}\" and is waiting for approval."
                );
            }
        }
        auditLog((int)$user['id'], 'create_booking', 'booking', $bookingId);

        jsonResponse(['success' => true, 'booking_id' => $bookingId, 'checkin_code' => $checkinCode], 201);
        break;

    case 'update':
        if ($method !== 'PUT') jsonError('Method not allowed', 405);
        $data = getJsonInput();
        $id = (int)($data['id'] ?? 0);
        $status = $data['status'] ?? '';
        $note = trim($data['note'] ?? '');

        if (!$id || !in_array($status, ['approved', 'rejected', 'cancelled'], true)) {
            jsonError('Invalid update parameters');
        }

        $stmt = getDb()->prepare('SELECT * FROM bookings WHERE id = ?');
        $stmt->execute([$id]);
        $booking = $stmt->fetch();
        if (!$booking) jsonError('Booking not found', 404);

        $user = $_SESSION['user'] ?? null;
        if (!$user) jsonError('Authentication required', 401);

        $allowUpdate = false;
        $isReviewer = in_array($user['role'], ['admin', 'faculty'], true);

        if ($status === 'cancelled') {
            if ($booking['user_id'] === $user['id'] && $booking['status'] === 'pending') {
                $allowUpdate = true;
            }
            if ($user['role'] === 'admin') {
                $allowUpdate = true;
            }
        } elseif (in_array($status, ['approved', 'rejected'], true)) {
            if ($isReviewer) {
                $allowUpdate = true;
            }
        }

        if (!$allowUpdate) {
            jsonError('Insufficient permissions for this action', 403);
        }

        if (in_array($status, ['approved', 'rejected'], true)) {
            verifyCsrf();
            if ($status === 'approved') {
                $conflict = checkBookingConflict(
                    (int)$booking['room_id'],
                    $booking['start_datetime'],
                    $booking['end_datetime'],
                    (int)$booking['priority'],
                    $id
                );
                if ($conflict) {
                    jsonError($conflict);
                }
            }

            $columns = bookingColumnNames(getDb());
            $setParts = ['status = ?'];
            $values = [$status];

            if (!empty($columns['reviewed_by'])) {
                $setParts[] = 'reviewed_by = ?';
                $values[] = $user['id'];
            }
            if (!empty($columns['review_note'])) {
                $setParts[] = 'review_note = ?';
                $values[] = $note ?: null;
            }
            if (!empty($columns['reviewed_at'])) {
                $setParts[] = 'reviewed_at = NOW()';
            }

            $values[] = $id;
            $stmt = getDb()->prepare('UPDATE bookings SET ' . implode(', ', $setParts) . ' WHERE id = ?');
            $stmt->execute($values);
        } else {
            // cancellation flow for request owner or admin
            $stmt = getDb()->prepare('UPDATE bookings SET status = ? WHERE id = ?');
            $stmt->execute([$status, $id]);
        }

        $notifType = match ($status) {
            'approved' => 'success',
            'rejected' => 'error',
            'cancelled' => 'warning',
        };
        createNotification(
            (int)$booking['user_id'],
            $notifType,
            'Booking ' . ucfirst($status),
            "Your booking \"{$booking['title']}\" has been $status." . ($note ? " Note: $note" : '')
        );
        auditLog((int)$user['id'], "booking_$status", 'booking', $id);

        jsonResponse(['success' => true]);
        break;

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

        $stmt = getDb()->prepare('UPDATE bookings SET checked_in_at = NOW(), no_show = 0 WHERE id = ?');
        $stmt->execute([$booking['id']]);

        jsonResponse(['success' => true, 'booking_id' => $booking['id']]);
        break;

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

    case 'pending':
        $user = requireRole(['admin', 'faculty']);
        $db = getDb();

        if ($user['role'] === 'faculty') {
            $stmt = $db->prepare(
                "SELECT b.*, r.name AS room_name, r.building, u.name AS user_name, u.role AS user_role
                 FROM bookings b
                 JOIN rooms r ON r.id = b.room_id
                 JOIN users u ON u.id = b.user_id
                 WHERE b.status = 'pending' AND u.role IN ('student', 'club')
                 ORDER BY b.priority DESC, b.start_datetime ASC, b.created_at ASC"
            );
            $stmt->execute();
        } else {
            $stmt = $db->prepare(
                "SELECT b.*, r.name AS room_name, r.building, u.name AS user_name, u.role AS user_role
                 FROM bookings b
                 JOIN rooms r ON r.id = b.room_id
                 JOIN users u ON u.id = b.user_id
                 WHERE b.status = 'pending'
                 ORDER BY b.priority DESC, b.start_datetime ASC, b.created_at ASC"
            );
            $stmt->execute();
        }

        jsonResponse(['bookings' => $stmt->fetchAll()]);
        break;

    default:
        jsonError('Unknown action', 404);
}
