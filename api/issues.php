<?php
require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$user = require_login();

$allowedCategories = [
    'Maintenance Problem',
    'Schedule Conflict',
    'Projector/Equipment Issue',
    'AC/Fan/Light Problem',
    'Cleanliness Issue',
    'Furniture Problem',
    'Capacity Problem',
    'Other',
];
$allowedStatuses = ['Open', 'Under Review', 'In Progress', 'Resolved', 'Rejected'];
$allowedPriorities = ['Low', 'Medium', 'High', 'Urgent'];

function find_room_by_name($pdo, $roomName)
{
    $stmt = $pdo->prepare('SELECT id, name FROM rooms WHERE name = ? LIMIT 1');
    $stmt->execute([$roomName]);
    return $stmt->fetch();
}

function fetch_issue_comments($pdo, $issueId)
{
    $stmt = $pdo->prepare('SELECT id, author_name, author_role, message, created_at FROM issue_comments WHERE issue_id = ? ORDER BY created_at');
    $stmt->execute([$issueId]);
    return $stmt->fetchAll();
}

function save_uploaded_attachment($fieldName = 'attachment')
{
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $uploadsDir = __DIR__ . '/../public/uploads';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }

    $originalName = basename($_FILES[$fieldName]['name']);
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $newName = uniqid('issue_', true) . ($extension ? '.' . $extension : '');
    $target = $uploadsDir . '/' . $newName;

    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $target)) {
        return null;
    }

    return 'uploads/' . $newName;
}

function issue_row($pdo, $row)
{
    $row['comments'] = fetch_issue_comments($pdo, $row['id']);
    $row['has_document'] = (bool) $row['has_document'];
    $row['is_affecting_booking'] = (bool) $row['is_affecting_booking'];
    return $row;
}

if ($method === 'GET') {
    $conditions = [];
    $params = [];

    if (!empty($_GET['id'])) {
        $conditions[] = 'i.id = ?';
        $params[] = (int) $_GET['id'];
    }
    if (!empty($_GET['status']) && $_GET['status'] !== 'all') {
        $conditions[] = 'i.status = ?';
        $params[] = $_GET['status'];
    }
    if (!empty($_GET['category']) && $_GET['category'] !== 'all') {
        $conditions[] = 'i.category = ?';
        $params[] = $_GET['category'];
    }
    if (!empty($_GET['mine'])) {
        $conditions[] = 'i.user_id = ?';
        $params[] = $user['id'];
    }

    $sql = "SELECT i.*, u.name AS posted_by, u.role AS user_role
            FROM issues i
            LEFT JOIN users u ON i.user_id = u.id";
    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sql .= ' ORDER BY i.created_at DESC, i.id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $issues = array_map(function ($row) use ($pdo) {
        return issue_row($pdo, $row);
    }, $stmt->fetchAll());

    if (!empty($_GET['id'])) {
        json_response($issues[0] ?? null);
    }
    json_response($issues);
}

