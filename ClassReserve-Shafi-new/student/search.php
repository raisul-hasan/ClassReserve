<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['student', 'club']);

$user = currentUser();
$rooms = [];
$searched = false;
$startTime = $_GET['start_time'] ?? $_POST['start_time'] ?? '';
$endTime = $_GET['end_time'] ?? $_POST['end_time'] ?? '';
$attendees = (int) ($_GET['attendees'] ?? $_POST['attendees'] ?? 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' || ($startTime && $endTime)) {
    $searched = true;
    if ($startTime && $endTime && $startTime < $endTime) {
        $priority = priorityForRole($user['role']);
        $rooms = searchAvailableRooms($startTime, $endTime, max(1, $attendees), $priority);
    }
}

$pageTitle = 'Search Rooms';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Search Available Rooms</h1>
</div>

<div class="card">
    <form method="POST" id="room-search-form" data-ajax="true">
        <div class="form-row">
            <div class="form-group">
                <label for="start_time">Start Date &amp; Time</label>
                <input type="datetime-local" id="start_time" name="start_time" required value="<?= sanitize($startTime) ?>">
            </div>
            <div class="form-group">
                <label for="end_time">End Date &amp; Time</label>
                <input type="datetime-local" id="end_time" name="end_time" required value="<?= sanitize($endTime) ?>">
            </div>
        </div>
        <div class="form-group" style="max-width:200px">
            <label for="attendees">Minimum Capacity</label>
            <input type="number" id="attendees" name="attendees" min="1" value="<?= $attendees ?>" required>
            <p class="form-hint">Number of attendees / required seats</p>
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<div id="search-results">
<?php if ($searched): ?>
    <?php if (empty($rooms)): ?>
        <div class="empty-state"><p>No rooms available for the selected time and capacity.</p></div>
    <?php else: ?>
        <div class="grid-2">
            <?php foreach ($rooms as $room): ?>
            <div class="room-card">
                <h3><?= sanitize($room['name']) ?></h3>
                <div class="room-meta">
                    <?= sanitize($room['building']) ?> &middot; Floor <?= (int) $room['floor'] ?>
                    &middot; Capacity: <?= (int) $room['capacity'] ?>
                </div>
                <p style="font-size:.85rem;margin-bottom:.75rem"><?= sanitize($room['equipment'] ?? '') ?></p>
                <a href="/student/book.php?room_id=<?= (int) $room['id'] ?>&start=<?= urlencode($startTime) ?>&end=<?= urlencode($endTime) ?>&attendees=<?= $attendees ?>"
                   class="btn btn-primary btn-sm">Request Booking</a>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
