<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/faculty_helpers.php';

$user = requireFacultyUser();
$db = getDb();

$tabs = [
    'search' => 'Search Available Rooms',
    'suggestions' => 'Smart Room Suggestion',
    'favorites' => 'Favorite Rooms',
    'usage' => 'Room Usage',
];
$tab = (string) ($_GET['tab'] ?? 'search');
if (!isset($tabs[$tab])) {
    $tab = 'search';
}

$buildings = facultyDistinctRoomValues($db, 'building');
$types = facultyDistinctRoomValues($db, 'type');

$date = (string) ($_GET['date'] ?? date('Y-m-d'));
$startTime = (string) ($_GET['start_time'] ?? '09:00');
$endTime = (string) ($_GET['end_time'] ?? '10:00');
$minCapacity = max(0, (int) ($_GET['min_capacity'] ?? 1));
$maxCapacity = max(0, (int) ($_GET['max_capacity'] ?? 0));
$building = (string) ($_GET['building'] ?? '');
$type = (string) ($_GET['type'] ?? '');
$participants = max(1, (int) ($_GET['participants'] ?? 30));
$error = '';
$rooms = null;

if ($tab === 'search' && isset($_GET['date'], $_GET['start_time'], $_GET['end_time'])) {
    $window = facultyNormalizeWindow($date, $startTime, $endTime);
    if ($window === null) {
        $error = 'Please choose a valid date and time range.';
        $rooms = [];
    } else {
        [$start, $end] = $window;
        $rooms = facultyAvailableRooms($db, $start, $end, [
            'min_capacity' => $minCapacity,
            'max_capacity' => $maxCapacity,
            'building' => $building,
            'type' => $type,
        ]);
    }
}

if ($tab === 'suggestions' && isset($_GET['date'], $_GET['start_time'], $_GET['end_time'], $_GET['participants'])) {
    $window = facultyNormalizeWindow($date, $startTime, $endTime);
    if ($window === null) {
        $error = 'Please choose a valid date and time range.';
        $rooms = [];
    } else {
        [$start, $end] = $window;
        $rooms = facultyAvailableRooms($db, $start, $end, ['min_capacity' => $participants]);
        usort($rooms, static function (array $a, array $b) use ($participants): int {
            $aWaste = max(0, (int) ($a['capacity'] ?? 0) - $participants);
            $bWaste = max(0, (int) ($b['capacity'] ?? 0) - $participants);
            return $aWaste <=> $bWaste ?: ((int) ($a['capacity'] ?? 0) <=> (int) ($b['capacity'] ?? 0));
        });
    }
}

$hasFavoriteSupport = facultyTableExists($db, 'favorite_rooms')
    && facultyHasColumn($db, 'favorite_rooms', 'user_id')
    && facultyHasColumn($db, 'favorite_rooms', 'room_id');
