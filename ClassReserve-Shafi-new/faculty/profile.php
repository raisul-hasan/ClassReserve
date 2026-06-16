<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/faculty_helpers.php';

$user = requireFacultyUser();
$db = getDb();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));

    if (!hash_equals(getCsrfToken(), $token)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } elseif ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid name and email address.';
    } else {
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
        $stmt->execute([$email, (int) $user['id']]);
        if ($stmt->fetch()) {
            $error = 'That email address is already used by another account.';
        } else {
            $stmt = $db->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
            $stmt->execute([$name, $email, (int) $user['id']]);
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
            $user = $_SESSION['user'];
            $message = 'Profile settings updated.';
        }
    }
}

$stmt = $db->prepare('
    SELECT
        COUNT(*) AS total_bookings,
        SUM(CASE WHEN status = \'approved\' THEN 1 ELSE 0 END) AS approved_bookings,
        SUM(CASE WHEN status = \'pending\' THEN 1 ELSE 0 END) AS pending_bookings,
        SUM(CASE WHEN status = \'rejected\' THEN 1 ELSE 0 END) AS rejected_bookings
    FROM bookings
    WHERE user_id = ?
');
$stmt->execute([(int) $user['id']]);
$summary = $stmt->fetch() ?: [];

$pageTitle = 'Profile';
ob_start();
?>
<div class="page-header">
    <div>
        <h1>Profile</h1>
        <p>Your faculty account details, booking summary, and basic preferences.</p>
    </div>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= sanitize($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= sanitize($error) ?></div><?php endif; ?>

<div class="split-grid">
    <div class="card">
        <h3 class="card-title">Faculty Details</h3>
        <div class="receipt-card">
            <div class="receipt-row"><span class="receipt-label">Name</span><span class="receipt-value"><?= sanitize((string) $user['name']) ?></span></div>
            <div class="receipt-row"><span class="receipt-label">Email</span><span class="receipt-value"><?= sanitize((string) $user['email']) ?></span></div>
            <div class="receipt-row"><span class="receipt-label">Role</span><span class="receipt-value"><?= sanitize(roleLabel((string) $user['role'])) ?></span></div>
            <?php if (!empty($user['created_at'])): ?>
                <div class="receipt-row"><span class="receipt-label">Joined</span><span class="receipt-value"><?= sanitize(facultyDateOnly((string) $user['created_at'])) ?></span></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <h3 class="card-title">Booking Summary</h3>
        <div class="stat-grid" style="margin-bottom:0">
            <div class="stat-card stat-bookings"><div class="stat-label">Total</div><div class="stat-value"><?= (int) ($summary['total_bookings'] ?? 0) ?></div></div>
            <div class="stat-card stat-rooms"><div class="stat-label">Approved</div><div class="stat-value"><?= (int) ($summary['approved_bookings'] ?? 0) ?></div></div>
            <div class="stat-card stat-pending"><div class="stat-label">Pending</div><div class="stat-value"><?= (int) ($summary['pending_bookings'] ?? 0) ?></div></div>
            <div class="stat-card stat-notices"><div class="stat-label">Rejected</div><div class="stat-value"><?= (int) ($summary['rejected_bookings'] ?? 0) ?></div></div>
        </div>
    </div>
</div>

<div class="split-grid" style="margin-top:20px">
    <div class="card">
        <h3 class="card-title">Basic Profile Settings</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= sanitize(getCsrfToken()) ?>">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-input" value="<?= sanitize((string) $user['name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-input" value="<?= sanitize((string) $user['email']) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Save Changes</button>
        </form>
    </div>

    <div class="card">
        <h3 class="card-title">Faculty Preferences</h3>
        <div class="empty-state" style="padding:28px 20px">
            <i data-lucide="sliders-horizontal"></i>
            <p>Preference controls need database fields for faculty notification and scheduling preferences.</p>
        </div>
    </div>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../public/includes/layout.php';
