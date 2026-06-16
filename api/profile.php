<?php

require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'me';
$user = requireAuth();

function profileColumns(): array
{
    $columns = ['id', 'name', 'email', 'role', 'is_active', 'created_at'];
    if (tableColumnExists('users', 'phone')) {
        $columns[] = 'phone';
    }
    if (tableColumnExists('users', 'department')) {
        $columns[] = 'department';
    }
    return $columns;
}

function loadCurrentProfile(int $userId): array
{
    $columns = implode(', ', profileColumns());
    $stmt = getDb()->prepare("SELECT $columns FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch();
    if (!$profile) {
        jsonError('Profile not found', 404);
    }
    return $profile;
}

switch ($action) {
    case 'me':
        if ($method !== 'GET') jsonError('Method not allowed', 405);
        jsonResponse(['profile' => loadCurrentProfile((int)$user['id'])]);
        break;

    case 'update':
        if ($method !== 'PUT') jsonError('Method not allowed', 405);
        verifyCsrf();
        $data = getJsonInput();

        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $department = trim($data['department'] ?? '');
        $currentPassword = $data['current_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        if ($name === '') {
            jsonError('Full name is required');
        }

        if ($phone !== '' && !preg_match('/^[0-9+().\-\s]{7,30}$/', $phone)) {
            jsonError('Phone number format is invalid');
        }

        $db = getDb();
        $fields = ['name = ?'];
        $params = [$name];

        if (tableColumnExists('users', 'phone')) {
            $fields[] = 'phone = ?';
            $params[] = $phone !== '' ? $phone : null;
        } else {
            jsonError('Missing users.phone column. Run db/migrations/001_add_user_profile_fields.sql first.', 500);
        }

        if (tableColumnExists('users', 'department')) {
            $fields[] = 'department = ?';
            $params[] = $department !== '' ? $department : null;
        } else {
            jsonError('Missing users.department column. Run db/migrations/001_add_user_profile_fields.sql first.', 500);
        }

        if ($newPassword !== '' || $confirmPassword !== '' || $currentPassword !== '') {
            if ($newPassword === '' || $confirmPassword === '' || $currentPassword === '') {
                jsonError('Current password, new password, and confirmation are required to change password');
            }
            if (strlen($newPassword) < 6) {
                jsonError('New password must be at least 6 characters');
            }
            if ($newPassword !== $confirmPassword) {
                jsonError('Password confirmation does not match');
            }

            $passwordColumn = tableColumnExists('users', 'password') ? 'password' : 'password_hash';
            $stmt = $db->prepare("SELECT $passwordColumn AS password_hash FROM users WHERE id = ?");
            $stmt->execute([(int)$user['id']]);
            $row = $stmt->fetch();
            if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
                jsonError('Current password is incorrect', 403);
            }

            $fields[] = "$passwordColumn = ?";
            $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $params[] = (int)$user['id'];
        $stmt = $db->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?');
        $stmt->execute($params);

        $profile = loadCurrentProfile((int)$user['id']);
        $_SESSION['user'] = $profile;
        auditLog((int)$user['id'], 'update_profile', 'user', (int)$user['id']);

        jsonResponse(['success' => true, 'profile' => $profile]);
        break;

    default:
        jsonError('Unknown action', 404);
}
