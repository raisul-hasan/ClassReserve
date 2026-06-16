<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$pageTitle = 'Classroom Forum';
$user = currentUser();
$isAdmin = $user['role'] === 'admin';
ob_start();
?>
<div class="page-header forum-page-header">
    <div>
        <h1>Classroom Forum</h1>
        <p>Report issues, discuss maintenance, and upvote concerns</p>
    </div>
    <button class="btn btn-primary" data-modal="report-modal"><i data-lucide="plus"></i> Report Issue</button>
</div>

<div class="forum-search">
    <i data-lucide="search"></i>
    <input type="text" id="forum-search" class="form-input" placeholder="Search issues...">
</div>

<div class="filter-pills forum-filter-pills">
    <button class="filter-pill active" data-category="">All</button>
    <button class="filter-pill" data-category="Equipment">Equipment</button>
    <button class="filter-pill" data-category="Comfort">Comfort</button>
    <button class="filter-pill" data-category="Safety">Safety</button>
    <button class="filter-pill" data-category="Scheduling">Scheduling</button>
    <button class="filter-pill" data-category="Other">Other</button>
</div>

<div class="forum-layout">
    <div id="issues-feed" class="issues-feed">
        <div class="spinner"></div>
    </div>
    <aside id="issue-detail-panel" class="forum-detail-panel">
        <div class="forum-detail-empty">
            <i data-lucide="message-square"></i>
            <p>Select an issue to view details and comments.</p>
        </div>
    </aside>
</div>

<div class="modal-overlay" id="report-modal">
    <div class="modal">
        <h2>Report an Issue</h2>
        <form id="report-form">
            <div class="form-group">
                <label>Title</label>
                <input type="text" id="issue-title" class="form-input" required placeholder="Brief description">
            </div>
            <div class="form-group">
                <label>Room Name</label>
                <input type="text" id="issue-room" class="form-input" required placeholder="e.g. A101">
            </div>
            <div class="form-group">
                <label>Category</label>
                <select id="issue-category" class="form-select">
                    <option value="Equipment">Equipment</option>
                    <option value="Comfort">Comfort</option>
                    <option value="Safety">Safety</option>
                    <option value="Scheduling">Scheduling</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Priority</label>
                <select id="issue-priority" class="form-select">
                    <option value="Low">Low</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="High">High</option>
                    <option value="Urgent">Urgent</option>
                </select>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="issue-desc" class="form-textarea" required placeholder="Describe the issue in detail..."></textarea>
            </div>
            <div style="display:flex;gap:12px;justify-content:flex-end">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<script>
