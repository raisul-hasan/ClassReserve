<?php

require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'csrf':
        jsonResponse(['csrf_token' => getCsrfToken()]);
        break;

    case 'login':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $data = getJsonInput();
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? '';

        if (!$email || !$password) {
            jsonError('Email and password are required');
        }

<<<<<<< HEAD
        $stmt = getDb()->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
=======
        $activeSql = tableColumnExists('users', 'is_active') ? ' AND is_active = 1' : '';
        $stmt = getDb()->prepare('SELECT * FROM users WHERE email = ?' . $activeSql);
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        $passwordHash = $user['password'] ?? $user['password_hash'] ?? '';
        if (!$user || !password_verify($password, $passwordHash)) {
>>>>>>> origin/Riche01
            jsonError('Invalid credentials', 401);
        }

        if ($role && $user['role'] !== $role) {
            jsonError('Role mismatch for this account', 403);
        }

<<<<<<< HEAD
        unset($user['password_hash']);
=======
        unset($user['password'], $user['password_hash']);
>>>>>>> origin/Riche01
        $_SESSION['user'] = $user;
        auditLog((int)$user['id'], 'login', 'user', (int)$user['id']);

        jsonResponse([
            'success' => true,
            'user' => $user,
            'csrf_token' => getCsrfToken(),
        ]);
        break;

    case 'register':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        verifyCsrf();
        $data = getJsonInput();
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'student';

        if (!$name || !$email || !$password) {
            jsonError('Name, email, and password are required');
        }

        if (!in_array($role, ['student', 'club', 'faculty'], true)) {
            jsonError('Invalid role for registration');
        }

        if (strlen($password) < 6) {
            jsonError('Password must be at least 6 characters');
        }

        $stmt = getDb()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            jsonError('Email already registered');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
<<<<<<< HEAD
        $stmt = getDb()->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $email, $hash, $role]);
=======
        $passwordColumn = tableColumnExists('users', 'password') ? 'password' : 'password_hash';
        $columns = ['name', 'email', $passwordColumn, 'role'];
        $values = [$name, $email, $hash, $role];

        if ($role === 'club' && tableColumnExists('users', 'club_name')) {
            $columns[] = 'club_name';
            $values[] = $name;
        }

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $stmt = getDb()->prepare(
            'INSERT INTO users (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')'
        );
        $stmt->execute($values);
>>>>>>> origin/Riche01
        $userId = (int)getDb()->lastInsertId();

        auditLog($userId, 'register', 'user', $userId);
        createNotification($userId, 'success', 'Welcome to ClassReserve', 'Your account has been created successfully.');

        jsonResponse(['success' => true, 'user_id' => $userId], 201);
        break;

    case 'logout':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $userId = $_SESSION['user']['id'] ?? null;
        if ($userId) {
            auditLog((int)$userId, 'logout', 'user', (int)$userId);
        }
        session_destroy();
        jsonResponse(['success' => true]);
        break;

    case 'me':
        if (empty($_SESSION['user'])) {
            jsonResponse(['authenticated' => false]);
        }
        jsonResponse([
            'authenticated' => true,
            'user' => $_SESSION['user'],
            'csrf_token' => getCsrfToken(),
        ]);
        break;

    default:
        jsonError('Unknown action', 404);
}
