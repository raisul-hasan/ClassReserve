<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$pageTitle = 'Classroom Forum';
$user = currentUser();
$isAdmin = $user['role'] === 'admin';
ob_start();
?>
<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start">
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

<div class="filter-pills" style="margin-bottom:20px">
    <button class="filter-pill active" data-category="">All</button>
    <button class="filter-pill" data-category="Equipment">Equipment</button>
    <button class="filter-pill" data-category="Comfort">Comfort</button>
    <button class="filter-pill" data-category="Safety">Safety</button>
    <button class="filter-pill" data-category="Scheduling">Scheduling</button>
    <button class="filter-pill" data-category="Other">Other</button>
</div>

<div id="issues-feed">
    <div class="spinner"></div>
</div>

<div class="drawer-overlay" id="issue-drawer-overlay"></div>
<div class="drawer" id="issue-drawer">
    <button class="drawer-close" id="issue-drawer-close"><i data-lucide="x"></i></button>
    <div id="issue-drawer-content"></div>
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
                <label>Date Noticed</label>
                <input type="date" id="issue-date" class="form-input" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="issue-desc" class="form-textarea" required placeholder="Describe the issue in detail..."></textarea>
            </div>
            <div class="form-group">
                <label>Attachment</label>
                <input type="file" id="issue-file" class="form-input">
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

    init() {
        this.loadIssues();
        document.getElementById('forum-search').addEventListener('input', debounce(() => this.loadIssues(), 300));
        document.querySelectorAll('[data-category]').forEach(pill => {
            pill.addEventListener('click', () => {
                document.querySelectorAll('[data-category]').forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
                this.category = pill.dataset.category;
                this.loadIssues();
            });
        });
        document.getElementById('issue-drawer-close').addEventListener('click', () => this.closeDrawer());
        document.getElementById('issue-drawer-overlay').addEventListener('click', () => this.closeDrawer());
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
            if (data.issues.length === 0) {
                feed.innerHTML = '<div class="empty-state"><i data-lucide="inbox"></i><p>No issues found</p></div>';
            } else {
                feed.innerHTML = data.issues.map(i => this.renderCard(i)).join('');
                feed.querySelectorAll('.issue-card').forEach(card => {
                    card.addEventListener('click', (e) => {
                        if (e.target.closest('.upvote-btn')) return;
                        this.openIssue(card.dataset.id);
                    });
                });
                feed.querySelectorAll('.upvote-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => { e.stopPropagation(); this.upvote(btn.dataset.id, btn); });
                });
            }
            lucide.createIcons();
        } catch (err) { console.error(err); }
    },

    renderCard(i) {
        const statusClass = { 'Open':'badge-open','Under Review':'badge-review','In Progress':'badge-progress','Resolved':'badge-resolved','Rejected':'badge-rejected' };
        const priClass = { Urgent:'badge-urgent', High:'badge-high', Medium:'badge-medium', Low:'badge-low' };
        return `
            <div class="issue-card" data-id="${i.id}">
                <button class="upvote-btn" data-id="${i.id}">
                    <i data-lucide="chevron-up"></i>
                    <span class="upvote-count">${i.upvotes}</span>
                </button>
                <div class="issue-content">
                    <div class="issue-title">${i.title}</div>
                    <div class="issue-meta">
                        <span class="badge ${statusClass[i.status]||''}">${i.status}</span>
                        <span class="badge ${priClass[i.priority]||''}">${i.priority}</span>
                        <span class="badge" style="background:rgba(94,101,123,.15)">${i.category}</span>
                    </div>
                    <div class="issue-snippet">${i.description.substring(0, 150)}${i.description.length > 150 ? '...' : ''}</div>
                    <div class="issue-footer">
                        <span><i data-lucide="map-pin"></i> ${i.room_name}</span>
                        <span><i data-lucide="user"></i> ${i.user_name}</span>
                        <span><i data-lucide="message-circle"></i> ${i.comment_count} comments</span>
                    </div>
                    ${i.admin_response ? `<div class="admin-response-banner"><strong>Admin:</strong> ${i.admin_response}</div>` : ''}
                </div>
            </div>`;
    },

    async upvote(id, btn) {
        try {
            const data = await ClassReserve.api('/api/issues.php?action=upvote', {
                method: 'POST',
                body: JSON.stringify({ id: parseInt(id) })
            });
            btn.querySelector('.upvote-count').textContent = data.upvotes;
        } catch (err) { console.error(err); }
    },

    async openIssue(id) {
        try {
            const data = await ClassReserve.api('/api/issues.php?action=get&id=' + id);
            const i = data.issue;
            const statusClass = { 'Open':'badge-open','Under Review':'badge-review','In Progress':'badge-progress','Resolved':'badge-resolved','Rejected':'badge-rejected' };

            document.getElementById('issue-drawer-content').innerHTML = `
                <h2 style="margin-bottom:12px">${i.title}</h2>
                <div class="issue-meta" style="margin-bottom:16px">
                    <span class="badge ${statusClass[i.status]||''}">${i.status}</span>
                    ${ClassReserve.priorityBadge(i.priority)}
                    <span class="badge" style="background:rgba(94,101,123,.15)">${i.category}</span>
                </div>
                <p style="color:var(--cr-slate);line-height:1.6;margin-bottom:16px">${i.description}</p>
                <div style="font-size:.8rem;color:var(--cr-slate);margin-bottom:20px">
                    <i data-lucide="map-pin" style="width:14px;display:inline"></i> ${i.room_name} ·
                    <i data-lucide="user" style="width:14px;display:inline"></i> ${i.user_name}
                </div>
                ${i.admin_response ? `<div class="admin-response-banner" style="margin-bottom:20px"><strong>Admin Response:</strong> ${i.admin_response}</div>` : ''}
                <h4 style="margin-bottom:12px">Comments (${data.comments.length})</h4>
                <div class="comment-stream" id="comment-stream">
                    ${data.comments.map(c => `
                        <div class="comment-item">
                            <span class="comment-author">${c.user_name}</span>
                            <span class="comment-time">${ClassReserve.formatDate(c.created_at)}</span>
                            <div class="comment-text">${c.message}</div>
                        </div>
                    `).join('') || '<p style="color:var(--cr-slate);font-size:.85rem">No comments yet</p>'}
                </div>
                <div class="comment-composer">
                    <input type="text" id="new-comment" placeholder="Add a comment...">
                    <button class="btn btn-primary btn-sm" onclick="Forum.postComment(${i.id})">Post</button>
                </div>
                ${this.isAdmin ? `
                    <div style="margin-top:24px;padding-top:20px;border-top:1px solid rgba(241,230,210,.08)">
                        <h4 style="margin-bottom:12px">Admin Response</h4>
                        <textarea id="admin-response" class="form-textarea" placeholder="Write admin response...">${i.admin_response || ''}</textarea>
                        <select id="admin-status" class="form-select" style="margin-top:8px">
                            ${['Open','Under Review','In Progress','Resolved','Rejected'].map(s =>
                                `<option value="${s}" ${s===i.status?'selected':''}>${s}</option>`
                            ).join('')}
                        </select>
                        <button class="btn btn-primary btn-sm" style="margin-top:8px" onclick="Forum.updateStatus(${i.id})">Update Status</button>
                    </div>
                ` : ''}
            `;
            document.getElementById('issue-drawer').classList.add('open');
            document.getElementById('issue-drawer-overlay').classList.add('open');
            lucide.createIcons();
        } catch (err) { console.error(err); }
    },

    async postComment(issueId) {
        const msg = document.getElementById('new-comment').value.trim();
        if (!msg) return;
        try {
            await ClassReserve.api('/api/issues.php?action=comment', {
                method: 'POST',
                body: JSON.stringify({ issue_id: issueId, message: msg })
            });
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
            this.closeDrawer();
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
            const res = await fetch('/api/issues.php?action=create', {
                method: 'POST',
                headers: { 'X-CSRF-Token': ClassReserve.csrfToken },
                body: fd
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error);
            document.getElementById('report-modal').classList.remove('open');
            document.getElementById('report-form').reset();
            this.loadIssues();
        } catch (err) { alert(err.message); }
    },

    closeDrawer() {
        document.getElementById('issue-drawer').classList.remove('open');
        document.getElementById('issue-drawer-overlay').classList.remove('open');
    }
};

function debounce(fn, ms) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}

document.addEventListener('DOMContentLoaded', () => Forum.init());
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/includes/layout.php';
