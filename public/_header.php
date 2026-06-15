<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$name = htmlspecialchars($_SESSION['name'] ?? '');
$role = $_SESSION['role'] ?? 'guest';
?>
<header class="site-header">
  <div class="brand">ClassReserve</div>
  <nav>
    <a href="dashboard.php">Dashboard</a>
    <a href="bookings.php">Bookings</a>
    <a href="rooms.php">Rooms</a>
    <a href="maintenance.php">Maintenance</a>
    <?php if ($role === 'admin' || $role === 'faculty'): ?>
      <a href="approvals.php">Approvals</a>
    <?php endif; ?>
    <?php if ($role === 'admin'): ?>
      <a href="admin_users.php">Admin</a>
    <?php endif; ?>
    <a href="notifications.php">Notifications</a>
    <a href="profile.php">Profile</a>
    <?php if (!empty($name)): ?>
      <span style="margin-left:8px;color:#374151">Signed in as <?php echo htmlspecialchars($name); ?></span>
      <a href="logout.php" class="button button-secondary">Logout</a>
    <?php else: ?>
      <a href="login.php" class="button button-primary">Login</a>
    <?php endif; ?>
  </nav>
</header>
