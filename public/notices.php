<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$pageTitle = 'Notices';
ob_start();
?>
<div class="page-header">
    <h1>Notices</h1>
    <p>Review booking updates, approvals, rejections, and system messages</p>
</div>

<div class="card">
    <div class="table-toolbar">
        <h3 class="card-title">All Notices</h3>
        <button class="btn btn-secondary btn-sm" id="mark-all-notices"><i data-lucide="check-check"></i> Mark All Read</button>
    </div>
    <div id="notices-list" class="notice-list">
        <div class="spinner"></div>
    </div>
</div>

<script>
async function loadNoticePage() {
    const list = document.getElementById('notices-list');
    try {
        const data = await ClassReserve.api('/api/notifications.php?action=list');
        if (!data.notifications.length) {
            list.innerHTML = '<div class="empty-state"><i data-lucide="bell"></i><p>No notices yet</p></div>';
            lucide.createIcons();
            return;
        }

        list.innerHTML = data.notifications.map(notice => `
            <button class="notice-item ${Number(notice.is_read) ? '' : 'unread'}" type="button" data-id="${notice.id}">
                <span class="notification-dot ${ClassReserve.notificationDotClass(notice.type)}"></span>
                <span class="notice-content">
                    <span class="notice-title">${ClassReserve.escapeHtml(notice.title)}</span>
                    <span class="notice-message">${ClassReserve.escapeHtml(notice.message)}</span>
                    <span class="notice-time">${ClassReserve.formatDate(notice.created_at)} ${ClassReserve.formatTime(notice.created_at)}</span>
                </span>
            </button>
        `).join('');
    } catch (err) {
        list.innerHTML = `<div class="empty-state">${ClassReserve.escapeHtml(err.message)}</div>`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadNoticePage();
    document.getElementById('mark-all-notices').addEventListener('click', async () => {
        await ClassReserve.markNotificationsRead();
        await loadNoticePage();
    });
    document.getElementById('notices-list').addEventListener('click', async (event) => {
        const item = event.target.closest('.notice-item');
        if (!item || !item.classList.contains('unread')) return;
        await ClassReserve.markNotificationsRead(Number(item.dataset.id));
        await loadNoticePage();
    });
});
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/includes/layout.php';
