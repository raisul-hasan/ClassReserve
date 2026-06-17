<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

if (currentUser()['role'] !== 'admin') {
    header('Location: ' . getBaseUrl() . '/public/dashboard.php');
    exit;
}

$pageTitle = 'Manage Users';
ob_start();
?>
<div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1>Users</h1>
        <p>Review, create, and manage user accounts and permissions</p>
    </div>
    <button class="btn btn-primary" onclick="openCreateModal()">
        <i data-lucide="user-plus"></i> Add User
    </button>
</div>

<!-- Search and Filters Card -->
<div class="card" style="margin-bottom: 24px; padding: 18px;">
    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 16px; align-items: end;">
        <div class="form-group" style="margin: 0;">
            <label for="user-search">Search Users</label>
            <div style="position: relative;">
                <input type="text" id="user-search" class="form-input" placeholder="Search by name or email..." style="padding-left: 38px;">
                <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--cr-slate);"></i>
            </div>
        </div>
        <div class="form-group" style="margin: 0;">
            <label for="filter-role">Role</label>
            <select id="filter-role" class="form-select">
                <option value="all">All Roles</option>
                <option value="admin">Administrator</option>
                <option value="faculty">Faculty</option>
                <option value="club">Club</option>
                <option value="student">Student</option>
            </select>
        </div>
        <div class="form-group" style="margin: 0;">
            <label for="filter-status">Status</label>
            <select id="filter-status" class="form-select">
                <option value="all">All Statuses</option>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
            </select>
        </div>
        <div style="padding-bottom: 1px;">
            <button class="btn btn-secondary" onclick="loadUsers()">
                <i data-lucide="refresh-cw" style="width: 15px; height: 15px;"></i>
            </button>
        </div>
    </div>
</div>

<!-- Users Table Card -->
<div class="card" style="padding: 0; overflow-x: auto;">
    <table class="data-table" id="users-table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 1px solid rgba(241, 230, 210, 0.06);">
                <th style="padding: 14px 18px; text-align: left;">User</th>
                <th style="padding: 14px 18px; text-align: left;">Email</th>
                <th style="padding: 14px 18px; text-align: left;">Role</th>
                <th style="padding: 14px 18px; text-align: left;">Status</th>
                <th style="padding: 14px 18px; text-align: left;">Bookings</th>
                <th style="padding: 14px 18px; text-align: left;">Joined</th>
                <th style="padding: 14px 18px; text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody id="users-list-body">
            <tr>
                <td colspan="7" style="text-align: center; padding: 24px;">
                    <div class="spinner"></div>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Create User Modal -->
<div class="modal-overlay" id="create-user-modal">
    <div class="modal" style="max-width: 480px;">
        <h2 style="margin-top: 0;">Add New User</h2>
        <form id="create-user-form">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" id="new-user-name" class="form-input" required placeholder="e.g. Jane Smith">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" id="new-user-email" class="form-input" required placeholder="e.g. jane@example.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" id="new-user-password" class="form-input" required placeholder="Min. 6 characters">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Role</label>
                    <select id="new-user-role" class="form-select">
                        <option value="student">Student</option>
                        <option value="club">Club</option>
                        <option value="faculty">Faculty</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="new-user-status" class="form-select">
                        <option value="1">Active</option>
                        <option value="0">Suspended</option>
                    </select>
                </div>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal-overlay" id="edit-user-modal">
    <div class="modal" style="max-width: 480px;">
        <h2 style="margin-top: 0;">Edit User</h2>
        <form id="edit-user-form">
            <input type="hidden" id="edit-user-id">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" id="edit-user-name" class="form-input" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" id="edit-user-email" class="form-input" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Role</label>
                    <select id="edit-user-role" class="form-select">
                        <option value="student">Student</option>
                        <option value="club">Club</option>
                        <option value="faculty">Faculty</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="edit-user-status" class="form-select">
                        <option value="1">Active</option>
                        <option value="0">Suspended</option>
                    </select>
                </div>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal-overlay" id="reset-pw-modal">
    <div class="modal" style="max-width: 400px;">
        <h2 style="margin-top: 0;">Reset Password</h2>
        <p style="color: var(--cr-slate); font-size: 0.9rem; margin-bottom: 20px;" id="reset-pw-info"></p>
        <form id="reset-pw-form">
            <input type="hidden" id="reset-pw-user-id">
            <div class="form-group">
                <label>New Password</label>
                <input type="password" id="reset-pw-value" class="form-input" required placeholder="Min. 6 characters">
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Reset Password</button>
            </div>
        </form>
    </div>
