<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$user = currentUser();

// Admin should be sent to the admin panel
if ($user['role'] === 'admin') {
    header('Location: /public/admin.php');
    exit;
}

$pageTitle = 'Dashboard';
ob_start();
?>
<div class="page-header">
    <h1>Dashboard</h1>
    <p>Welcome back, <?= sanitize($user['name']) ?> — <?= sanitize(rolePortal($user['role'])) ?></p>
</div>

<?php if ($user['role'] === 'faculty'): ?>
    <!-- Faculty Portal View -->
    <div class="stat-grid" id="stat-grid">
        <div class="stat-card stat-rooms">
            <div class="stat-icon"><i data-lucide="door-open"></i></div>
            <div class="stat-label">Available Rooms Today</div>
            <div class="stat-value" id="stat-rooms">—</div>
        </div>
        <div class="stat-card stat-bookings">
            <div class="stat-icon"><i data-lucide="calendar"></i></div>
            <div class="stat-label">My Reservations</div>
            <div class="stat-value" id="stat-bookings">—</div>
        </div>
        <div class="stat-card stat-pending">
            <div class="stat-icon"><i data-lucide="inbox"></i></div>
            <div class="stat-label">Pending Requests</div>
            <div class="stat-value" id="stat-pending">—</div>
        </div>
        <div class="stat-card stat-notices">
            <div class="stat-icon"><i data-lucide="bell"></i></div>
            <div class="stat-label">Notices</div>
            <div class="stat-value" id="stat-notices">—</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card" style="margin-bottom:24px">
        <h3 class="card-title"><i data-lucide="zap" style="width:16px;display:inline;vertical-align:-2px"></i> Quick Actions</h3>
        <div style="display:flex;gap:12px;flex-wrap:wrap">
            <a href="/public/new-booking.php" class="btn btn-primary"><i data-lucide="search"></i> Search Available Room</a>
            <a href="/public/new-booking.php" class="btn btn-primary"><i data-lucide="calendar-plus"></i> Reserve Room</a>
            <button class="btn btn-secondary action-tab-trigger" data-target="tab-my-bookings"><i data-lucide="list"></i> View My Bookings</button>
            <button class="btn btn-secondary action-tab-trigger" data-target="tab-pending"><i data-lucide="check-square"></i> Review Pending Requests</button>
            <a href="/public/calendar.php" class="btn btn-secondary"><i data-lucide="calendar"></i> View Calendar</a>
        </div>
    </div>

    <!-- Tabs Content & Notifications Split -->
    <div class="split-grid">
        <div class="card" style="flex:2">
            <div class="auth-tabs" id="dashboard-tabs" style="margin-bottom:20px">
                <button class="auth-tab active" data-target="tab-pending">Review Pending Requests (<span id="count-pending">0</span>)</button>
                <button class="auth-tab" data-target="tab-my-bookings">My Bookings (<span id="count-my-bookings">0</span>)</button>
                <button class="auth-tab" data-target="tab-approved-events">Approved Events</button>
            </div>

            <!-- Tab: Pending Requests -->
            <div class="tab-panel active" id="tab-pending">
                <div style="overflow-x:auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Requester</th>
                                <th>Room</th>
                                <th>Event Details</th>
                                <th>Time</th>
                                <th>Purpose</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="pending-requests-body">
                            <tr><td colspan="6"><div class="spinner"></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab: My Bookings -->
            <div class="tab-panel hidden" id="tab-my-bookings">
                <div style="overflow-x:auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Event Title</th>
                                <th>Room</th>
                                <th>Date & Time</th>
                                <th>Purpose</th>
                                <th>Participants</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="my-bookings-body">
                            <tr><td colspan="7"><div class="spinner"></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab: Approved Events -->
            <div class="tab-panel hidden" id="tab-approved-events">
                <div style="overflow-x:auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Event Title</th>
                                <th>Booked By</th>
                                <th>Room</th>
                                <th>Date & Time</th>
                                <th>Purpose</th>
                            </tr>
                        </thead>
                        <tbody id="approved-events-body">
                            <tr><td colspan="5"><div class="spinner"></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card" style="flex:1">
            <h3 class="card-title"><i data-lucide="bell-ring" style="width:16px;display:inline;vertical-align:-2px"></i> Notifications</h3>
            <ul class="activity-list" id="recent-notifications">
                <li class="activity-item"><div class="spinner"></div></li>
            </ul>
        </div>
    </div>

    <!-- Review Drawer -->
    <div class="drawer-overlay" id="review-drawer-overlay"></div>
    <div class="drawer" id="review-drawer">
        <button class="drawer-close" id="review-drawer-close"><i data-lucide="x"></i></button>
        <div id="review-drawer-content" style="margin-top:20px"></div>
    </div>

