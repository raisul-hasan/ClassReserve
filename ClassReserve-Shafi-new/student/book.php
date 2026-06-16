<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['student', 'club']);

$user = currentUser();
$roomId = (int) ($_GET['room_id'] ?? 0);
$startTime = $_GET['start'] ?? '';
$endTime = $_GET['end'] ?? '';
$attendees = max(1, (int) ($_GET['attendees'] ?? 1));
$error = '';

$db = getDb();
$stmt = $db->prepare('SELECT * FROM rooms WHERE id = ? AND status = "available"');
$stmt->execute([$roomId]);
$room = $stmt->fetch();

if (!$room) {
    flash('error', 'Room not found or unavailable.');
    header('Location: /student/search.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $attendees = max(1, (int) ($_POST['attendees'] ?? 1));

    if (!$title || !$startTime || !$endTime) {
        $error = 'Title and time are required.';
    } elseif ($startTime >= $endTime) {
        $error = 'End time must be after start time.';
    } else {
        $priority = priorityForRole($user['role']);
        $check = checkRoomAvailability($roomId, $startTime, $endTime, $priority);

        if (!$check['available']) {
            $error = 'This room has a scheduling conflict. Please search again.';
        } else {
            try {
                $eventFile = handleFileUpload($_FILES['event_file'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }

            if (!$error) {
                $stmt = $db->prepare(
                    'INSERT INTO bookings (room_id, user_id, title, description, start_time, end_time, attendees, status, priority, event_file)
                     VALUES (?, ?, ?, ?, ?, ?, ?, "pending", ?, ?)'
                );
                $stmt->execute([
                    $roomId, $user['id'], $title, $description,
                    $startTime, $endTime, $attendees, $priority, $eventFile
                ]);
                flash('success', 'Booking request submitted. Awaiting faculty approval.');
                header('Location: /student/bookings.php');
                exit;
            }
        }
    }
}

$pageTitle = 'Request Booking';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Request Booking</h1>
    <a href="/student/search.php" class="btn btn-outline">Back to Search</a>
</div>

<div class="card">
    <h3><?= sanitize($room['name']) ?> &mdash; <?= sanitize($room['building']) ?></h3>
    <p class="room-meta">Capacity: <?= (int) $room['capacity'] ?> &middot; <?= sanitize($room['equipment'] ?? '') ?></p>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= sanitize($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="title">Event Title</label>
            <input type="text" id="title" name="title" required value="<?= sanitize($_POST['title'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="description">Event Details</label>
            <textarea id="description" name="description" rows="4"><?= sanitize($_POST['description'] ?? '') ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="start_time">Start</label>
                <input type="datetime-local" id="start_time" name="start_time" required value="<?= sanitize($_POST['start_time'] ?? $startTime) ?>">
            </div>
            <div class="form-group">
                <label for="end_time">End</label>
                <input type="datetime-local" id="end_time" name="end_time" required value="<?= sanitize($_POST['end_time'] ?? $endTime) ?>">
            </div>
        </div>
        <div class="form-group" style="max-width:200px">
            <label for="attendees">Attendees</label>
            <input type="number" id="attendees" name="attendees" min="1" max="<?= (int) $room['capacity'] ?>" value="<?= $attendees ?>" required>
        </div>
        <div class="form-group">
            <label for="event_file">Upload Event Details (optional)</label>
            <input type="file" id="event_file" name="event_file" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
            <p class="form-hint">PDF, DOC, or image up to 5MB</p>
        </div>
        <button type="submit" class="btn btn-primary">Submit Request</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