if ($method !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$uploadedPath = null;
if (stripos($contentType, 'multipart/form-data') !== false) {
    $input = $_POST;
    $uploadedPath = save_uploaded_attachment();
} else {
    $input = get_json_input();
}
$action = $input['action'] ?? 'create';

if ($action === 'comment') {
    $issueId = (int) ($input['issue_id'] ?? 0);
    $message = clean_string($input['message'] ?? '');
    if ($issueId <= 0 || $message === '') {
        json_response(['error' => 'Issue and comment message are required.'], 422);
    }

    $stmt = $pdo->prepare('INSERT INTO issue_comments (issue_id, user_id, author_name, author_role, message) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$issueId, $user['id'], $user['name'], $user['role'], $message]);

    $issueStmt = $pdo->prepare('SELECT user_id, title FROM issues WHERE id = ?');
    $issueStmt->execute([$issueId]);
    $issue = $issueStmt->fetch();
    if ($issue && (int) $issue['user_id'] !== (int) $user['id']) {
        create_notification($pdo, $issue['user_id'], 'info', 'New Issue Comment', $user['name'] . ' commented on "' . $issue['title'] . '".');
    }

    json_response(['ok' => true, 'id' => (int) $pdo->lastInsertId()]);
}

if ($action === 'upvote') {
    $issueId = (int) ($input['issue_id'] ?? 0);
    if ($issueId <= 0) {
        json_response(['error' => 'Issue is required.'], 422);
    }

    $stmt = $pdo->prepare('UPDATE issues SET upvotes = upvotes + 1, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$issueId]);
    json_response(['ok' => true]);
}

if ($action === 'status') {
    require_role('admin');
    $issueId = (int) ($input['issue_id'] ?? 0);
    $status = clean_string($input['status'] ?? '');
    $adminResponse = clean_string($input['admin_response'] ?? '');
    $rejectionReason = clean_string($input['reason'] ?? '');

    if ($issueId <= 0 || !in_array($status, ['Under Review', 'In Progress', 'Resolved', 'Rejected'], true)) {
        json_response(['error' => 'A valid issue and status are required.'], 422);
    }

    $stmt = $pdo->prepare('UPDATE issues SET status = ?, admin_response = COALESCE(NULLIF(?, ""), admin_response), rejection_reason = COALESCE(NULLIF(?, ""), rejection_reason), updated_at = NOW() WHERE id = ?');
    $stmt->execute([$status, $adminResponse, $rejectionReason, $issueId]);

    $issueStmt = $pdo->prepare('SELECT user_id, title FROM issues WHERE id = ?');
    $issueStmt->execute([$issueId]);
    $issue = $issueStmt->fetch();
    if ($issue) {
        $type = $status === 'Resolved' ? 'success' : ($status === 'Rejected' ? 'error' : 'info');
        create_notification($pdo, $issue['user_id'], $type, 'Issue Status Updated', '"' . $issue['title'] . '" is now ' . $status . '.');
    }

    json_response(['ok' => true]);
}

if ($action === 'maintenance_from_issue') {
    require_role('admin');
    $issueId = (int) ($input['issue_id'] ?? 0);
    $start = clean_string($input['start_datetime'] ?? '');
    $end = clean_string($input['end_datetime'] ?? '');
    $reason = clean_string($input['reason'] ?? '');

    if ($issueId <= 0 || !valid_datetime($start) || !valid_datetime($end) || strtotime($end) <= strtotime($start)) {
        json_response(['error' => 'Valid issue, start time, and end time are required.'], 422);
    }

    $stmt = $pdo->prepare('SELECT user_id, room_id, room_name, description FROM issues WHERE id = ?');
    $stmt->execute([$issueId]);
    $issue = $stmt->fetch();
    if (!$issue) {
        json_response(['error' => 'Issue not found.'], 404);
    }

    $roomId = (int) ($issue['room_id'] ?? 0);
    if ($roomId <= 0) {
        $room = find_room_by_name($pdo, $issue['room_name']);
        $roomId = (int) ($room['id'] ?? 0);
    }
    if ($roomId <= 0) {
        json_response(['error' => 'Could not match this issue to a room.'], 422);
    }

    $stmt = $pdo->prepare('INSERT INTO maintenance (room_id, start_datetime, end_datetime, reason) VALUES (?, ?, ?, ?)');
    $stmt->execute([$roomId, $start, $end, $reason ?: $issue['description']]);
    $maintenanceId = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare('UPDATE issues SET status = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute(['In Progress', $issueId]);

    create_notification($pdo, $issue['user_id'] ?? null, 'warning', 'Maintenance Scheduled', 'Maintenance has been scheduled for ' . $issue['room_name'] . '.');

    json_response(['ok' => true, 'id' => $maintenanceId]);
}

$title = clean_string($input['title'] ?? '');
$roomName = clean_string($input['room_name'] ?? $input['roomName'] ?? '');
$category = clean_string($input['category'] ?? '');
$description = clean_string($input['description'] ?? '');
$priority = clean_string($input['priority'] ?? 'Medium');

if ($title === '' || $roomName === '' || $category === '' || $description === '') {
    json_response(['error' => 'Title, room, category, and description are required.'], 422);
}
if (!in_array($category, $allowedCategories, true)) {
    json_response(['error' => 'Invalid issue category.'], 422);
}
if (!in_array($priority, $allowedPriorities, true)) {
    $priority = 'Medium';
}

$room = find_room_by_name($pdo, $roomName);
$roomId = $room ? (int) $room['id'] : null;
$hasDocument = (!empty($input['has_document']) || !empty($input['hasDocument']) || $uploadedPath) ? 1 : 0;
$isAffectingBooking = !empty($input['is_affecting_booking']) || !empty($input['isAffectingBooking']) ? 1 : 0;
$relatedBooking = clean_string($input['related_booking'] ?? $input['relatedBooking'] ?? '');

$stmt = $pdo->prepare('INSERT INTO issues (user_id, room_id, title, room_name, category, description, priority, has_document, uploaded_path, is_affecting_booking, related_booking) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([
    $user['id'],
    $roomId,
    $title,
    $roomName,
    $category,
    $description,
    $priority,
    $hasDocument,
    $uploadedPath,
    $isAffectingBooking,
    $relatedBooking ?: null,
]);

$issueId = (int) $pdo->lastInsertId();
$stmt = $pdo->prepare("SELECT i.*, u.name AS posted_by, u.role AS user_role FROM issues i LEFT JOIN users u ON i.user_id = u.id WHERE i.id = ?");
$stmt->execute([$issueId]);
$createdIssue = issue_row($pdo, $stmt->fetch());
create_role_notification($pdo, ['admin'], 'warning', 'New Issue Report', $title . ' was reported for ' . $roomName . '.');
json_response($createdIssue, 201);
