<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirectByRole($_SESSION['user_role']);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = getDb()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            loginUser($user);
            redirectByRole($user['role']);
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Please enter email and password.';
    }
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?></title>
    <script>
        (() => {
            try {
                const storedTheme = localStorage.getItem('classreserve.theme');
                const theme = storedTheme === 'light' || storedTheme === 'dark' ? storedTheme : 'dark';
                document.documentElement.dataset.theme = theme;
                document.documentElement.style.colorScheme = theme;
            } catch {
                document.documentElement.dataset.theme = 'dark';
            }
        })();
    </script>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand"><a href="/index.php">Classroom Availability</a></div>
        <button class="theme-switch" type="button" data-theme-toggle aria-label="Switch theme" aria-live="polite">
            <span class="theme-switch-track" aria-hidden="true"><span class="theme-switch-thumb"></span></span>
            <span class="theme-switch-label">Light</span>
        </button>
    </nav>
    <div class="auth-wrapper">
        <div class="auth-card">
            <h1>Sign In</h1>
            <p class="subtitle">Classroom booking &amp; availability</p>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= sanitize($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required value="<?= sanitize($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%">Login</button>
            </form>
            <p style="text-align:center;margin-top:1rem;font-size:.9rem">
                No account? <a href="/register.php">Register</a>
            </p>
            <div class="alert alert-info" style="margin-top:1.25rem;font-size:.8rem">
                <strong>Demo accounts:</strong><br>
                Admin: admin@classroom.local / admin123<br>
                Faculty: faculty@classroom.local / faculty123<br>
                Student: alice@student.local / student123<br>
                Club: club@student.local / club123
            </div>
        </div>
    </div>
    <script src="/assets/js/app.js"></script>
</body>
</html>