<?php else: ?>
    <!-- Student / Club Portal View -->
    <div class="stat-grid" id="stat-grid">
        <div class="stat-card stat-rooms">
            <div class="stat-icon"><i data-lucide="door-open"></i></div>
            <div class="stat-label">Available Rooms</div>
            <div class="stat-value" id="stat-rooms">—</div>
        </div>
        <div class="stat-card stat-bookings">
            <div class="stat-icon"><i data-lucide="calendar"></i></div>
            <div class="stat-label">My Bookings</div>
            <div class="stat-value" id="stat-bookings">—</div>
        </div>
        <div class="stat-card stat-pending">
            <div class="stat-icon"><i data-lucide="clock"></i></div>
            <div class="stat-label">Pending</div>
            <div class="stat-value" id="stat-pending">—</div>
        </div>
        <div class="stat-card stat-notices">
            <div class="stat-icon"><i data-lucide="bell"></i></div>
            <div class="stat-label">Notices</div>
            <div class="stat-value" id="stat-notices">—</div>
        </div>
    </div>

    <div class="split-grid">
        <div class="card">
            <h3 class="card-title"><i data-lucide="calendar-days" style="width:16px;display:inline;vertical-align:-2px"></i> Recent Bookings</h3>
            <ul class="activity-list" id="recent-bookings">
                <li class="activity-item"><div class="spinner"></div></li>
            </ul>
        </div>
        <div class="card">
            <h3 class="card-title"><i data-lucide="bell-ring" style="width:16px;display:inline;vertical-align:-2px"></i> Notifications</h3>
            <ul class="activity-list" id="recent-notifications">
                <li class="activity-item"><div class="spinner"></div></li>
            </ul>
        </div>
    </div>

    <div style="margin-top:24px;display:flex;gap:12px">
        <a href="/public/new-booking.php" class="btn btn-primary"><i data-lucide="plus"></i> New Booking</a>
        <a href="/public/calendar.php" class="btn btn-secondary"><i data-lucide="calendar"></i> View Calendar</a>
        <a href="/public/forum.php" class="btn btn-secondary"><i data-lucide="messages-square"></i> Forum</a>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    const userRole = '<?= $user['role'] ?>';

    if (userRole === 'faculty') {
        // Tab switching
        document.querySelectorAll('#dashboard-tabs .auth-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('#dashboard-tabs .auth-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                const target = tab.dataset.target;
                document.querySelectorAll('.tab-panel').forEach(p => {
                    p.classList.toggle('hidden', p.id !== target);
                });
            });
        });

        // Quick Action tab triggers
        document.querySelectorAll('.action-tab-trigger').forEach(btn => {
            btn.addEventListener('click', () => {
                const target = btn.dataset.target;
                const tab = document.querySelector(`#dashboard-tabs .auth-tab[data-target="${target}"]`);
                if (tab) tab.click();
            });
        });

        // Review drawer utilities
        window.reviewBooking = (bookingJsonStr) => {
            const booking = JSON.parse(decodeURIComponent(bookingJsonStr));
            const attachmentHtml = booking.uploaded_path 
                ? `<div class="receipt-row"><span class="receipt-label">Attachment</span><span class="receipt-value"><a href="/public/${booking.uploaded_path}" target="_blank" class="btn btn-sm btn-secondary" style="font-size:0.75rem;padding:3px 6px"><i data-lucide="file-text"></i> View Document</a></span></div>`
                : '';

            document.getElementById('review-drawer-content').innerHTML = `
                <h2 style="margin-bottom:16px;font-size:1.4rem">Review Booking Request</h2>
                <p style="color:var(--cr-slate);margin-bottom:20px;font-size:0.9rem">Submitted by <strong>${ClassReserve.escapeHtml(booking.user_name)}</strong> (${roleLabel(booking.user_role)})</p>
                
                <div class="receipt-card" style="margin-bottom:24px">
                    <div class="receipt-row"><span class="receipt-label">Event Title</span><span class="receipt-value">${ClassReserve.escapeHtml(booking.title)}</span></div>
                    <div class="receipt-row"><span class="receipt-label">Description</span><span class="receipt-value">${ClassReserve.escapeHtml(booking.description || 'No description provided')}</span></div>
                    <div class="receipt-row"><span class="receipt-label">Room</span><span class="receipt-value">${ClassReserve.escapeHtml(booking.room_name)}</span></div>
                    <div class="receipt-row"><span class="receipt-label">Building</span><span class="receipt-value">${ClassReserve.escapeHtml(booking.building)}</span></div>
                    <div class="receipt-row"><span class="receipt-label">Date</span><span class="receipt-value">${ClassReserve.formatDate(booking.start_datetime)}</span></div>
                    <div class="receipt-row"><span class="receipt-label">Time</span><span class="receipt-value">${ClassReserve.formatTime(booking.start_datetime)} — ${ClassReserve.formatTime(booking.end_datetime)}</span></div>
                    <div class="receipt-row"><span class="receipt-label">Expected Attendees</span><span class="receipt-value">${booking.attendees ?? '—'}</span></div>
                    <div class="receipt-row"><span class="receipt-label">Booking Type / Purpose</span><span class="receipt-value">${ClassReserve.escapeHtml(booking.purpose || '—')}</span></div>
                    ${attachmentHtml}
                </div>

                <div class="form-group">
                    <label for="review-note">Review Note (Reason)</label>
                    <input type="text" id="review-note" class="form-input" placeholder="e.g. Approved for class prep">
                </div>

                <div style="display:flex;gap:12px;margin-top:20px">
                    <button class="btn btn-primary" style="flex:1" onclick="submitReview(${booking.id}, 'approved')"><i data-lucide="check"></i> Approve</button>
                    <button class="btn btn-secondary" style="flex:1;color:#ff8a85;border-color:rgba(137,29,26,0.3)" onclick="submitReview(${booking.id}, 'rejected')"><i data-lucide="x"></i> Reject</button>
                </div>
            `;

            document.getElementById('review-drawer').classList.add('open');
            document.getElementById('review-drawer-overlay').classList.add('open');
            lucide.createIcons();
        };

        window.submitReview = async (id, status) => {
            const note = document.getElementById('review-note').value;
            try {
                await ClassReserve.api('/api/bookings.php?action=update', {
                    method: 'PUT',
                    body: JSON.stringify({ id, status, note })
                });
                document.querySelectorAll('.drawer, .drawer-overlay').forEach(d => d.classList.remove('open'));
                loadFacultyDashboard();
            } catch (err) {
                alert(err.message);
            }
        };

        window.cancelBooking = async (id) => {
            if (!confirm('Are you sure you want to cancel this booking?')) return;
            try {
                await ClassReserve.api('/api/bookings.php?action=update', {
                    method: 'PUT',
                    body: JSON.stringify({ id, status: 'cancelled' })
                });
                loadFacultyDashboard();
            } catch (err) {
                alert(err.message);
            }
        };

        function roleLabel(role) {
            return role === 'club' ? 'Club' : (role === 'faculty' ? 'Faculty' : 'Student');
        }

        async function loadFacultyDashboard() {
            try {
                const data = await ClassReserve.api('/api/dashboard.php');
                const s = data.stats;
                document.getElementById('stat-rooms').textContent = s.rooms ?? 0;
                document.getElementById('stat-bookings').textContent = s.bookings ?? 0;
                document.getElementById('stat-pending').textContent = s.pending ?? 0;
                document.getElementById('stat-notices').textContent = s.notices ?? 0;
                
                document.getElementById('count-pending').textContent = s.pending ?? 0;
                document.getElementById('count-my-bookings').textContent = s.bookings ?? 0;

                // Pending Requests
                const pendingEl = document.getElementById('pending-requests-body');
                if (!data.pending_requests || data.pending_requests.length === 0) {
                    pendingEl.innerHTML = '<tr><td colspan="6" class="empty-state">No pending requests awaiting your review</td></tr>';
                } else {
                    pendingEl.innerHTML = data.pending_requests.map(b => {
                        const priorityClass = b.priority == 3 ? 'role-faculty' : (b.priority == 2 ? 'role-club' : 'role-student');
                        const priorityText = b.priority == 3 ? 'Faculty' : (b.priority == 2 ? 'Club' : 'Student');
                        const timeStr = `${ClassReserve.formatDate(b.start_datetime)}<br><span style="font-size:0.75rem;color:var(--cr-slate)">${ClassReserve.formatTime(b.start_datetime)} — ${ClassReserve.formatTime(b.end_datetime)}</span>`;
                        const escBooking = encodeURIComponent(JSON.stringify(b));
                        return `
                            <tr>
                                <td><strong>${ClassReserve.escapeHtml(b.user_name)}</strong><br><span class="role-badge ${priorityClass}">${priorityText}</span></td>
                                <td><strong>${ClassReserve.escapeHtml(b.room_name)}</strong><br><span style="font-size:0.75rem;color:var(--cr-slate)">${ClassReserve.escapeHtml(b.building)}</span></td>
                                <td><strong>${ClassReserve.escapeHtml(b.title)}</strong></td>
                                <td>${timeStr}</td>
                                <td>${ClassReserve.escapeHtml(b.purpose || '—')}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="reviewBooking('${escBooking}')">Review</button>
                                </td>
                            </tr>
                        `;
                    }).join('');
                }

                // My Bookings
                const myBookingsEl = document.getElementById('my-bookings-body');
                if (!data.my_bookings || data.my_bookings.length === 0) {
                    myBookingsEl.innerHTML = '<tr><td colspan="7" class="empty-state">You have not made any bookings yet</td></tr>';
                } else {
                    myBookingsEl.innerHTML = data.my_bookings.map(b => {
                        const timeStr = `${ClassReserve.formatDate(b.start_datetime)}<br><span style="font-size:0.75rem;color:var(--cr-slate)">${ClassReserve.formatTime(b.start_datetime)} — ${ClassReserve.formatTime(b.end_datetime)}</span>`;
                        const showCancel = b.status === 'pending' || b.status === 'approved';
                        const actionHtml = showCancel 
                            ? `<button class="btn btn-sm btn-ghost" style="color:#ff8a85" onclick="cancelBooking(${b.id})">Cancel</button>` 
                            : '—';
                        return `
                            <tr>
                                <td><strong>${ClassReserve.escapeHtml(b.title)}</strong></td>
                                <td><strong>${ClassReserve.escapeHtml(b.room_name)}</strong></td>
                                <td>${timeStr}</td>
                                <td>${ClassReserve.escapeHtml(b.purpose || '—')}</td>
                                <td>${b.attendees ?? '—'}</td>
                                <td>${ClassReserve.statusBadge(b.status)}</td>
                                <td>${actionHtml}</td>
                            </tr>
                        `;
                    }).join('');
                }

                // Approved Events
                const approvedEl = document.getElementById('approved-events-body');
                if (!data.approved_bookings || data.approved_bookings.length === 0) {
                    approvedEl.innerHTML = '<tr><td colspan="5" class="empty-state">No approved academic events scheduled</td></tr>';
                } else {
                    approvedEl.innerHTML = data.approved_bookings.map(b => {
                        const priorityClass = b.priority == 3 ? 'role-faculty' : (b.priority == 2 ? 'role-club' : 'role-student');
                        const priorityText = b.priority == 3 ? 'Faculty' : (b.priority == 2 ? 'Club' : 'Student');
                        const timeStr = `${ClassReserve.formatDate(b.start_datetime)}<br><span style="font-size:0.75rem;color:var(--cr-slate)">${ClassReserve.formatTime(b.start_datetime)} — ${ClassReserve.formatTime(b.end_datetime)}</span>`;
                        return `
                            <tr>
                                <td><strong>${ClassReserve.escapeHtml(b.title)}</strong></td>
                                <td><strong>${ClassReserve.escapeHtml(b.user_name)}</strong><br><span class="role-badge ${priorityClass}">${priorityText}</span></td>
                                <td><strong>${ClassReserve.escapeHtml(b.room_name)}</strong><br><span style="font-size:0.75rem;color:var(--cr-slate)">${ClassReserve.escapeHtml(b.building)}</span></td>
                                <td>${timeStr}</td>
                                <td>${ClassReserve.escapeHtml(b.purpose || '—')}</td>
                            </tr>
                        `;
                    }).join('');
                }

                // Notifications
                const notifEl = document.getElementById('recent-notifications');
                if (data.recent_notifications.length === 0) {
                    notifEl.innerHTML = '<li class="empty-state">No notifications</li>';
                } else {
                    notifEl.innerHTML = data.recent_notifications.map(n => `
                        <li class="activity-item">
                            <span class="activity-dot dot-${n.type === 'success' ? 'success' : n.type === 'pending' ? 'pending' : n.type === 'error' ? 'error' : 'info'}"></span>
                            <div>
                                <strong>${ClassReserve.escapeHtml(n.title)}</strong><br>
                                <span style="font-size:.8rem;color:var(--cr-slate)">${ClassReserve.escapeHtml(n.message)}</span>
                            </div>
                        </li>
                    `).join('');
                }

                lucide.createIcons();
            } catch (err) {
                console.error(err);
            }
        }

        loadFacultyDashboard();
    } else {
        // Original Student Dashboard Logic
        try {
            const data = await ClassReserve.api('/api/dashboard.php');
            const s = data.stats;
            document.getElementById('stat-rooms').textContent = s.rooms ?? 0;
            document.getElementById('stat-bookings').textContent = s.bookings ?? 0;
            document.getElementById('stat-pending').textContent = s.pending ?? 0;
            document.getElementById('stat-notices').textContent = s.notices ?? s.issues ?? 0;

            const bookingsEl = document.getElementById('recent-bookings');
            if (data.recent_bookings.length === 0) {
                bookingsEl.innerHTML = '<li class="empty-state">No bookings yet</li>';
            } else {
                bookingsEl.innerHTML = data.recent_bookings.map(b => `
                    <li class="activity-item">
                        <span class="activity-dot dot-${b.status === 'approved' ? 'success' : b.status === 'pending' ? 'pending' : 'error'}"></span>
                        <div style="flex:1">
                            <strong>${ClassReserve.escapeHtml(b.title)}</strong> — ${ClassReserve.escapeHtml(b.room_name)}<br>
                            <span style="font-size:.75rem;color:var(--cr-slate)">${ClassReserve.formatDate(b.start_datetime)} ${ClassReserve.formatTime(b.start_datetime)}</span>
                        </div>
                        ${ClassReserve.statusBadge(b.status)}
                    </li>
                `).join('');
            }

            const notifEl = document.getElementById('recent-notifications');
            if (data.recent_notifications.length === 0) {
                notifEl.innerHTML = '<li class="empty-state">No notifications</li>';
            } else {
                notifEl.innerHTML = data.recent_notifications.map(n => `
                    <li class="activity-item">
                        <span class="activity-dot dot-${n.type === 'success' ? 'success' : n.type === 'pending' ? 'pending' : n.type === 'error' ? 'error' : 'info'}"></span>
                        <div>
                            <strong>${ClassReserve.escapeHtml(n.title)}</strong><br>
                            <span style="font-size:.8rem;color:var(--cr-slate)">${ClassReserve.escapeHtml(n.message)}</span>
                        </div>
                    </li>
                `).join('');
            }
            lucide.createIcons();
        } catch (err) {
            console.error(err);
        }
    }
});
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/includes/layout.php';
