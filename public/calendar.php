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
  <title>Calendar | ClassReserve</title>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
  <?php include __DIR__ . '/_header.php'; ?>
  <main class="page-content">
    <section class="page-heading">
      <h1>Calendar</h1>
      <p class="lead">Upcoming bookings and maintenance blocks.</p>
    </section>
    <section class="card">
      <div id="calendar-events">Loading events...</div>
    </section>
  </main>
  <script src="assets/js/calendar.js"></script>
</body>
</html>
