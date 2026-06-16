<?php

require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

switch ($action) {
    // ─── LIST ────────────────────────────────────────────────────────────────
    case 'list':
        $user = requireAuth();
        $db = getDb();
        $unreadOnly = ($_GET['unread'] ?? '') === '1';
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 50)));

        $sql = 'SELECT * FROM notifications WHERE user_id = ?';
        if ($unreadOnly) $sql .= ' AND is_read = 0';
        $sql .= ' ORDER BY created_at DESC LIMIT ' . $limit;

        $stmt = $db->prepare($sql);
        $stmt->execute([$user['id']]);
        jsonResponse(['notifications' => $stmt->fetchAll()]);
        break;

    // ─── COUNT ────────────────────────────────────────────────────────────────
    case 'count':
        $user = requireAuth();
        $stmt = getDb()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$user['id']]);
        jsonResponse(['unread_count' => (int)$stmt->fetchColumn()]);
        break;

    // ─── MARK READ ────────────────────────────────────────────────────────────
    case 'mark_read':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $user = requireAuth();
        $data = getJsonInput();
        $id = (int)($data['id'] ?? 0);

        if ($id) {
            $stmt = getDb()->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
            $stmt->execute([$id, $user['id']]);
        } else {
            $stmt = getDb()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
            $stmt->execute([$user['id']]);
        }

        jsonResponse(['success' => true]);
        break;

    // ─── DELETE SINGLE ────────────────────────────────────────────────────────
    case 'delete':
        if ($method !== 'DELETE') jsonError('Method not allowed', 405);
        $user = requireAuth();
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) jsonError('Notification ID is required');

        $stmt = getDb()->prepare('DELETE FROM notifications WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $user['id']]);
        jsonResponse(['success' => true]);
        break;

    // ─── DELETE ALL READ ──────────────────────────────────────────────────────
    case 'clear_read':
        if ($method !== 'DELETE') jsonError('Method not allowed', 405);
        $user = requireAuth();
        $stmt = getDb()->prepare('DELETE FROM notifications WHERE user_id = ? AND is_read = 1');
        $stmt->execute([$user['id']]);
        jsonResponse(['success' => true, 'deleted' => $stmt->rowCount()]);
        break;

    default:
        jsonError('Unknown action', 404);
}
