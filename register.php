<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirectByRole($_SESSION['user_role']);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'student';
    $clubName = trim($_POST['club_name'] ?? '');

    if (!in_array($role, ['student', 'club'], true)) {
        $role = 'student';
    }

    if (!$name || !$email || !$password) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif ($role === 'club' && !$clubName) {
        $error = 'Club name is required for club accounts.';
    } else {
        $stmt = getDb()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = getDb()->prepare(
                'INSERT INTO users (name, email, password_hash, role, club_name) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$name, $email, $hash, $role, $role === 'club' ? $clubName : null]);
            flash('success', 'Account created. Please sign in.');
            header('Location: /login.php');
            exit;
        }
    }
}

$pageTitle = 'Register';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand"><a href="/index.php">Classroom Availability</a></div>
    </nav>
    <div class="auth-wrapper">
        <div class="auth-card">
            <h1>Register</h1>
            <p class="subtitle">Create a student or club account</p>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= sanitize($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required value="<?= sanitize($_POST['name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required value="<?= sanitize($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="role">Account Type</label>
                    <select id="role" name="role" onchange="document.getElementById('club-field').style.display=this.value==='club'?'block':'none'">
                        <option value="student" <?= ($_POST['role'] ?? '') === 'student' ? 'selected' : '' ?>>Student</option>
                        <option value="club" <?= ($_POST['role'] ?? '') === 'club' ? 'selected' : '' ?>>Club</option>
                    </select>
                </div>
                <div class="form-group" id="club-field" style="display:<?= ($_POST['role'] ?? '') === 'club' ? 'block' : 'none' ?>">
                    <label for="club_name">Club Name</label>
                    <input type="text" id="club_name" name="club_name" value="<?= sanitize($_POST['club_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%">Create Account</button>
            </form>
            <p style="text-align:center;margin-top:1rem;font-size:.9rem">
                Already have an account? <a href="/login.php">Sign in</a>
            </p>
        </div>
    </div>
</body>
</html>
