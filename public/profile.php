<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$name = htmlspecialchars($_SESSION['name'] ?? '');
$email = htmlspecialchars($_SESSION['email'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Profile | ClassReserve</title>
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
      <h1>Profile</h1>
      <p class="lead">Update your name or change your password.</p>
    </section>

    <section class="card grid two-column-form">
      <form action="#" method="post" class="form profile-form">
        <h2>Account information</h2>
        <input type="hidden" name="action" value="update_profile">
        <label>Name
          <input type="text" name="name" value="<?php echo $name; ?>" required>
        </label>
        <label>Email
          <input type="email" value="<?php echo $email; ?>" disabled>
        </label>
        <button type="submit" class="button button-primary">Save profile</button>
      </form>

      <form action="#" method="post" class="form profile-form">
        <h2>Change password</h2>
        <input type="hidden" name="action" value="change_password">
        <label>Current password
          <input type="password" name="current_password" required>
        </label>
        <label>New password
          <input type="password" name="new_password" required>
        </label>
        <button type="submit" class="button button-secondary">Update password</button>
      </form>
    </section>
  </main>
  <script src="assets/js/profile.js"></script>
</body>
</html>
