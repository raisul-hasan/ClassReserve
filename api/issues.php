<?php

require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $db = getDb();
        $category = $_GET['category'] ?? '';
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';

        $sql = "
            SELECT i.*, u.name AS user_name, u.role AS user_role,
                   rb.title AS related_booking_title,
                   rb.start_datetime AS related_booking_start,
                   rb.end_datetime AS related_booking_end,
                   rr.name AS related_booking_room,
                   (SELECT COUNT(*) FROM comments c WHERE c.issue_id = i.id) AS comment_count
            FROM issues i
            JOIN users u ON u.id = i.user_id
            LEFT JOIN bookings rb ON rb.id = i.related_booking
            LEFT JOIN rooms rr ON rr.id = rb.room_id
            WHERE 1=1
        ";
        $params = [];

        if ($category) {
            $sql .= ' AND i.category = ?';
            $params[] = $category;
        }
        if ($status) {
            $sql .= ' AND i.status = ?';
            $params[] = $status;
        }
        if ($search) {
            $sql .= ' AND (i.title LIKE ? OR i.description LIKE ? OR i.room_name LIKE ? OR i.category LIKE ? OR u.name LIKE ?)';
            $like = "%$search%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= ' ORDER BY i.upvotes DESC, i.created_at DESC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $issues = array_map(static function (array $issue): array {
            $issue['issue_id'] = (int)$issue['id'];
            $issue['room_code'] = $issue['room_name'];
            $issue['upvote_count'] = (int)$issue['upvotes'];
            $issue['comment_count'] = (int)$issue['comment_count'];
            $issue['conflict_warning'] = ($issue['category'] === 'Scheduling' || (int)($issue['is_affecting_booking'] ?? 0) === 1)
                ? 'This issue may involve a booking or scheduling conflict.'
                : null;
            return $issue;
        }, $stmt->fetchAll());

        jsonResponse(['issues' => $issues]);
        break;

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = getDb()->prepare("
            SELECT i.*, u.name AS user_name, u.role AS user_role,
                   rb.title AS related_booking_title,
                   rb.start_datetime AS related_booking_start,
                   rb.end_datetime AS related_booking_end,
                   rr.name AS related_booking_room,
                   (SELECT COUNT(*) FROM comments c WHERE c.issue_id = i.id) AS comment_count
            FROM issues i
            JOIN users u ON u.id = i.user_id
            LEFT JOIN bookings rb ON rb.id = i.related_booking
            LEFT JOIN rooms rr ON rr.id = rb.room_id
            WHERE i.id = ?
        ");
        $stmt->execute([$id]);
        $issue = $stmt->fetch();
        if (!$issue) jsonError('Issue not found', 404);
        $issue['issue_id'] = (int)$issue['id'];
        $issue['room_code'] = $issue['room_name'];
        $issue['upvote_count'] = (int)$issue['upvotes'];
        $issue['comment_count'] = (int)$issue['comment_count'];
        $issue['conflict_warning'] = ($issue['category'] === 'Scheduling' || (int)($issue['is_affecting_booking'] ?? 0) === 1)
            ? 'This issue may involve a booking or scheduling conflict.'
            : null;

        $stmt = getDb()->prepare("
            SELECT c.*, u.name AS user_name
            FROM comments c
            JOIN users u ON u.id = c.user_id
            WHERE c.issue_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$id]);

        jsonResponse(['issue' => $issue, 'comments' => $stmt->fetchAll()]);
        break;

    case 'create':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $user = requireAuth();
        verifyCsrf();

        $title = trim($_POST['title'] ?? '');
        $roomName = trim($_POST['room_name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $priority = $_POST['priority'] ?? 'Medium';
        $isAffecting = (int)($_POST['is_affecting_booking'] ?? 0);
        $relatedBookingValue = trim((string)($_POST['related_booking'] ?? ''));
        $relatedBooking = $relatedBookingValue !== '' ? (int)$relatedBookingValue : null;

        if (!$title || !$roomName || !$category || !$description) {
            jsonError('All required fields must be filled');
        }

        $db = getDb();
        $stmt = $db->prepare("
            INSERT INTO issues (user_id, title, room_name, category, description, priority, is_affecting_booking, related_booking)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user['id'], $title, $roomName, $category, $description, $priority, $isAffecting, $relatedBooking]);
        $issueId = (int)$db->lastInsertId();

        auditLog((int)$user['id'], 'create_issue', 'issue', $issueId);
        jsonResponse(['success' => true, 'issue_id' => $issueId], 201);
        break;

    case 'upvote':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $user = requireAuth();
        $data = getJsonInput();
        $id = (int)($data['id'] ?? 0);
        if (!$id) jsonError('Issue ID is required');

        $stmt = getDb()->prepare('SELECT id FROM issues WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) jsonError('Issue not found', 404);

        if (tableExists('issue_upvotes')) {
            $stmt = getDb()->prepare('INSERT IGNORE INTO issue_upvotes (issue_id, user_id) VALUES (?, ?)');
            $stmt->execute([$id, (int)$user['id']]);
            if ($stmt->rowCount() > 0) {
                getDb()->prepare('UPDATE issues SET upvotes = upvotes + 1 WHERE id = ?')->execute([$id]);
            }
        } else {
            getDb()->prepare('UPDATE issues SET upvotes = upvotes + 1 WHERE id = ?')->execute([$id]);
        }

        $stmt = getDb()->prepare('SELECT upvotes FROM issues WHERE id = ?');
        $stmt->execute([$id]);
        jsonResponse(['success' => true, 'upvotes' => (int)$stmt->fetchColumn(), 'duplicate_prevented' => tableExists('issue_upvotes')]);
        break;

    case 'comment':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $user = requireAuth();
        verifyCsrf();
        $data = getJsonInput();
        $issueId = (int)($data['issue_id'] ?? 0);
        $message = trim($data['message'] ?? '');

        if (!$issueId || !$message) jsonError('Issue ID and message required');

        $stmt = getDb()->prepare('SELECT id FROM issues WHERE id = ?');
        $stmt->execute([$issueId]);
        if (!$stmt->fetch()) jsonError('Issue not found', 404);

        $stmt = getDb()->prepare('INSERT INTO comments (issue_id, user_id, message) VALUES (?, ?, ?)');
        $stmt->execute([$issueId, $user['id'], $message]);
        $commentId = (int)getDb()->lastInsertId();

        $stmt = getDb()->prepare("
            SELECT c.*, u.name AS user_name, u.role AS user_role
            FROM comments c
            JOIN users u ON u.id = c.user_id
            WHERE c.id = ?
        ");
        $stmt->execute([$commentId]);

        jsonResponse(['success' => true, 'comment_id' => $commentId, 'comment' => $stmt->fetch()], 201);
        break;

    case 'update_status':
        if ($method !== 'PUT') jsonError('Method not allowed', 405);
        $user = requireRole(['admin']);
        verifyCsrf();
        $data = getJsonInput();
        $id = (int)($data['id'] ?? 0);
        $status = $data['status'] ?? '';
        $adminResponse = trim($data['admin_response'] ?? '');

        $validStatuses = ['Open', 'Under Review', 'In Progress', 'Resolved', 'Rejected'];
        if (!$id || !in_array($status, $validStatuses, true)) {
            jsonError('Invalid status update');
        }

        $stmt = getDb()->prepare('UPDATE issues SET status = ?, admin_response = ? WHERE id = ?');
        $stmt->execute([$status, $adminResponse ?: null, $id]);

        $stmt = getDb()->prepare('SELECT user_id, title FROM issues WHERE id = ?');
        $stmt->execute([$id]);
        $issue = $stmt->fetch();
        if ($issue) {
            createNotification(
                (int)$issue['user_id'],
                'info',
                'Issue Updated',
                "Your issue \"{$issue['title']}\" status changed to $status.",
                "/public/forum.php?issue=$id"
            );
        }

        auditLog((int)$user['id'], 'update_issue_status', 'issue', $id);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonError('Unknown action', 404);
}