$favorites = [];
if ($tab === 'favorites' && $hasFavoriteSupport) {
    $favoriteOrder = facultyHasColumn($db, 'rooms', 'building') ? 'r.building, r.name' : 'r.name';
    $stmt = $db->prepare('
        SELECT r.id, ' . facultyRoomSelectFields($db) . '
        FROM favorite_rooms fr
        JOIN rooms r ON r.id = fr.room_id
        WHERE fr.user_id = ?
        ORDER BY ' . $favoriteOrder . '
    ');
    $stmt->execute([(int) $user['id']]);
    $favorites = $stmt->fetchAll();
}

$frequentRooms = [];
$usageRooms = [];
if (in_array($tab, ['favorites', 'usage'], true)) {
    [$startColumn, $endColumn] = facultyBookingTimeColumns($db);
    $buildingField = facultyHasColumn($db, 'rooms', 'building') ? 'MAX(r.building) AS building' : "'' AS building";
    $typeField = facultyHasColumn($db, 'rooms', 'type') ? 'MAX(r.type) AS room_type' : "'' AS room_type";
    $capacityField = facultyHasColumn($db, 'rooms', 'capacity') ? 'MAX(r.capacity) AS capacity' : '0 AS capacity';
    $stmt = $db->prepare('
        SELECT
            r.id,
            r.name AS room_name,
            ' . $buildingField . ',
            ' . $typeField . ',
            ' . $capacityField . ',
            COUNT(*) AS booking_count,
            SUM(CASE WHEN b.status = \'approved\' THEN 1 ELSE 0 END) AS approved_count,
            SUM(CASE WHEN b.status = \'pending\' THEN 1 ELSE 0 END) AS pending_count,
            ROUND(COALESCE(SUM(TIMESTAMPDIFF(MINUTE, b.' . facultyIdentifier($startColumn) . ', b.' . facultyIdentifier($endColumn) . ')), 0) / 60, 2) AS total_hours,
            MAX(b.' . facultyIdentifier($startColumn) . ') AS last_used
        FROM bookings b
        JOIN rooms r ON r.id = b.room_id
        WHERE b.user_id = ?
        GROUP BY r.id, r.name
        ORDER BY booking_count DESC, total_hours DESC, r.name ASC
    ');
    $stmt->execute([(int) $user['id']]);
    $usageRooms = $stmt->fetchAll();
    $frequentRooms = array_slice($usageRooms, 0, 5);
}

$pageTitle = 'Rooms';
ob_start();
?>
<div class="page-header">
    <div>
        <h1>Rooms</h1>
        <p>Search rooms, get smart suggestions, and review your room preferences and usage.</p>
    </div>
</div>

<div class="filter-pills" style="margin-bottom:20px">
    <?php foreach ($tabs as $key => $label): ?>
        <a class="filter-pill <?= $tab === $key ? 'active' : '' ?>" style="text-decoration:none" href="/faculty/rooms.php?tab=<?= sanitize($key) ?>"><?= sanitize($label) ?></a>
    <?php endforeach; ?>
</div>

<?php if ($tab === 'search'): ?>
    <div class="card" style="margin-bottom:24px">
        <h3 class="card-title">Search Available Rooms</h3>
        <form method="GET">
            <input type="hidden" name="tab" value="search">
            <div class="grid gap-4" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px">
                <div class="form-group"><label for="date">Date</label><input type="date" id="date" name="date" class="form-input" value="<?= sanitize($date) ?>" required></div>
                <div class="form-group"><label for="start_time">Start Time</label><input type="time" id="start_time" name="start_time" class="form-input" value="<?= sanitize($startTime) ?>" required></div>
                <div class="form-group"><label for="end_time">End Time</label><input type="time" id="end_time" name="end_time" class="form-input" value="<?= sanitize($endTime) ?>" required></div>
                <div class="form-group"><label for="min_capacity">Minimum Capacity</label><input type="number" id="min_capacity" name="min_capacity" class="form-input" min="1" value="<?= (int) $minCapacity ?>"></div>
                <div class="form-group"><label for="max_capacity">Maximum Capacity</label><input type="number" id="max_capacity" name="max_capacity" class="form-input" min="0" value="<?= (int) $maxCapacity ?>" placeholder="No limit"></div>
                <?php if ($buildings): ?>
                    <div class="form-group">
                        <label for="building">Building</label>
                        <select id="building" name="building" class="form-select">
                            <option value="">All Buildings</option>
                            <?php foreach ($buildings as $option): ?>
                                <option value="<?= sanitize((string) $option) ?>" <?= $building === (string) $option ? 'selected' : '' ?>><?= sanitize((string) $option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <?php if ($types): ?>
                    <div class="form-group">
                        <label for="type">Room Type</label>
                        <select id="type" name="type" class="form-select">
                            <option value="">All Types</option>
                            <?php foreach ($types as $option): ?>
                                <option value="<?= sanitize((string) $option) ?>" <?= $type === (string) $option ? 'selected' : '' ?>><?= sanitize((string) $option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
            <div style="margin-top:16px;display:flex;gap:12px;flex-wrap:wrap">
                <button type="submit" class="btn btn-primary"><i data-lucide="search"></i> Search Rooms</button>
                <a href="/faculty/reserve.php" class="btn btn-secondary"><i data-lucide="calendar-plus"></i> Reserve Room</a>
            </div>
        </form>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= sanitize($error) ?></div>
    <?php elseif ($rooms !== null): ?>
        <div class="card">
            <h3 class="card-title">Available Rooms</h3>
            <?php if (!$rooms): ?>
                <div class="empty-state"><i data-lucide="door-closed"></i><p>No available rooms match these filters.</p></div>
            <?php else: ?>
                <div class="room-grid">
                    <?php foreach ($rooms as $room): ?>
                        <div class="room-card">
                            <span class="room-type-badge"><?= sanitize((string) ($room['type'] ?? 'Room')) ?></span>
                            <div class="room-name"><?= sanitize((string) $room['name']) ?></div>
                            <div class="room-meta"><?= sanitize((string) ($room['building'] ?? '')) ?><?php if (isset($room['floor'])): ?> - Floor <?= sanitize((string) $room['floor']) ?><?php endif; ?></div>
                            <div class="room-capacity"><i data-lucide="users"></i> <?= (int) ($room['capacity'] ?? 0) ?> seats</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card"><div class="empty-state"><i data-lucide="search"></i><p>Choose search filters to see real-time room availability.</p></div></div>
    <?php endif; ?>
<?php elseif ($tab === 'suggestions'): ?>
    <div class="card" style="margin-bottom:24px">
        <h3 class="card-title">Smart Room Suggestion</h3>
        <form method="GET">
            <input type="hidden" name="tab" value="suggestions">
            <div class="grid gap-4" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px">
                <div class="form-group"><label for="suggest-date">Date</label><input type="date" id="suggest-date" name="date" class="form-input" value="<?= sanitize($date) ?>" required></div>
                <div class="form-group"><label for="suggest-start">Start Time</label><input type="time" id="suggest-start" name="start_time" class="form-input" value="<?= sanitize($startTime) ?>" required></div>
                <div class="form-group"><label for="suggest-end">End Time</label><input type="time" id="suggest-end" name="end_time" class="form-input" value="<?= sanitize($endTime) ?>" required></div>
                <div class="form-group"><label for="participants">Expected Participants</label><input type="number" id="participants" name="participants" class="form-input" min="1" value="<?= (int) $participants ?>" required></div>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top:16px"><i data-lucide="sparkles"></i> Suggest Rooms</button>
        </form>
    </div>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= sanitize($error) ?></div>
    <?php elseif ($rooms !== null): ?>
        <div class="card">
            <h3 class="card-title">Recommended Rooms</h3>
            <?php if (!$rooms): ?>
                <div class="empty-state"><i data-lucide="door-closed"></i><p>No rooms are available with enough capacity for this time.</p></div>
            <?php else: ?>
                <div class="room-grid">
                    <?php foreach ($rooms as $index => $room): ?>
                        <?php $wasted = max(0, (int) ($room['capacity'] ?? 0) - $participants); ?>
                        <div class="room-card">
                            <span class="room-type-badge"><?= $index === 0 ? 'Best Fit' : sanitize((string) ($room['type'] ?? 'Room')) ?></span>
                            <div class="room-name"><?= sanitize((string) $room['name']) ?></div>
                            <div class="room-meta"><?= sanitize((string) ($room['building'] ?? '')) ?><?php if (isset($room['floor'])): ?> - Floor <?= sanitize((string) $room['floor']) ?><?php endif; ?></div>
                            <div class="room-capacity"><i data-lucide="users"></i> <?= (int) ($room['capacity'] ?? 0) ?> seats</div>
                            <div style="margin-top:12px;color:var(--cr-slate);font-size:.85rem"><?= $wasted ?> extra <?= $wasted === 1 ? 'seat' : 'seats' ?> beyond expected participants</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card"><div class="empty-state"><i data-lucide="sparkles"></i><p>Enter your schedule and participant count to get room suggestions.</p></div></div>
    <?php endif; ?>
<?php elseif ($tab === 'favorites'): ?>
    <?php if (!$hasFavoriteSupport): ?>
        <div class="card" style="margin-bottom:24px">
            <h3 class="card-title">Favorite Rooms</h3>
            <div class="empty-state" style="padding:28px 20px">
                <i data-lucide="star"></i>
                <p>Favorite room management requires a database table such as <strong>favorite_rooms</strong> with faculty user and room references.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="card" style="margin-bottom:24px">
            <h3 class="card-title">Saved Favorite Rooms</h3>
            <?php if (!$favorites): ?>
                <div class="empty-state"><i data-lucide="star"></i><p>No favorite rooms saved yet.</p></div>
            <?php else: ?>
                <div class="room-grid">
                    <?php foreach ($favorites as $room): ?>
                        <div class="room-card">
                            <span class="room-type-badge"><?= sanitize((string) ($room['room_type'] ?? 'Room')) ?></span>
                            <div class="room-name"><?= sanitize((string) $room['room_name']) ?></div>
                            <div class="room-meta"><?= sanitize((string) ($room['building'] ?? '')) ?></div>
                            <div class="room-capacity"><i data-lucide="users"></i> <?= (int) ($room['room_capacity'] ?? 0) ?> seats</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3 class="card-title">Frequently Used Rooms</h3>
        <?php if (!$frequentRooms): ?>
            <div class="empty-state"><i data-lucide="clock"></i><p>No frequent room usage yet.</p></div>
        <?php else: ?>
            <div class="room-grid">
                <?php foreach ($frequentRooms as $room): ?>
                    <div class="room-card">
                        <span class="room-type-badge"><?= sanitize((string) ($room['room_type'] ?? 'Room')) ?></span>
                        <div class="room-name"><?= sanitize((string) $room['room_name']) ?></div>
                        <div class="room-meta"><?= sanitize((string) ($room['building'] ?? '')) ?></div>
                        <div class="room-capacity"><i data-lucide="calendar-check"></i> <?= (int) $room['booking_count'] ?> bookings</div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="card">
        <h3 class="card-title">Room Usage</h3>
        <div style="overflow-x:auto">
            <table class="data-table">
                <thead><tr><th>Room</th><th>Type</th><th>Capacity</th><th>Total Bookings</th><th>Approved</th><th>Pending</th><th>Total Hours</th><th>Last Used</th></tr></thead>
                <tbody>
                    <?php if (!$usageRooms): ?>
                        <tr><td colspan="8" class="empty-state">No room usage found yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($usageRooms as $room): ?>
                            <tr>
                                <td><strong><?= sanitize((string) $room['room_name']) ?></strong><br><span style="font-size:.75rem;color:var(--cr-slate)"><?= sanitize((string) $room['building']) ?></span></td>
                                <td><?= sanitize((string) $room['room_type']) ?></td>
                                <td><?= (int) $room['capacity'] ?></td>
                                <td><?= (int) $room['booking_count'] ?></td>
                                <td><?= (int) $room['approved_count'] ?></td>
                                <td><?= (int) $room['pending_count'] ?></td>
                                <td><?= sanitize((string) $room['total_hours']) ?></td>
                                <td><?= sanitize(facultyDateOnly((string) $room['last_used'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../public/includes/layout.php';
