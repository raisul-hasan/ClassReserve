<?php
// Auth endpoints: session check (GET), register/login/logout (POST)
require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $user = current_user();
    json_response(['ok' => true, 'user' => $user ? require_login() : null]);
}

if ($method !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$input = get_json_input();
$action = $input['action'] ?? '';

if ($action === 'register') {
    $name = clean_string($input['name'] ?? '');
    $email = strtolower(clean_string($input['email'] ?? ''));
    $password = (string) ($input['password'] ?? '');
    $role = $input['role'] ?? 'student';

    if (!$name || !$email || !$password) {
        json_response(['error' => 'Name, email, and password are required.'], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['error' => 'Please enter a valid email address.'], 400);
    }

    if (!is_strong_password($password)) {
        json_response(['error' => 'Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.'], 400);
    }

    if ($role === 'admin') {
        json_response(['error' => 'Admin accounts are managed by the system.'], 403);
    }

    if (!in_array($role, ['student', 'club', 'faculty'], true)) {
        json_response(['error' => 'Invalid registration role.'], 400);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');

    try {
        $stmt->execute([$name, $email, $hash, $role]);
        json_response(['ok' => true, 'message' => 'Registration successful. Please log in.']);
    } catch (Exception $e) {
        json_response(['error' => 'Registration failed. This email may already be registered.'], 400);
    }
}

if ($action === 'login') {
    $email = strtolower(clean_string($input['email'] ?? ''));
    $password = (string) ($input['password'] ?? '');

    if (!$email || !$password) {
        json_response(['error' => 'Email and password are required.'], 400);
    }

    // Brute-force rate limiting check (max 5 failures in 15 minutes by IP or email)
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $attemptCheck = $pdo->prepare("SELECT COUNT(*) FROM failed_logins WHERE (ip_address = ? OR email = ?) AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $attemptCheck->execute([$ip, $email]);
    if ($attemptCheck->fetchColumn() >= 5) {
        json_response(['error' => 'Too many failed login attempts. Please try again after 15 minutes.'], 429);
    }

    $stmt = $pdo->prepare('SELECT id, password_hash, role, name, email, is_active FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && !(bool) $user['is_active']) {
        json_response(['error' => 'This account has been deactivated.'], 403);
    }

    if ($user && password_verify($password, $user['password_hash'])) {
        // Clear failed attempts on successful login
        $clearAttempts = $pdo->prepare("DELETE FROM failed_logins WHERE ip_address = ? OR email = ?");
        $clearAttempts->execute([$ip, $email]);

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['is_active'] = (bool) $user['is_active'];
        
        create_audit_log($pdo, $user['id'], 'login', 'user', $user['id'], ['email' => $user['email'], 'role' => $user['role']]);
        
        json_response([
            'ok' => true,
            'message' => 'Login successful.',
            'user' => [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'is_active' => (bool) $user['is_active'],
            ],
        ]);
    }

    // Record failed attempt
    $recordAttempt = $pdo->prepare("INSERT INTO failed_logins (ip_address, email) VALUES (?, ?)");
    $recordAttempt->execute([$ip, $email]);

    json_response(['error' => 'Invalid email or password.'], 401);
}

if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
    json_response(['ok' => true, 'message' => 'Logged out.']);
}

json_response(['error' => 'Unknown action.'], 400);
