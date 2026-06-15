<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Maintenance | ClassReserve</title>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
  <header class="site-header">
    <div class="brand">ClassReserve</div>
    <nav>
      <a href="dashboard.php">Dashboard</a>
      <a href="bookings.php">Bookings</a>
      <a href="rooms.php">Rooms</a>
      <a href="maintenance.php">Maintenance</a>
      <a href="profile.php">Profile</a>
      <a href="logout.php" class="button button-secondary">Logout</a>
    </nav>
  </header>
  <main class="page-content">
    <section class="page-heading">
      <h1>Maintenance</h1>
      <p class="lead">View scheduled maintenance blocks for rooms.</p>
    </section>
    <section class="card">
      <div id="maintenance-list">Loading maintenance blocks...</div>
    </section>
  </main>
  <script src="assets/js/maintenance.js"></script>
</body>
</html>
