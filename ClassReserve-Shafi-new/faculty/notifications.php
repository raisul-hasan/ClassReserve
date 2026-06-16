<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/faculty_helpers.php';

$user = requireFacultyUser();
$db = getDb();
$message = '';
$error = '';
$hasNotifications = facultyTableExists($db, 'notifications');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hasNotifications) {
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals(getCsrfToken(), $token)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
        $stmt->execute([(int) $user['id']]);
        $message = 'Notifications marked as read.';
    }
}

$notifications = [];
if ($hasNotifications) {
    $stmt = $db->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
    $stmt->execute([(int) $user['id']]);
    $notifications = $stmt->fetchAll();
}

$pageTitle = 'Notifications';
ob_start();
?>
<div class="page-header">
    <div>
        <h1>Notifications</h1>
        <p>Booking and account updates for your faculty account.</p>
    </div>
    <?php if ($hasNotifications && $notifications): ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= sanitize(getCsrfToken()) ?>">
            <button type="submit" class="btn btn-secondary"><i data-lucide="check-check"></i> Mark All Read</button>
        </form>
    <?php endif; ?>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= sanitize($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= sanitize($error) ?></div><?php endif; ?>

<?php if (!$hasNotifications): ?>
    <div class="card">
        <div class="empty-state"><i data-lucide="bell"></i><p>Notification storage is not available in the current database.</p></div>
    </div>
<?php else: ?>
    <div class="card">
        <h3 class="card-title">Recent Notifications</h3>
        <?php if (!$notifications): ?>
            <div class="empty-state"><i data-lucide="bell"></i><p>No notifications yet.</p></div>
        <?php else: ?>
            <ul class="activity-list">
                <?php foreach ($notifications as $notification): ?>
                    <li class="activity-item">
                        <span class="activity-dot <?= ((int) ($notification['is_read'] ?? 0)) === 0 ? 'dot-pending' : 'dot-info' ?>"></span>
                        <div style="flex:1">
                            <strong><?= sanitize((string) $notification['title']) ?></strong>
                            <span style="float:right;color:var(--cr-slate);font-size:.75rem"><?= sanitize(facultyDateTime((string) $notification['created_at'])) ?></span><br>
                            <span style="font-size:.85rem;color:var(--cr-slate)"><?= sanitize((string) $notification['message']) ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../public/includes/layout.php';
