<?php
require_once __DIR__ . '/bootstrap.php';

$user = requireRole(['admin']);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

switch ($action) {
    // ─── LIST with optional filters ──────────────────────────────────────────
    case 'list':
        $db = getDb();

        $actionFilter = trim($_GET['action_filter'] ?? '');
        $userFilter   = trim($_GET['user_search'] ?? '');
        $dateFrom     = trim($_GET['date_from'] ?? '');
        $dateTo       = trim($_GET['date_to'] ?? '');
        $limit        = max(1, min(500, (int)($_GET['limit'] ?? 200)));
        $offset       = max(0, (int)($_GET['offset'] ?? 0));

        $sql = "
            SELECT a.*, u.name AS user_name, u.role AS user_role
            FROM audit_logs a
            LEFT JOIN users u ON u.id = a.user_id
            WHERE 1=1
        ";
        $params = [];

        if ($actionFilter !== '') {
            $sql .= ' AND a.action LIKE ?';
            $params[] = "%$actionFilter%";
        }

        if ($userFilter !== '') {
            $sql .= ' AND u.name LIKE ?';
            $params[] = "%$userFilter%";
        }

        if ($dateFrom !== '') {
            $sql .= ' AND a.created_at >= ?';
            $params[] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo !== '') {
            $sql .= ' AND a.created_at <= ?';
            $params[] = $dateTo . ' 23:59:59';
        }

        $sql .= ' ORDER BY a.created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        // Total count for pagination
        $countSql = "
            SELECT COUNT(*)
            FROM audit_logs a
            LEFT JOIN users u ON u.id = a.user_id
            WHERE 1=1
        ";
        $countParams = array_slice($params, 0, count($params) - 2); // remove limit/offset
        $countStmt = $db->prepare(str_replace(
            'ORDER BY a.created_at DESC LIMIT ? OFFSET ?',
            '',
            $sql
        ));
        $countStmt->execute($countParams);
        $total = (int)$countStmt->fetchColumn();

        jsonResponse(['logs' => $logs, 'total' => $total, 'limit' => $limit, 'offset' => $offset]);
        break;

    default:
        jsonError('Action not found', 404);
}
