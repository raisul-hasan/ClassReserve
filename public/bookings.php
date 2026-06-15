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
  <title>Bookings | ClassReserve</title>
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
      <h1>Bookings</h1>
      <p class="lead">Review all booking requests and approved room reservations.</p>
    </section>
    <section class="card">
      <div id="bookings-list">Loading bookings...</div>
    </section>
  </main>
  <script src="assets/js/bookings.js"></script>
</body>
</html>
