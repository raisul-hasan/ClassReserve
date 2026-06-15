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
  <title>Register | ClassReserve</title>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="page-auth">
  <main class="card auth-card">
    <h1>Create account</h1>
    <p class="lead">Register as a student, club, or faculty member.</p>
    <?php if ($flash): ?>
      <div class="alert"><?php echo htmlspecialchars($flash); ?></div>
    <?php endif; ?>
    <form action="#" method="post" class="form" id="register-form">
      <input type="hidden" name="action" value="register">
      <label>Name
        <input type="text" name="name" required>
      </label>
      <label>Email
        <input type="email" name="email" required>
      </label>
      <label>Password
        <input type="password" name="password" required>
      </label>
      <label>Role
        <select name="role" required>
          <option value="student">Student</option>
          <option value="club">Club</option>
          <option value="faculty">Faculty</option>
        </select>
      </label>
      <button type="submit" class="button button-primary">Register</button>
    </form>
    <p class="small">Already have an account? <a href="login.php">Login</a>.</p>
  </main>
  <script src="assets/js/auth.js"></script>
</body>
</html>
