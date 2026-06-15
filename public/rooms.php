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
  <title>Rooms | ClassReserve</title>
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
      <h1>Rooms</h1>
      <p class="lead">Browse all rooms and their capacity, equipment, and status.</p>
    </section>
    <section class="card">
      <div id="room-list">Loading rooms...</div>
    </section>
  </main>
  <script src="assets/js/rooms.js"></script>
</body>
</html>
