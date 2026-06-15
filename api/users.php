<?php
// Admin user management.
require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$admin = require_role('admin');
$allowedRoles = ['student', 'club', 'faculty', 'admin'];
$parsedInput = null;

function user_response($row)
{
    return [
        'id' => (int) $row['id'],
        'name' => $row['name'],
        'email' => $row['email'],
        'role' => $row['role'],
        'is_active' => (bool) $row['is_active'],
        'created_at' => $row['created_at'] ?? null,
    ];
}

function find_user_by_id($pdo, $id)
{
    $stmt = $pdo->prepare('SELECT id, name, email, role, is_active, created_at FROM users WHERE id = ?');
    $stmt->execute([(int) $id]);
    return $stmt->fetch();
}

function validate_user_fields($name, $email, $role, $allowedRoles)
{
    if ($name === '' || $email === '') {
        json_response(['error' => 'Name and email are required.'], 422);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['error' => 'Please enter a valid email address.'], 422);
    }
    if (!in_array($role, $allowedRoles, true)) {
        json_response(['error' => 'Invalid user role.'], 422);
    }
}

function request_bool($value, $default)
{
    if ($value === null) {
        return (int) $default;
    }

    $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    return $parsed === null ? (int) $default : (int) $parsed;
}

if ($method === 'GET') {
    $sql = 'SELECT id, name, email, role, is_active, created_at FROM users';
    $where = [];
    $params = [];

    if (!empty($_GET['id'])) {
        $where[] = 'id = ?';
        $params[] = (int) $_GET['id'];
    }
    if (!empty($_GET['role'])) {
        $where[] = 'role = ?';
        $params[] = $_GET['role'];
    }
    if (isset($_GET['is_active'])) {
        $where[] = 'is_active = ?';
        $params[] = request_bool($_GET['is_active'], 1);
    }
    if (!empty($_GET['q'])) {
        $where[] = '(name LIKE ? OR email LIKE ?)';
        $params[] = '%' . $_GET['q'] . '%';
        $params[] = '%' . $_GET['q'] . '%';
    }

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY created_at DESC, id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = array_map('user_response', $stmt->fetchAll());

    if (!empty($_GET['id'])) {
        json_response($users[0] ?? null);
    }
    json_response($users);
}

if ($method === 'POST') {
    $input = get_json_input();
    $action = $input['action'] ?? 'create';

    if ($action === 'update') {
        $parsedInput = $input;
        $method = 'PUT';
    } elseif ($action === 'deactivate' || $action === 'delete') {
        $_GET['id'] = $input['id'] ?? null;
        $method = 'DELETE';
    } elseif ($action === 'activate') {
        $id = (int) ($input['id'] ?? 0);
        $existing = $id > 0 ? find_user_by_id($pdo, $id) : null;
        if (!$existing) {
            json_response(['error' => 'User not found.'], 404);
        }

        $stmt = $pdo->prepare('UPDATE users SET is_active = 1 WHERE id = ?');
        $stmt->execute([$id]);
        json_response(['ok' => true, 'id' => $id, 'is_active' => true]);
    } elseif ($action !== 'create') {
        json_response(['error' => 'Unknown action.'], 400);
    } else {
        $name = clean_string($input['name'] ?? '');
        $email = strtolower(clean_string($input['email'] ?? ''));
        $role = clean_string($input['role'] ?? 'faculty');
        $password = (string) ($input['password'] ?? '');
        $isActive = array_key_exists('is_active', $input) ? request_bool($input['is_active'], 1) : 1;

        validate_user_fields($name, $email, $role, $allowedRoles);
        if (strlen($password) < 6) {
            json_response(['error' => 'Password must be at least 6 characters.'], 422);
        }

        $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?)');
        try {
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $isActive]);
            json_response(['ok' => true, 'id' => (int) $pdo->lastInsertId()], 201);
        } catch (Exception $e) {
            json_response(['error' => 'A user with this email already exists.'], 409);
        }
    }
}

if ($method === 'PUT') {
    $input = $parsedInput ?? get_json_input();
    $id = (int) ($input['id'] ?? $_GET['id'] ?? 0);
    $existing = $id > 0 ? find_user_by_id($pdo, $id) : null;
    if (!$existing) {
        json_response(['error' => 'User not found.'], 404);
    }

    $name = clean_string($input['name'] ?? $existing['name']);
    $email = strtolower(clean_string($input['email'] ?? $existing['email']));
    $role = clean_string($input['role'] ?? $existing['role']);
    $isActive = array_key_exists('is_active', $input) ? request_bool($input['is_active'], $existing['is_active']) : (int) $existing['is_active'];
    $password = (string) ($input['password'] ?? '');

    validate_user_fields($name, $email, $role, $allowedRoles);
    if ((int) $admin['id'] === $id && ($role !== 'admin' || !$isActive)) {
        json_response(['error' => 'You cannot remove your own admin access.'], 409);
    }

    if ($password !== '') {
        if (strlen($password) < 6) {
            json_response(['error' => 'Password must be at least 6 characters.'], 422);
        }
        $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, role = ?, is_active = ?, password_hash = ? WHERE id = ?');
        $params = [$name, $email, $role, $isActive, password_hash($password, PASSWORD_DEFAULT), $id];
    } else {
        $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, role = ?, is_active = ? WHERE id = ?');
        $params = [$name, $email, $role, $isActive, $id];
    }

    try {
        $stmt->execute($params);
    } catch (Exception $e) {
        json_response(['error' => 'A user with this email already exists.'], 409);
    }

    json_response(['ok' => true, 'id' => $id]);
}

if ($method === 'DELETE') {
    $id = (int) ($_GET['id'] ?? 0);
    $existing = $id > 0 ? find_user_by_id($pdo, $id) : null;
    if (!$existing) {
        json_response(['error' => 'User not found.'], 404);
    }
    if ((int) $admin['id'] === $id) {
        json_response(['error' => 'You cannot deactivate your own account.'], 409);
    }

    $stmt = $pdo->prepare('UPDATE users SET is_active = 0 WHERE id = ?');
    $stmt->execute([$id]);
    json_response(['ok' => true, 'id' => $id, 'is_active' => false]);
}

json_response(['error' => 'Method not allowed'], 405);
