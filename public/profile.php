<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$pageTitle = 'Profile';
$user = currentUser();
ob_start();
?>
<div class="page-header">
    <h1>Profile</h1>
    <p>Your account details and reservation summary</p>
</div>

<div class="split-grid">
    <div class="card profile-card">
        <div class="profile-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
        <h3><?= sanitize($user['name']) ?></h3>
        <p><?= sanitize(rolePortal($user['role'])) ?></p>
        <span class="role-badge role-<?= sanitize($user['role']) ?>"><?= sanitize(roleLabel($user['role'])) ?></span>
    </div>

    <div class="card">
        <h3 class="card-title">Account Information</h3>
        <dl class="detail-list">
            <div><dt>Name</dt><dd><?= sanitize($user['name']) ?></dd></div>
            <div><dt>Email</dt><dd><?= sanitize($user['email']) ?></dd></div>
            <div><dt>Role</dt><dd><?= sanitize(roleLabel($user['role'])) ?></dd></div>
            <div><dt>Status</dt><dd><?= !empty($user['is_active']) ? 'Active' : 'Inactive' ?></dd></div>
        </dl>
    </div>
</div>

<div class="stat-grid" style="margin-top:20px" id="profile-stats">
    <div class="stat-card stat-bookings">
        <div class="stat-label">My Bookings</div>
        <div class="stat-value" id="profile-bookings">-</div>
    </div>
    <div class="stat-card stat-pending">
        <div class="stat-label">Pending</div>
        <div class="stat-value" id="profile-pending">-</div>
    </div>
    <div class="stat-card stat-notices">
        <div class="stat-label">Unread Notices</div>
        <div class="stat-value" id="profile-notices">-</div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const data = await ClassReserve.api('/api/dashboard.php');
        document.getElementById('profile-bookings').textContent = data.stats.bookings ?? 0;
        document.getElementById('profile-pending').textContent = data.stats.pending ?? 0;
        document.getElementById('profile-notices').textContent = data.stats.notices ?? 0;
    } catch (err) {
        console.error(err);
    }
});
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/includes/layout.php';
