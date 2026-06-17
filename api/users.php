<?php

require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

// All actions require authentication
$user = requireRole(['admin']);

switch ($action) {
    // ─── LIST ────────────────────────────────────────────────────────────────
    case 'list':
        if ($method !== 'GET') jsonError('Method not allowed', 405);
        $db = getDb();

        $search = trim($_GET['search'] ?? '');
        $role   = $_GET['role']   ?? '';
        $status = $_GET['status'] ?? '';

        $sql = "
            SELECT u.id, u.name, u.email, u.role, u.is_active, u.created_at,
                   COUNT(b.id) AS booking_count
            FROM users u
            LEFT JOIN bookings b ON b.user_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if ($role && in_array($role, ['admin', 'faculty', 'club', 'student'], true)) {
            $sql .= ' AND u.role = ?';
            $params[] = $role;
        }

        if ($status === 'active') {
            $sql .= ' AND u.is_active = 1';
        } elseif ($status === 'suspended') {
            $sql .= ' AND u.is_active = 0';
        }

        if ($search !== '') {
            $sql .= ' AND (u.name LIKE ? OR u.email LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= ' GROUP BY u.id ORDER BY u.name ASC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        jsonResponse(['users' => $stmt->fetchAll()]);
        break;

    // ─── CREATE ──────────────────────────────────────────────────────────────
    case 'create':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        verifyCsrf();
        $data = getJsonInput();

        $name     = trim($data['name'] ?? '');
        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $role     = $data['role'] ?? 'student';
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

        if (!$name || !$email || !$password) {
            jsonError('Name, email, and password are required');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonError('Invalid email address');
        }
        if (!in_array($role, ['admin', 'faculty', 'club', 'student'], true)) {
            jsonError('Invalid role');
        }
        if (strlen($password) < 6) {
            jsonError('Password must be at least 6 characters');
        }

        $db = getDb();
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            jsonError('Email already registered');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $passwordColumn = tableColumnExists('users', 'password') ? 'password' : 'password_hash';
        $stmt = $db->prepare(
            "INSERT INTO users (name, email, $passwordColumn, role, is_active) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$name, $email, $hash, $role, $isActive]);
        $userId = (int)$db->lastInsertId();

        auditLog((int)$user['id'], 'create_user', 'user', $userId, "Created user: $name ($email) as $role");
        createNotification($userId, 'success', 'Welcome to ClassReserve',
            'Your account has been created by an administrator. You can log in using your email and the password provided.');
        jsonResponse(['success' => true, 'user_id' => $userId], 201);
        break;

    // ─── UPDATE ──────────────────────────────────────────────────────────────
    case 'update':
        if ($method !== 'PUT') jsonError('Method not allowed', 405);
        verifyCsrf();
        $data = getJsonInput();
        $id = (int)($data['id'] ?? 0);

        if (!$id) {
            jsonError('User ID is required');
        }

        // Prevent self-modification of role / status
        if ($id === (int)$user['id'] && (isset($data['role']) || isset($data['is_active']))) {
            jsonError('You cannot modify your own role or active status.');
        }

        $db = getDb();
        $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $target = $stmt->fetch();
        if (!$target) jsonError('User not found', 404);

        $fields = [];
        $params = [];

        if (isset($data['name'])) {
            $name = trim($data['name']);
            if (!$name) jsonError('Name cannot be empty');
            $fields[] = 'name = ?';
            $params[] = $name;
        }
        if (isset($data['email'])) {
            $email = trim($data['email']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonError('Invalid email address');
            // Check uniqueness (exclude self)
            $stmt2 = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $stmt2->execute([$email, $id]);
            if ($stmt2->fetch()) jsonError('Email already in use');
            $fields[] = 'email = ?';
            $params[] = $email;
        }
        if (isset($data['role'])) {
            if (!in_array($data['role'], ['admin', 'faculty', 'club', 'student'], true)) {
                jsonError('Invalid role');
            }
            $fields[] = 'role = ?';
            $params[] = $data['role'];
        }
        if (isset($data['is_active'])) {
            $ia = (int)$data['is_active'];
            if ($ia !== 0 && $ia !== 1) jsonError('Invalid active status');
            $fields[] = 'is_active = ?';
            $params[] = $ia;
        }

        if (empty($fields)) {
            jsonError('No fields to update');
        }

        $params[] = $id;
        $stmt = $db->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?');
        $stmt->execute($params);

        auditLog((int)$user['id'], 'update_user', 'user', $id,
            "Updated: " . implode(', ', $fields));
        jsonResponse(['success' => true]);
        break;

    // ─── RESET PASSWORD ──────────────────────────────────────────────────────
    case 'reset_password':
        if ($method !== 'PUT') jsonError('Method not allowed', 405);
        verifyCsrf();
        $data = getJsonInput();
        $id       = (int)($data['id'] ?? 0);
        $password = $data['password'] ?? '';

        if (!$id) jsonError('User ID is required');
        if (strlen($password) < 6) jsonError('Password must be at least 6 characters');

        $db = getDb();
        $stmt = $db->prepare('SELECT id, name FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $target = $stmt->fetch();
        if (!$target) jsonError('User not found', 404);

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $passwordColumn = tableColumnExists('users', 'password') ? 'password' : 'password_hash';
        $db->prepare("UPDATE users SET $passwordColumn = ? WHERE id = ?")->execute([$hash, $id]);

        auditLog((int)$user['id'], 'reset_password', 'user', $id);
        createNotification($id, 'info', 'Password Reset',
            'An administrator has reset your password. Please log in with the new password.');
        jsonResponse(['success' => true]);
        break;

    // ─── DELETE ──────────────────────────────────────────────────────────────
    case 'delete':
        if ($method !== 'DELETE') jsonError('Method not allowed', 405);
        verifyCsrf();
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) jsonError('User ID is required');

        // Prevent self-deletion
        if ($id === (int)$user['id']) {
            jsonError('You cannot delete your own account.');
        }

        $db = getDb();
        $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $target = $stmt->fetch();
        if (!$target) jsonError('User not found', 404);

        $db->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);

        auditLog((int)$user['id'], 'delete_user', 'user', $id,
            "Deleted user: {$target['name']} ({$target['email']})");
        jsonResponse(['success' => true]);
        break;

    default:
        jsonError('Unknown action', 404);
}
