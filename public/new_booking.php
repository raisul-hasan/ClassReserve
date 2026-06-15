<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$roomPref = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>New Booking | ClassReserve</title>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
  <?php include __DIR__ . '/_header.php'; ?>
  <main class="page-content">
    <section class="page-heading">
      <h1>Request a booking</h1>
      <p class="lead">Create a new room reservation request.</p>
    </section>

    <section class="card">
      <form id="booking-form" enctype="multipart/form-data">
        <label>Room
          <select name="room_id" id="room-select" required>
            <option value="">Loading rooms...</option>
          </select>
        </label>
        <label>Date
          <input type="date" name="date" required>
        </label>
        <label>Start time
          <input type="time" name="start_time" required>
        </label>
        <label>End time
          <input type="time" name="end_time" required>
        </label>
        <label>Title
          <input type="text" name="title" required>
        </label>
        <label>Description
          <textarea name="description"></textarea>
        </label>
        <label>Attachment (optional)
          <input type="file" name="attachment">
        </label>
        <button class="button button-primary" type="submit">Submit request</button>
      </form>
      <div id="booking-result"></div>
    </section>
  </main>
  <script src="assets/js/new_booking.js"></script>
</body>
</html>
