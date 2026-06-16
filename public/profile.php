<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$pageTitle = 'Profile';
$user = currentUser();
ob_start();
?>
<div class="page-header profile-page-header">
    <div>
        <h1>Profile</h1>
        <p>Your account details and reservation summary</p>
    </div>
    <button class="btn btn-primary" type="button" data-modal="profile-edit-modal">
        <i data-lucide="edit-3"></i> Edit Profile
    </button>
</div>

<div id="profile-alert" class="hidden"></div>

<div class="split-grid">
    <div class="card profile-card">
        <div class="profile-avatar" id="profile-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
        <h3 id="profile-name-card"><?= sanitize($user['name']) ?></h3>
        <p><?= sanitize(rolePortal($user['role'])) ?></p>
        <span class="role-badge role-<?= sanitize($user['role']) ?>"><?= sanitize(roleLabel($user['role'])) ?></span>
    </div>

    <div class="card">
        <h3 class="card-title">Account Information</h3>
        <dl class="detail-list">
            <div><dt>Name</dt><dd id="profile-name-info"><?= sanitize($user['name']) ?></dd></div>
            <div><dt>Email</dt><dd id="profile-email-info"><?= sanitize($user['email']) ?></dd></div>
            <div><dt>Phone</dt><dd id="profile-phone-info">Not set</dd></div>
            <div><dt>Department</dt><dd id="profile-department-info">Not set</dd></div>
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

<div class="modal-overlay" id="profile-edit-modal">
    <div class="modal">
        <h2>Edit Profile</h2>
        <form id="profile-edit-form">
            <div id="profile-modal-alert" class="hidden"></div>
            <div class="form-group">
                <label for="profile-name">Full Name</label>
                <input type="text" id="profile-name" class="form-input" required>
            </div>
            <div class="form-group">
                <label for="profile-phone">Phone</label>
                <input type="tel" id="profile-phone" class="form-input" placeholder="+880 1XXXXXXXXX">
            </div>
            <div class="form-group">
                <label for="profile-department">Department</label>
                <input type="text" id="profile-department" class="form-input" placeholder="Department or program">
            </div>

            <div class="profile-password-section">
                <h3>Password</h3>
                <p class="muted-text">Leave these fields blank to keep your current password.</p>
                <div class="form-group">
                    <label for="profile-current-password">Current Password</label>
                    <input type="password" id="profile-current-password" class="form-input" autocomplete="current-password">
                </div>
                <div class="form-group">
                    <label for="profile-new-password">New Password</label>
                    <input type="password" id="profile-new-password" class="form-input" minlength="6" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="profile-confirm-password">Confirm New Password</label>
                    <input type="password" id="profile-confirm-password" class="form-input" minlength="6" autocomplete="new-password">
                </div>
            </div>

            <div style="display:flex;gap:12px;justify-content:flex-end">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
const ProfilePage = {
    profile: null,

    async init() {
        await Promise.all([this.loadProfile(), this.loadStats()]);
        document.getElementById('profile-edit-form').addEventListener('submit', (e) => this.saveProfile(e));
        document.querySelector('[data-modal="profile-edit-modal"]').addEventListener('click', () => this.fillForm());
    },

    showAlert(targetId, message, type = 'success') {
        const box = document.getElementById(targetId);
        box.className = `alert alert-${type}`;
        box.textContent = message;
        box.classList.remove('hidden');
    },

    clearAlert(targetId) {
        const box = document.getElementById(targetId);
        box.className = 'hidden';
        box.textContent = '';
    },

    async loadProfile() {
        const data = await ClassReserve.api('/api/profile.php?action=me');
        this.profile = data.profile;
        this.renderProfile();
    },

    async loadStats() {
        try {
            const data = await ClassReserve.api('/api/dashboard.php');
            document.getElementById('profile-bookings').textContent = data.stats.bookings ?? 0;
            document.getElementById('profile-pending').textContent = data.stats.pending ?? 0;
            document.getElementById('profile-notices').textContent = data.stats.notices ?? 0;
        } catch (err) {
            console.error(err);
        }
    },

    renderProfile() {
        const p = this.profile || {};
        const name = p.name || '';
        document.getElementById('profile-avatar').textContent = name.substring(0, 1).toUpperCase() || '?';
        document.getElementById('profile-name-card').textContent = name;
        document.getElementById('profile-name-info').textContent = name;
        document.getElementById('profile-email-info').textContent = p.email || '';
        document.getElementById('profile-phone-info').textContent = p.phone || 'Not set';
        document.getElementById('profile-department-info').textContent = p.department || 'Not set';
    },

    fillForm() {
        this.clearAlert('profile-modal-alert');
        const p = this.profile || {};
        document.getElementById('profile-name').value = p.name || '';
        document.getElementById('profile-phone').value = p.phone || '';
        document.getElementById('profile-department').value = p.department || '';
        document.getElementById('profile-current-password').value = '';
        document.getElementById('profile-new-password').value = '';
        document.getElementById('profile-confirm-password').value = '';
    },

    async saveProfile(e) {
        e.preventDefault();
        this.clearAlert('profile-modal-alert');
        this.clearAlert('profile-alert');

        const name = document.getElementById('profile-name').value.trim();
        const phone = document.getElementById('profile-phone').value.trim();
        const department = document.getElementById('profile-department').value.trim();
        const currentPassword = document.getElementById('profile-current-password').value;
        const newPassword = document.getElementById('profile-new-password').value;
        const confirmPassword = document.getElementById('profile-confirm-password').value;

        if (!name) {
            this.showAlert('profile-modal-alert', 'Full name is required', 'error');
            return;
        }
        if (phone && !/^[0-9+().\-\s]{7,30}$/.test(phone)) {
            this.showAlert('profile-modal-alert', 'Phone number format is invalid', 'error');
            return;
        }
        if ((currentPassword || newPassword || confirmPassword) && newPassword !== confirmPassword) {
            this.showAlert('profile-modal-alert', 'Password confirmation does not match', 'error');
            return;
        }

        try {
            const data = await ClassReserve.api('/api/profile.php?action=update', {
                method: 'PUT',
                body: JSON.stringify({
                    name,
                    phone,
                    department,
                    current_password: currentPassword,
                    new_password: newPassword,
                    confirm_password: confirmPassword
                })
            });
            this.profile = data.profile;
            this.renderProfile();
            document.getElementById('profile-edit-modal').classList.remove('open');
            this.showAlert('profile-alert', 'Profile updated successfully');
        } catch (err) {
            this.showAlert('profile-modal-alert', err.message, 'error');
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    ProfilePage.init().catch(err => {
        console.error(err);
        ProfilePage.showAlert('profile-alert', 'Could not load profile information', 'error');
    });
});
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/includes/layout.php';
