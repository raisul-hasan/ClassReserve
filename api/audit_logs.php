<?php
require_once __DIR__ . '/helpers.php';

require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

$limit = min(max((int) ($_GET['limit'] ?? 100), 1), 200);
$stmt = $pdo->prepare("SELECT a.id, a.user_id, a.action, a.target_type, a.target_id, a.details, a.created_at,
    u.name AS user_name, u.email AS user_email, u.role AS user_role
    FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC, a.id DESC LIMIT {$limit}");
$stmt->execute();
json_response($stmt->fetchAll());
