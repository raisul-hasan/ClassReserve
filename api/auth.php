<?php
// Basic auth endpoints: register / login (POST)
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['action'])) {
    echo json_encode(['error' => 'Missing action']);
    exit;
}

if ($input['action'] === 'register') {
    $name = $input['name'] ?? '';
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    if (!$email || !$password) {
        echo json_encode(['error' => 'Email and password required']);
        exit;
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
    try {
        $stmt->execute([$name, $email, $hash, $input['role'] ?? 'student']);
        echo json_encode(['ok' => true]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => 'Registration failed']);
    }
    exit;
}

if ($input['action'] === 'login') {
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    $stmt = $pdo->prepare('SELECT id, password_hash, role, name FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        echo json_encode(['ok' => true, 'user' => ['id' => $user['id'], 'name' => $user['name'], 'role' => $user['role']]]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
    }
    exit;
}

echo json_encode(['error' => 'Unknown action']);
