<?php
<<<<<<< HEAD

declare(strict_types=1);

require_once __DIR__ . '/includes/faculty_helpers.php';

requireFacultyUser();

require __DIR__ . '/../public/new-booking.php';
=======
require_once __DIR__ . '/../includes/auth.php';
requireRole(['faculty']);

$user = currentUser();
$db = getDb();
$error = '';

$stmt = $db->query('SELECT * FROM rooms WHERE status = "available" ORDER BY building, name');
$allRooms = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomId = (int) ($_POST['room_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $attendees = max(1, (int) ($_POST['attendees'] ?? 1));

    if (!$roomId || !$title || !$startTime || !$endTime) {
        $error = 'All required fields must be filled.';
    } elseif ($startTime >= $endTime) {
        $error = 'End time must be after start time.';
    } else {
        $priority = PRIORITY_FACULTY;
        $check = checkRoomAvailability($roomId, $startTime, $endTime, $priority);

        if (!$check['available']) {
            $error = 'Conflict detected: ' . ($check['conflicts'][0]['title'] ?? $check['conflicts'][0]['reason'] ?? 'room unavailable');
        } else {
            $stmt = $db->prepare(
                'INSERT INTO bookings (room_id, user_id, title, description, start_time, end_time, attendees, status, priority, reviewed_by, reviewed_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, "approved", ?, ?, NOW())'
            );
            $stmt->execute([
                $roomId, $user['id'], $title, $description,
                $startTime, $endTime, $attendees, $priority, $user['id']
            ]);
            flash('success', 'Room reserved successfully (auto-approved).');
            header('Location: /faculty/dashboard.php');
            exit;
        }
    }
}

$pageTitle = 'Reserve Room';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Reserve Room for Extra Class</h1>
</div>

<div class="card">
    <?php if ($error): ?>
        <div class="alert alert-error"><?= sanitize($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="room_id">Room</label>
            <select id="room_id" name="room_id" required>
                <option value="">Select a room</option>
                <?php foreach ($allRooms as $r): ?>
                    <option value="<?= (int) $r['id'] ?>" <?= (int)($_POST['room_id'] ?? 0) === (int)$r['id'] ? 'selected' : '' ?>>
                        <?= sanitize($r['name']) ?> — <?= sanitize($r['building']) ?> (Cap: <?= (int) $r['capacity'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="title">Class / Session Title</label>
            <input type="text" id="title" name="title" required value="<?= sanitize($_POST['title'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="description">Description (optional)</label>
            <textarea id="description" name="description" rows="3"><?= sanitize($_POST['description'] ?? '') ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="start_time">Start</label>
                <input type="datetime-local" id="start_time" name="start_time" required value="<?= sanitize($_POST['start_time'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="end_time">End</label>
                <input type="datetime-local" id="end_time" name="end_time" required value="<?= sanitize($_POST['end_time'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group" style="max-width:200px">
            <label for="attendees">Expected Attendees</label>
            <input type="number" id="attendees" name="attendees" min="1" value="<?= (int) ($_POST['attendees'] ?? 1) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Reserve (Auto-Approve)</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
>>>>>>> origin/Riche01
