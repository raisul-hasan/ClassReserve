<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin - Users | ClassReserve</title>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
  <?php include __DIR__ . '/_header.php'; ?>
  <main class="page-content">
    <section class="page-heading">
      <h1>Admin: Users</h1>
    </section>
    <section class="card">
      <div id="users-list">Loading users...</div>
    </section>
  </main>
  <script src="assets/js/admin_users.js"></script>
</body>
</html>
