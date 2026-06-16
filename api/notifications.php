<?php

require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $user = requireAuth();
        $unreadOnly = ($_GET['unread'] ?? '') === '1';

        $sql = 'SELECT * FROM notifications WHERE user_id = ?';
        if ($unreadOnly) $sql .= ' AND is_read = 0';
        $sql .= ' ORDER BY created_at DESC LIMIT 50';

        $stmt = getDb()->prepare($sql);
        $stmt->execute([$user['id']]);
        jsonResponse(['notifications' => $stmt->fetchAll()]);
        break;

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

    case 'count':
        $user = requireAuth();
        $stmt = getDb()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$user['id']]);
        jsonResponse(['unread_count' => (int)$stmt->fetchColumn()]);
        break;

    default:
        jsonError('Unknown action', 404);
}
