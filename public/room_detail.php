<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$roomId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Room Detail | ClassReserve</title>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
  <?php include __DIR__ . '/_header.php'; ?>
  <main class="page-content">
    <section class="page-heading">
      <h1 id="room-name">Room</h1>
    </section>

    <section class="card">
      <div id="room-info">Loading room...</div>
    </section>

    <section class="card">
      <h2>Existing bookings</h2>
      <div id="room-bookings">Loading bookings...</div>
    </section>

    <a class="button button-primary" id="book-now" href="new_booking.php">Book this room</a>
  </main>
  <script>
    const ROOM_ID = <?php echo json_encode($roomId); ?>;
  </script>
  <script src="assets/js/room_detail.js"></script>
</body>
</html>