</div>

<script>
let allUsers = [];
const currentUserId = <?= (int)currentUser()['id'] ?>;

async function loadUsers() {
    const tbody = document.getElementById('users-list-body');
    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 24px;"><div class="spinner"></div></td></tr>';
    try {
        const role   = document.getElementById('filter-role').value;
        const status = document.getElementById('filter-status').value;
        const search = document.getElementById('user-search').value.trim();

        const params = new URLSearchParams({ action: 'list' });
        if (role   !== 'all') params.set('role', role);
        if (status !== 'all') params.set('status', status);
        if (search)           params.set('search', search);

        const res = await ClassReserve.api('/api/users.php?' + params);
        allUsers = res.users || [];
        renderUsers();
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 24px; color: var(--cr-slate);">Failed to load users: ${ClassReserve.escapeHtml(err.message)}</td></tr>`;
    }
}

function roleLabel(role) {
    return { admin: 'Administrator', faculty: 'Faculty', club: 'Club', student: 'Student' }[role] || role;
}

function renderUsers() {
    const listBody = document.getElementById('users-list-body');

    if (allUsers.length === 0) {
        listBody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 32px; color: var(--cr-slate);">No users match the criteria.</td></tr>`;
        return;
    }

    listBody.innerHTML = allUsers.map(u => {
        const isSelf = (u.id === currentUserId);
        const statusText      = Number(u.is_active) ? 'Active' : 'Suspended';
        const statusClass     = Number(u.is_active) ? 'status-approved' : 'status-rejected';
        const roleBadgeClass  = `role-${u.role}`;
        const joinedDate      = ClassReserve.formatDate(u.created_at);

        const avatarInitial = ClassReserve.escapeHtml(u.name.substring(0, 1).toUpperCase());

        let actionButtons;
        if (isSelf) {
            actionButtons = `<span style="font-size: 0.8rem; color: var(--cr-slate); font-style: italic;">Your account</span>`;
        } else {
            const suspendIcon  = Number(u.is_active) ? 'user-x'     : 'user-check';
            const suspendLabel = Number(u.is_active) ? 'Suspend'     : 'Activate';
            const suspendColor = Number(u.is_active) ? '#c07070'     : '#70c080';

            actionButtons = `
                <div style="display: inline-flex; gap: 5px; flex-wrap: wrap; justify-content: flex-end;">
                    <button class="btn btn-secondary btn-sm" onclick="openEditModal(${u.id})" title="Edit user"
                        style="display: flex; align-items: center; gap: 4px;">
                        <i data-lucide="edit" style="width: 12px; height: 12px;"></i> Edit
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="openResetPwModal(${u.id}, '${ClassReserve.escapeHtml(u.name)}')" title="Reset password"
                        style="display: flex; align-items: center; gap: 4px; color: #8fb8ff;">
                        <i data-lucide="key" style="width: 12px; height: 12px;"></i> PW
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="toggleStatus(${u.id}, ${u.is_active})"
                        style="display: flex; align-items: center; gap: 4px; color: ${suspendColor};">
                        <i data-lucide="${suspendIcon}" style="width: 12px; height: 12px;"></i> ${suspendLabel}
                    </button>
                    <button class="btn btn-ghost btn-sm" onclick="deleteUser(${u.id})"
                        style="display: flex; align-items: center; gap: 4px; color: #ff5555;">
                        <i data-lucide="trash-2" style="width: 12px; height: 12px;"></i>
                    </button>
                </div>
            `;
        }

        return `
            <tr style="border-bottom: 1px solid rgba(241, 230, 210, 0.06);">
                <td style="padding: 14px 18px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 34px; height: 34px; border-radius: 50%; background: var(--cr-maroon); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; color: #fff; flex-shrink: 0;">
                            ${avatarInitial}
                        </div>
                        <strong style="color: var(--cr-beige); font-size: 0.95rem;">${ClassReserve.escapeHtml(u.name)}</strong>
                    </div>
                </td>
                <td style="padding: 14px 18px; color: var(--cr-slate); font-size: 0.9rem;">${ClassReserve.escapeHtml(u.email)}</td>
                <td style="padding: 14px 18px;">
                    <span class="role-badge ${roleBadgeClass}" style="margin: 0;">${roleLabel(u.role)}</span>
                </td>
                <td style="padding: 14px 18px;">
                    <span class="status-pill ${statusClass}">${statusText}</span>
                </td>
                <td style="padding: 14px 18px; font-size: 0.9rem; color: var(--cr-beige);">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="calendar" style="width: 13px; height: 13px; color: var(--cr-slate);"></i>
                        ${u.booking_count}
                    </div>
                </td>
                <td style="padding: 14px 18px; color: var(--cr-slate); font-size: 0.88rem;">${joinedDate}</td>
                <td style="padding: 14px 18px; text-align: right;">${actionButtons}</td>
            </tr>
        `;
    }).join('');

    lucide.createIcons();
}

