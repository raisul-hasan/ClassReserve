<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$name = htmlspecialchars($_SESSION['name'] ?? '');
$role = htmlspecialchars($_SESSION['role'] ?? 'student');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard | ClassReserve</title>
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
    <section class="hero-card">
      <h1>Welcome back, <?php echo $name; ?>.</h1>
      <p class="lead">You are signed in as <strong><?php echo ucfirst($role); ?></strong>.</p>
    </section>

    <section class="grid cards-grid">
      <article class="card">
        <h2>Bookings</h2>
        <p>View and manage booking requests.</p>
        <a class="button button-primary" href="bookings.php">Open bookings</a>
      </article>
      <article class="card">
        <h2>Rooms</h2>
        <p>Browse available rooms and capacity details.</p>
        <a class="button button-primary" href="rooms.php">View rooms</a>
      </article>
      <article class="card">
        <h2>Maintenance</h2>
        <p>Review room maintenance blocks.</p>
        <a class="button button-primary" href="maintenance.php">Maintenance</a>
      </article>
      <article class="card">
        <h2>Profile</h2>
        <p>Update your account details and password.</p>
        <a class="button button-primary" href="profile.php">My profile</a>
      </article>
    </section>
  </main>
</body>
</html>
