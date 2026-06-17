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

        $whereSql = ' WHERE 1=1';
        $params = [];

        $sql = "
            SELECT a.*, u.name AS user_name, u.role AS user_role
            FROM audit_logs a
            LEFT JOIN users u ON u.id = a.user_id
            $whereSql
        ";

        if ($actionFilter !== '') {
            $whereSql .= ' AND a.action LIKE ?';
            $params[] = "%$actionFilter%";
        }

        if ($userFilter !== '') {
            $whereSql .= ' AND u.name LIKE ?';
            $params[] = "%$userFilter%";
        }

        if ($dateFrom !== '') {
            $whereSql .= ' AND a.created_at >= ?';
            $params[] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo !== '') {
            $whereSql .= ' AND a.created_at <= ?';
            $params[] = $dateTo . ' 23:59:59';
        }

        $sql = "
            SELECT a.*, u.name AS user_name, u.role AS user_role
            FROM audit_logs a
            LEFT JOIN users u ON u.id = a.user_id
            $whereSql
        ";
        $sql .= ' ORDER BY a.created_at DESC LIMIT ? OFFSET ?';
        $stmt = $db->prepare($sql);
        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }
        $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll();

        $countSql = "
            SELECT COUNT(*)
            FROM audit_logs a
            LEFT JOIN users u ON u.id = a.user_id
            $whereSql
        ";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        jsonResponse(['logs' => $logs, 'total' => $total, 'limit' => $limit, 'offset' => $offset]);
        break;

    default:
        jsonError('Action not found', 404);
}