// ─── EDIT ─────────────────────────────────────────────────────────────────────
function openEditModal(userId) {
    const u = allUsers.find(x => x.id === userId);
    if (!u) return;
    document.getElementById('edit-user-id').value    = u.id;
    document.getElementById('edit-user-name').value  = u.name;
    document.getElementById('edit-user-email').value = u.email;
    document.getElementById('edit-user-role').value  = u.role;
    document.getElementById('edit-user-status').value = String(u.is_active);
    document.getElementById('edit-user-modal').classList.add('open');
}

document.getElementById('edit-user-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = parseInt(document.getElementById('edit-user-id').value);
    try {
        await ClassReserve.api('/api/users.php?action=update', {
            method: 'PUT',
            body: JSON.stringify({
                id,
                name:      document.getElementById('edit-user-name').value,
                email:     document.getElementById('edit-user-email').value,
                role:      document.getElementById('edit-user-role').value,
                is_active: parseInt(document.getElementById('edit-user-status').value),
            })
        });
        document.getElementById('edit-user-modal').classList.remove('open');
        await loadUsers();
    } catch (err) { alert(err.message); }
});

// ─── CREATE ───────────────────────────────────────────────────────────────────
function openCreateModal() {
    document.getElementById('create-user-form').reset();
    document.getElementById('create-user-modal').classList.add('open');
}

document.getElementById('create-user-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    try {
        await ClassReserve.api('/api/users.php?action=create', {
            method: 'POST',
            body: JSON.stringify({
                name:      document.getElementById('new-user-name').value,
                email:     document.getElementById('new-user-email').value,
                password:  document.getElementById('new-user-password').value,
                role:      document.getElementById('new-user-role').value,
                is_active: parseInt(document.getElementById('new-user-status').value),
            })
        });
        document.getElementById('create-user-modal').classList.remove('open');
        await loadUsers();
    } catch (err) { alert(err.message); }
});

// ─── RESET PASSWORD ───────────────────────────────────────────────────────────
function openResetPwModal(userId, name) {
    document.getElementById('reset-pw-user-id').value = userId;
    document.getElementById('reset-pw-info').textContent = `Reset password for: ${name}`;
    document.getElementById('reset-pw-value').value = '';
    document.getElementById('reset-pw-modal').classList.add('open');
}

document.getElementById('reset-pw-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = parseInt(document.getElementById('reset-pw-user-id').value);
    const pw = document.getElementById('reset-pw-value').value;
    try {
        await ClassReserve.api('/api/users.php?action=reset_password', {
            method: 'PUT',
            body: JSON.stringify({ id, password: pw })
        });
        document.getElementById('reset-pw-modal').classList.remove('open');
        alert('Password has been reset successfully.');
    } catch (err) { alert(err.message); }
});

// ─── TOGGLE STATUS ────────────────────────────────────────────────────────────
async function toggleStatus(userId, currentStatus) {
    const nextStatus = Number(currentStatus) ? 0 : 1;
    const action = nextStatus ? 'activate' : 'suspend';
    if (!confirm(`Are you sure you want to ${action} this user?`)) return;
    try {
        await ClassReserve.api('/api/users.php?action=update', {
            method: 'PUT',
            body: JSON.stringify({ id: userId, is_active: nextStatus })
        });
        await loadUsers();
    } catch (err) { alert(err.message); }
}

// ─── DELETE ───────────────────────────────────────────────────────────────────
async function deleteUser(userId) {
    if (!confirm('Delete this user? All their bookings and issues will be permanently removed.')) return;
    try {
        await ClassReserve.api(`/api/users.php?action=delete&id=${userId}`, { method: 'DELETE' });
        await loadUsers();
    } catch (err) { alert(err.message); }
}

// ─── INIT ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadUsers();
    let searchTimeout;
    document.getElementById('user-search').addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(loadUsers, 350);
    });
    document.getElementById('filter-role').addEventListener('change', loadUsers);
    document.getElementById('filter-status').addEventListener('change', loadUsers);
});
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/includes/layout.php';
