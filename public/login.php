<?php
session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login | ClassReserve</title>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="page-auth">
  <main class="card auth-card">
    <h1>ClassReserve</h1>
    <p class="lead">Login to manage your classroom reservations.</p>
    <?php if ($flash): ?>
      <div class="alert"><?php echo htmlspecialchars($flash); ?></div>
    <?php endif; ?>
    <form action="#" method="post" class="form" id="login-form">
      <input type="hidden" name="action" value="login">
      <label>Email
        <input type="email" name="email" required>
      </label>
      <label>Password
        <input type="password" name="password" required>
      </label>
      <button type="submit" class="button button-primary">Sign in</button>
    </form>
    <p class="small">New user? <a href="register.php">Register here</a>.</p>
  </main>
  <script src="assets/js/auth.js"></script>
</body>
</html>
