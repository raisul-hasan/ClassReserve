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
  <title>Issues | ClassReserve</title>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
  <?php include __DIR__ . '/_header.php'; ?>
  <main class="page-content">
    <section class="page-heading">
      <h1>Issue Reports</h1>
      <p class="lead">Report classroom issues and view existing reports.</p>
    </section>
    <section class="card">
      <form id="issue-form">
        <label>Title
          <input type="text" name="title" required>
        </label>
        <label>Room name
          <input type="text" name="room_name">
        </label>
        <label>Category
          <input type="text" name="category">
        </label>
        <label>Description
          <textarea name="description"></textarea>
        </label>
        <label>Attachment
          <input type="file" name="attachment">
        </label>
        <button class="button button-primary" type="submit">Submit issue</button>
      </form>
    </section>
    <section class="card">
      <h2>Recent issues</h2>
      <div id="issues-list">Loading issues...</div>
    </section>
  </main>
  <script src="assets/js/issues.js"></script>
</body>
</html>
