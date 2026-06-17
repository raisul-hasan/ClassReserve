<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$pageTitle = 'Settings';
ob_start();
?>
<div class="page-header">
    <h1>Settings</h1>
    <p>Adjust student portal preferences for this browser</p>
</div>

<div class="card">
    <h3 class="card-title">Portal Preferences</h3>
    <div class="settings-list">
        <label class="settings-row">
            <span>
                <strong>Keep sidebar minimized</strong>
                <small>Remember the compact navigation rail across pages.</small>
            </span>
            <input type="checkbox" id="setting-sidebar">
        </label>
        <label class="settings-row">
            <span>
                <strong>Refresh notifications automatically</strong>
                <small>Check for new notices while you move through the portal.</small>
            </span>
            <input type="checkbox" id="setting-notifications">
        </label>
        <label class="settings-row">
            <span>
                <strong>Show booking check-in codes</strong>
                <small>Display approved booking codes in the My Bookings table.</small>
            </span>
            <input type="checkbox" id="setting-codes">
        </label>
        <?php if (currentUser()['role'] === 'admin'): ?>
        <label class="settings-row">
            <span>
                <strong>Auto-approve Faculty Requests</strong>
                <small>Automatically approve booking requests from faculty members.</small>
            </span>
            <input type="checkbox" id="setting-auto-approve">
        </label>
        <label class="settings-row">
            <span>
                <strong>Conflict Detection</strong>
                <small>Automatically detect and flag scheduling conflicts in lists.</small>
            </span>
            <input type="checkbox" id="setting-conflict-detection">
        </label>
        <?php endif; ?>
    </div>
    <div class="settings-actions">
        <button class="btn btn-primary" id="save-settings"><i data-lucide="save"></i> Save Settings</button>
        <span id="settings-status" class="muted-text"></span>
    </div>
</div>

<script>
const settings = {
    sidebar: document.getElementById('setting-sidebar'),
    notifications: document.getElementById('setting-notifications'),
    codes: document.getElementById('setting-codes'),
    autoApprove: document.getElementById('setting-auto-approve'),
    conflictDetection: document.getElementById('setting-conflict-detection')
};

function loadSettings() {
    settings.sidebar.checked = localStorage.getItem('classreserve.sidebarCollapsed') === '1';
    settings.notifications.checked = localStorage.getItem('classreserve.autoRefreshNotifications') !== '0';
    settings.codes.checked = localStorage.getItem('classreserve.showCheckinCodes') !== '0';
    if (settings.autoApprove) {
        settings.autoApprove.checked = localStorage.getItem('classreserve.autoApproveFaculty') === '1';
    }
    if (settings.conflictDetection) {
        settings.conflictDetection.checked = localStorage.getItem('classreserve.conflictDetection') !== '0';
    }
}

function saveSettings() {
    localStorage.setItem('classreserve.sidebarCollapsed', settings.sidebar.checked ? '1' : '0');
    localStorage.setItem('classreserve.autoRefreshNotifications', settings.notifications.checked ? '1' : '0');
    localStorage.setItem('classreserve.showCheckinCodes', settings.codes.checked ? '1' : '0');
    if (settings.autoApprove) {
        localStorage.setItem('classreserve.autoApproveFaculty', settings.autoApprove.checked ? '1' : '0');
    }
    if (settings.conflictDetection) {
        localStorage.setItem('classreserve.conflictDetection', settings.conflictDetection.checked ? '1' : '0');
    }
    document.getElementById('settings-status').textContent = 'Saved';
    setTimeout(() => document.getElementById('settings-status').textContent = '', 1800);
}

document.addEventListener('DOMContentLoaded', () => {
    loadSettings();
    document.getElementById('save-settings').addEventListener('click', saveSettings);
});
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/includes/layout.php';
