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
    <?php if ($user['role'] === 'faculty'): ?>
        <h1>Faculty Dashboard</h1>
        <p><span id="faculty-name"><?= sanitize($user['name']) ?></span> - <?= sanitize(rolePortal($user['role'])) ?></p>
    <?php else: ?>
        <h1>Dashboard</h1>
        <p>Welcome back, <?= sanitize($user['name']) ?> — <?= sanitize(rolePortal($user['role'])) ?></p>
    <?php endif; ?>
</div>

<?php if ($user['role'] === 'faculty'): ?>
    <div class="stat-grid" id="stat-grid">
        <div class="stat-card stat-rooms">
            <div class="stat-icon"><i data-lucide="door-open"></i></div>
            <div class="stat-label">Available Rooms Today</div>
            <div class="stat-value" id="stat-rooms">—</div>
        </div>
        <div class="stat-card stat-notices">
            <div class="stat-icon"><i data-lucide="calendar-clock"></i></div>
            <div class="stat-label">Today's Schedule</div>
            <div class="stat-value" id="stat-today">—</div>
        </div>
        <div class="stat-card stat-bookings">
            <div class="stat-icon"><i data-lucide="calendar"></i></div>
            <div class="stat-label">Upcoming Bookings</div>
            <div class="stat-value" id="stat-bookings">—</div>
        </div>
        <div class="stat-card stat-pending">
            <div class="stat-icon"><i data-lucide="inbox"></i></div>
            <div class="stat-label">Pending Requests</div>
            <div class="stat-value" id="stat-pending">—</div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px">
        <h3 class="card-title"><i data-lucide="zap" style="width:16px;display:inline;vertical-align:-2px"></i> Quick Actions</h3>
        <div style="display:flex;gap:12px;flex-wrap:wrap">
            <a href="/faculty/rooms" class="btn btn-primary"><i data-lucide="search"></i> Search Available Room</a>
            <a href="/faculty/reserve.php" class="btn btn-primary"><i data-lucide="calendar-plus"></i> Create Booking</a>
            <a href="/faculty/reservations" class="btn btn-secondary"><i data-lucide="list"></i> My Bookings</a>
            <a href="/faculty/approvals" class="btn btn-secondary"><i data-lucide="check-square"></i> Review Requests</a>
            <a href="/faculty/calendar" class="btn btn-secondary"><i data-lucide="calendar"></i> Calendar</a>
        </div>
    </div>

    <div class="split-grid">
        <div class="card" style="flex:2">
            <div class="auth-tabs" id="dashboard-tabs" style="margin-bottom:20px">
                <button class="auth-tab active" data-target="tab-today">Today's Schedule (<span id="count-today">0</span>)</button>
                <button class="auth-tab" data-target="tab-upcoming">Upcoming Bookings (<span id="count-upcoming">0</span>)</button>
                <button class="auth-tab" data-target="tab-pending">Pending Requests (<span id="count-pending">0</span>)</button>
            </div>

            <div class="tab-panel active" id="tab-today">
                <div style="overflow-x:auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Room</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody id="today-schedule-body">
                            <tr><td colspan="3"><div class="spinner"></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-panel hidden" id="tab-upcoming">
                <div style="overflow-x:auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Room</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="upcoming-bookings-body">
                            <tr><td colspan="4"><div class="spinner"></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-panel hidden" id="tab-pending">
                <div style="overflow-x:auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Requester</th>
                                <th>Room</th>
                                <th>Event</th>
                                <th>Requested Time</th>
                                <th>Priority</th>
                            </tr>
                        </thead>
                        <tbody id="pending-requests-body">
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
        const tabButtons = document.querySelectorAll('#dashboard-tabs .auth-tab');
        const tabPanels = document.querySelectorAll('.tab-panel');

        const showTab = (target) => {
            tabButtons.forEach(t => t.classList.toggle('active', t.dataset.target === target));
            tabPanels.forEach(p => p.classList.toggle('hidden', p.id !== target));
        };

        tabButtons.forEach(tab => {
            tab.addEventListener('click', () => {
                showTab(tab.dataset.target);
            });
        });

        document.querySelectorAll('.action-tab-trigger').forEach(btn => {
            btn.addEventListener('click', () => {
                showTab(btn.dataset.target);
            });
        });

        function emptyRow(cols, message) {
            return `<tr><td colspan="${cols}" class="empty-state">${message}</td></tr>`;
        }

        function roomText(booking) {
            const room = ClassReserve.escapeHtml(booking.room_name || 'Room');
            const building = ClassReserve.escapeHtml(booking.building || '');
            return `<strong>${room}</strong>${building ? `<br><span style="font-size:0.75rem;color:var(--cr-slate)">${building}</span>` : ''}`;
        }

        function timeRange(booking) {
            return `${ClassReserve.formatTime(booking.start_datetime)} - ${ClassReserve.formatTime(booking.end_datetime)}`;
        }

        function dateTimeRange(booking) {
            return `${ClassReserve.formatDate(booking.start_datetime)}<br><span style="font-size:0.75rem;color:var(--cr-slate)">${timeRange(booking)}</span>`;
        }

        function roleLabel(role) {
            return role === 'club' ? 'Club' : (role === 'faculty' ? 'Faculty' : 'Student');
        }

        function roleClass(role) {
            if (role === 'club') return 'role-club';
            if (role === 'faculty') return 'role-faculty';
            return 'role-student';
        }

        async function loadFacultyDashboard() {
            try {
                const data = await ClassReserve.api('/api/dashboard.php');
                const s = data.stats || {};
                const todaySchedule = data.today_schedule || [];
                const upcomingBookings = data.upcoming_bookings || data.my_bookings || [];
                const pendingRequests = data.pending_requests || [];

                document.getElementById('stat-rooms').textContent = s.rooms ?? 0;
                document.getElementById('stat-today').textContent = s.today ?? todaySchedule.length;
                document.getElementById('stat-bookings').textContent = s.bookings ?? 0;
                document.getElementById('stat-pending').textContent = s.pending ?? 0;

                document.getElementById('count-today').textContent = todaySchedule.length;
                document.getElementById('count-upcoming').textContent = upcomingBookings.length;
                document.getElementById('count-pending').textContent = pendingRequests.length;

                const todayEl = document.getElementById('today-schedule-body');
                if (todaySchedule.length === 0) {
                    todayEl.innerHTML = emptyRow(3, 'No faculty bookings scheduled today');
                } else {
                    todayEl.innerHTML = todaySchedule.map(b => `
                        <tr>
                            <td><strong>${ClassReserve.escapeHtml(b.title)}</strong><br><span style="font-size:0.75rem;color:var(--cr-slate)">${ClassReserve.escapeHtml(b.description || '')}</span></td>
                            <td>${roomText(b)}</td>
                            <td>${timeRange(b)}<br>${ClassReserve.statusBadge(b.status)}</td>
                        </tr>
                    `).join('');
                }

                const upcomingEl = document.getElementById('upcoming-bookings-body');
                if (upcomingBookings.length === 0) {
                    upcomingEl.innerHTML = emptyRow(4, 'No upcoming faculty bookings');
                } else {
                    upcomingEl.innerHTML = upcomingBookings.map(b => `
                        <tr>
                            <td><strong>${ClassReserve.escapeHtml(b.title)}</strong><br><span style="font-size:0.75rem;color:var(--cr-slate)">${ClassReserve.escapeHtml(b.description || '')}</span></td>
                            <td>${roomText(b)}</td>
                            <td>${dateTimeRange(b)}</td>
                            <td>${ClassReserve.statusBadge(b.status)}</td>
                        </tr>
                    `).join('');
                }

                const pendingEl = document.getElementById('pending-requests-body');
                if (pendingRequests.length === 0) {
                    pendingEl.innerHTML = emptyRow(5, 'No pending student or club requests');
                } else {
                    pendingEl.innerHTML = pendingRequests.map(b => {
                        const requesterRole = roleLabel(b.user_role);
                        return `
                            <tr>
                                <td><strong>${ClassReserve.escapeHtml(b.user_name)}</strong><br><span class="role-badge ${roleClass(b.user_role)}">${requesterRole}</span></td>
                                <td>${roomText(b)}</td>
                                <td><strong>${ClassReserve.escapeHtml(b.title)}</strong></td>
                                <td>${dateTimeRange(b)}</td>
                                <td><span class="role-badge ${roleClass(b.user_role)}">Tier ${b.priority}</span></td>
                            </tr>
                        `;
                    }).join('');
                }

                const notifEl = document.getElementById('recent-notifications');
                const notifications = data.recent_notifications || [];
                if (notifications.length === 0) {
                    notifEl.innerHTML = '<li class="empty-state">No notifications</li>';
                } else {
                    notifEl.innerHTML = notifications.map(n => `
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
                document.getElementById('today-schedule-body').innerHTML = emptyRow(3, 'Could not load dashboard data');
                document.getElementById('upcoming-bookings-body').innerHTML = emptyRow(4, 'Could not load dashboard data');
                document.getElementById('pending-requests-body').innerHTML = emptyRow(5, 'Could not load dashboard data');
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