const Forum = {
    category: '',
    isAdmin: <?= $isAdmin ? 'true' : 'false' ?>,
    selectedIssueId: null,

    init() {
        this.loadIssues();
        document.getElementById('forum-search').addEventListener('input', debounce(() => this.loadIssues(), 300));
        document.querySelectorAll('[data-category]').forEach(pill => {
            pill.addEventListener('click', () => {
                document.querySelectorAll('[data-category]').forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
                this.category = pill.dataset.category;
                this.selectedIssueId = null;
                this.loadIssues();
            });
        });
        document.getElementById('report-form').addEventListener('submit', (e) => this.submitReport(e));
    },

    async loadIssues() {
        const search = document.getElementById('forum-search').value;
        const params = new URLSearchParams({ action: 'list' });
        if (this.category) params.set('category', this.category);
        if (search) params.set('search', search);

        try {
            const data = await ClassReserve.api('/api/issues.php?' + params);
            const feed = document.getElementById('issues-feed');
            if ((data.issues || []).length === 0) {
                feed.innerHTML = '<div class="empty-state"><i data-lucide="inbox"></i><p>No issues found</p></div>';
            } else {
                feed.innerHTML = data.issues.map(i => this.renderCard(i)).join('');
                feed.querySelectorAll('.issue-card').forEach(card => {
                    card.addEventListener('click', (e) => {
                        if (e.target.closest('button, input, textarea, select')) return;
                        this.openIssue(card.dataset.id);
                    });
                });
                feed.querySelectorAll('.upvote-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => { e.stopPropagation(); this.upvote(btn.dataset.id, btn); });
                });
            }
            lucide.createIcons();
        } catch (err) {
            console.error(err);
            document.getElementById('issues-feed').innerHTML = '<div class="empty-state"><p>Could not load issues</p></div>';
        }
    },

    renderCard(i) {
        const statusClass = { 'Open':'badge-open','Under Review':'badge-review','In Progress':'badge-progress','Resolved':'badge-resolved','Rejected':'badge-rejected' };
        const priClass = { Urgent:'badge-urgent', High:'badge-high', Medium:'badge-medium', Low:'badge-low' };
        const description = i.description || '';
        const roleClass = i.user_role ? `role-${ClassReserve.escapeHtml(i.user_role)}` : '';
        const roleBadge = i.user_role ? `<span class="role-badge ${roleClass}">${ClassReserve.escapeHtml(i.user_role)}</span>` : '';
        const created = i.created_at ? `${ClassReserve.formatDate(i.created_at)} ${ClassReserve.formatTime(i.created_at)}` : '';
        return `
            <article class="issue-card" data-id="${i.id}">
                <div class="issue-card-main">
                    <button class="upvote-btn" data-id="${i.id}" type="button" aria-label="Upvote issue">
                        <i data-lucide="chevron-up"></i>
                        <span class="upvote-count">${Number(i.upvotes || 0)}</span>
                    </button>
                    <div class="issue-content">
                        <div class="issue-heading-row">
                            <h2 class="issue-title">${ClassReserve.escapeHtml(i.title)}</h2>
                            <div class="issue-meta">
                                <span class="badge ${statusClass[i.status]||''}">${ClassReserve.escapeHtml(i.status)}</span>
                                <span class="badge ${priClass[i.priority]||''}">${ClassReserve.escapeHtml(i.priority)}</span>
                                <span class="badge badge-category">${ClassReserve.escapeHtml(i.category)}</span>
                            </div>
                        </div>
                        <div class="issue-snippet">${ClassReserve.escapeHtml(description.substring(0, 180))}${description.length > 180 ? '...' : ''}</div>
                        <div class="issue-footer">
                            <span><i data-lucide="map-pin"></i> ${ClassReserve.escapeHtml(i.room_name)}</span>
                            <span><i data-lucide="user"></i> ${ClassReserve.escapeHtml(i.user_name)} ${roleBadge}</span>
                            <span><i data-lucide="clock"></i> ${ClassReserve.escapeHtml(created)}</span>
                            <span><i data-lucide="message-circle"></i> <span class="comment-count">${Number(i.comment_count || 0)}</span> comments</span>
                        </div>
                        ${i.admin_response ? `<div class="admin-response-banner"><strong>Admin:</strong> ${ClassReserve.escapeHtml(i.admin_response)}</div>` : ''}
                    </div>
                </div>
            </article>`;
    },

    async upvote(id, btn) {
        try {
            const data = await ClassReserve.api('/api/issues.php?action=upvote', {
                method: 'POST',
                body: JSON.stringify({ id: parseInt(id, 10) })
            });
            btn.querySelector('.upvote-count').textContent = data.upvotes;
        } catch (err) { alert(err.message); }
    },

    async openIssue(id) {
        const detail = document.getElementById('issue-detail-panel');
        if (!detail) return;
        detail.innerHTML = '<div class="spinner"></div>';
        this.selectedIssueId = String(id);
        document.querySelectorAll('.issue-card').forEach(card => {
            card.classList.toggle('selected', card.dataset.id === String(id));
        });

        try {
            const data = await ClassReserve.api('/api/issues.php?action=get&id=' + id);
            const i = data.issue;
            const statusClass = { 'Open':'badge-open','Under Review':'badge-review','In Progress':'badge-progress','Resolved':'badge-resolved','Rejected':'badge-rejected' };
            const created = i.created_at ? `${ClassReserve.formatDate(i.created_at)} ${ClassReserve.formatTime(i.created_at)}` : '';
            const roleClass = i.user_role ? `role-${ClassReserve.escapeHtml(i.user_role)}` : '';
            const roleBadge = i.user_role ? `<span class="role-badge ${roleClass}">${ClassReserve.escapeHtml(i.user_role)}</span>` : '';
            const relatedBooking = i.related_booking_title ? `
                <div class="related-booking-card">
                    <strong><i data-lucide="calendar-days"></i> Related Booking</strong>
                    <span>${ClassReserve.escapeHtml(i.related_booking_title)}</span>
                    ${i.related_booking_room ? `<small>${ClassReserve.escapeHtml(i.related_booking_room)}</small>` : ''}
                </div>
            ` : '';
            const warning = i.conflict_warning ? `
                <div class="conflict-warning-card">
                    <strong><i data-lucide="triangle-alert"></i> Conflict Warning</strong>
                    <span>${ClassReserve.escapeHtml(i.conflict_warning)}</span>
                </div>
            ` : '';

            detail.innerHTML = `
                <div class="issue-detail-body">
                    <div class="forum-detail-head">
                        <h2>${ClassReserve.escapeHtml(i.title)}</h2>
                    </div>
                    <div class="issue-meta" style="margin-bottom:12px">
                        <span class="badge ${statusClass[i.status]||''}">${ClassReserve.escapeHtml(i.status)}</span>
                        ${ClassReserve.priorityBadge(ClassReserve.escapeHtml(i.priority))}
                        <span class="badge badge-category">${ClassReserve.escapeHtml(i.category)}</span>
                    </div>
                    <p class="issue-detail-description">${ClassReserve.escapeHtml(i.description)}</p>
                    <div class="issue-detail-meta">
                        <span><i data-lucide="map-pin"></i> ${ClassReserve.escapeHtml(i.room_name)}</span>
                        <span><i data-lucide="user"></i> ${ClassReserve.escapeHtml(i.user_name)} ${roleBadge}</span>
                        <span><i data-lucide="clock"></i> ${ClassReserve.escapeHtml(created)}</span>
                        <span><i data-lucide="chevron-up"></i> ${Number(i.upvotes || 0)} upvotes</span>
                    </div>
                    ${i.admin_response ? `<div class="admin-response-banner"><strong>Admin Response:</strong> ${ClassReserve.escapeHtml(i.admin_response)}</div>` : ''}
                    ${relatedBooking}
                    ${warning}
                    <div class="issue-comments-head"><h3>Comments (${data.comments.length})</h3></div>
                    <div class="comment-stream" id="comment-stream-${i.id}">
                        ${data.comments.map(c => `
                            <div class="comment-item">
                                <span class="comment-author">${ClassReserve.escapeHtml(c.user_name)}</span>
                                <span class="comment-time">${ClassReserve.escapeHtml(ClassReserve.formatDate(c.created_at))}</span>
                                <div class="comment-text">${ClassReserve.escapeHtml(c.message)}</div>
                            </div>
                        `).join('') || '<p class="muted-text">No comments yet</p>'}
                    </div>
                    <form class="comment-composer" data-comment-form="${i.id}">
                        <input type="text" id="new-comment" placeholder="Add a comment..." autocomplete="off">
                        <button class="btn btn-primary btn-sm" type="submit">Post</button>
                    </form>
                    ${this.isAdmin ? `
                        <div class="admin-inline-tools">
                            <h3>Admin Response</h3>
                            <textarea id="admin-response" class="form-textarea" placeholder="Write admin response...">${ClassReserve.escapeHtml(i.admin_response || '')}</textarea>
                            <select id="admin-status" class="form-select">
                                ${['Open','Under Review','In Progress','Resolved','Rejected'].map(s =>
                                    `<option value="${s}" ${s===i.status?'selected':''}>${s}</option>`
                                ).join('')}
                            </select>
                            <button class="btn btn-primary btn-sm" type="button" onclick="Forum.updateStatus(${i.id})">Update Status</button>
                        </div>
                    ` : ''}
                </div>
            `;
            detail.querySelector(`[data-comment-form="${i.id}"]`)?.addEventListener('submit', (e) => {
                e.preventDefault();
                this.postComment(i.id);
            });
            lucide.createIcons();
        } catch (err) {
            detail.innerHTML = `<div class="alert alert-error">${ClassReserve.escapeHtml(err.message)}</div>`;
        }
    },

    async postComment(issueId) {
        const input = document.getElementById('new-comment');
        const msg = input?.value.trim() || '';
        if (!msg) return;
        try {
            await ClassReserve.api('/api/issues.php?action=comment', {
                method: 'POST',
                body: JSON.stringify({ issue_id: issueId, message: msg })
            });
            const card = document.querySelector(`.issue-card[data-id="${issueId}"]`);
            const count = card?.querySelector('.comment-count');
            if (count) count.textContent = String(Number(count.textContent || 0) + 1);
            this.openIssue(issueId);
        } catch (err) { alert(err.message); }
    },

    async updateStatus(id) {
        try {
            await ClassReserve.api('/api/issues.php?action=update_status', {
                method: 'PUT',
                body: JSON.stringify({
                    id,
                    status: document.getElementById('admin-status').value,
                    admin_response: document.getElementById('admin-response').value
                })
            });
            this.selectedIssueId = null;
            this.loadIssues();
        } catch (err) { alert(err.message); }
    },

    async submitReport(e) {
        e.preventDefault();
        const fd = new FormData();
        fd.append('title', document.getElementById('issue-title').value);
        fd.append('room_name', document.getElementById('issue-room').value);
        fd.append('category', document.getElementById('issue-category').value);
        fd.append('priority', document.getElementById('issue-priority').value);
        fd.append('description', document.getElementById('issue-desc').value);
        fd.append('csrf_token', ClassReserve.csrfToken);

        try {
            const res = await fetch(ClassReserve.baseUrl + '/api/issues.php?action=create', {
                method: 'POST',
                headers: { 'X-CSRF-Token': ClassReserve.csrfToken },
                credentials: 'same-origin',
                body: fd
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error);
            document.getElementById('report-modal').classList.remove('open');
            document.getElementById('report-form').reset();
            this.selectedIssueId = null;
            this.loadIssues();
        } catch (err) { alert(err.message); }
    }
};

function debounce(fn, ms) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}

document.addEventListener('DOMContentLoaded', () => {
    Forum.init();
    const issueId = new URLSearchParams(window.location.search).get('issue');
    if (issueId) window.setTimeout(() => Forum.openIssue(issueId), 200);
});
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/includes/layout.php';
