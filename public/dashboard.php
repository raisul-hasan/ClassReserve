<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$user = currentUser();

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
        <p>Welcome back, <?= sanitize($user['name']) ?> - <?= sanitize(rolePortal($user['role'])) ?></p>
    <?php endif; ?>
</div>

<?php if ($user['role'] === 'faculty'): ?>
<div class="stat-grid" id="stat-grid">
    <div class="stat-card stat-rooms">
        <div class="stat-icon"><i data-lucide="door-open"></i></div>
        <div class="stat-label">Available Rooms Today</div>
        <div class="stat-value" id="stat-rooms">-</div>
    </div>
    <div class="stat-card stat-notices">
        <div class="stat-icon"><i data-lucide="calendar-clock"></i></div>
        <div class="stat-label">Today's Schedule</div>
        <div class="stat-value" id="stat-today">-</div>
    </div>
    <div class="stat-card stat-bookings">
        <div class="stat-icon"><i data-lucide="calendar"></i></div>
        <div class="stat-label">Upcoming Bookings</div>
        <div class="stat-value" id="stat-bookings">-</div>
    </div>
    <div class="stat-card stat-pending">
        <div class="stat-icon"><i data-lucide="inbox"></i></div>
        <div class="stat-label">Pending Requests</div>
        <div class="stat-value" id="stat-pending">-</div>
    </div>
</div>

<div class="card" style="margin-bottom:24px">
    <h3 class="card-title"><i data-lucide="zap"></i> Quick Actions</h3>
    <div style="display:flex;gap:12px;flex-wrap:wrap">
        <a href="/faculty/rooms.php" class="btn btn-primary"><i data-lucide="search"></i> Search Available Room</a>
        <a href="/faculty/reserve.php" class="btn btn-primary"><i data-lucide="calendar-plus"></i> Create Booking</a>
        <a href="/faculty/reservations.php" class="btn btn-secondary"><i data-lucide="list"></i> My Bookings</a>
        <a href="/faculty/approvals.php" class="btn btn-secondary"><i data-lucide="check-square"></i> Review Requests</a>
        <a href="/faculty/calendar.php" class="btn btn-secondary"><i data-lucide="calendar"></i> Calendar</a>
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
                        <tr><th>Event</th><th>Room</th><th>Time</th></tr>
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
                        <tr><th>Event</th><th>Room</th><th>Date & Time</th><th>Status</th></tr>
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
                        <tr><th>Requester</th><th>Room</th><th>Event</th><th>Requested Time</th><th>Priority</th></tr>
                    </thead>
                    <tbody id="pending-requests-body">
                        <tr><td colspan="5"><div class="spinner"></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card" style="flex:1">
        <h3 class="card-title"><i data-lucide="bell-ring"></i> Notifications</h3>
        <ul class="activity-list" id="recent-notifications">
            <li class="activity-item"><div class="spinner"></div></li>
        </ul>
    </div>
</div>

<?php else: ?>
<div class="stat-grid" id="stat-grid">
    <div class="stat-card stat-rooms">
        <div class="stat-icon"><i data-lucide="door-open"></i></div>
        <div class="stat-label">Available Rooms</div>
        <div class="stat-value" id="stat-rooms">-</div>
    </div>
    <div class="stat-card stat-bookings">
        <div class="stat-icon"><i data-lucide="calendar-check"></i></div>
        <div class="stat-label">My Bookings</div>
        <div class="stat-value" id="stat-bookings">-</div>
    </div>
    <div class="stat-card stat-pending">
        <div class="stat-icon"><i data-lucide="clock"></i></div>
        <div class="stat-label">Pending</div>
        <div class="stat-value" id="stat-pending">-</div>
    </div>
    <div class="stat-card stat-notices">
        <div class="stat-icon"><i data-lucide="bell"></i></div>
        <div class="stat-label">Notices</div>
        <div class="stat-value" id="stat-notices">-</div>
    </div>
</div>

<div class="card dashboard-search-card">
    <h3 class="card-title"><i data-lucide="search"></i> Search Available Room</h3>
    <div class="dashboard-search-row">
        <div class="form-group">
            <label for="dash-search-date">Date</label>
            <input type="date" id="dash-search-date" class="form-input">
        </div>
        <div class="form-group">
            <label for="dash-start-time">Start Time</label>
            <input type="time" id="dash-start-time" class="form-input" value="09:00">
        </div>
        <div class="form-group">
            <label for="dash-end-time">End Time</label>
            <input type="time" id="dash-end-time" class="form-input" value="11:00">
        </div>
        <div class="form-group">
            <label for="dash-capacity">Capacity</label>
            <select id="dash-capacity" class="form-select">
                <option value="10">10+ Students</option>
                <option value="20">20+ Students</option>
                <option value="30" selected>30+ Students</option>
                <option value="50">50+ Students</option>
                <option value="80">80+ Students</option>
            </select>
        </div>
        <div class="form-group">
            <label for="dash-facilities">Facilities</label>
            <select id="dash-facilities" class="form-select" multiple size="2">
                <option value="Projector">Projector</option>
                <option value="AC">AC</option>
                <option value="Whiteboard">Whiteboard</option>
                <option value="Sound System">Sound System</option>
                <option value="Lab Computer">Lab Computer</option>
            </select>
        </div>
        <button class="btn btn-primary dashboard-search-btn" id="dash-search-btn"><i data-lucide="search"></i> Search Rooms</button>
    </div>
    <div id="dash-search-results" class="dashboard-search-results"></div>
</div>

<div class="dashboard-three-grid">
    <div class="card">
        <h3 class="card-title"><i data-lucide="calendar-check"></i> Upcoming Booking</h3>
        <div id="upcoming-booking">
            <div class="spinner"></div>
        </div>
    </div>

    <div class="card">
        <div class="table-toolbar">
            <h3 class="card-title"><i data-lucide="bell"></i> Notifications</h3>
            <a href="/public/notices.php" class="notification-link">View all</a>
        </div>
        <ul class="activity-list" id="dashboard-notifications">
            <li class="activity-item"><div class="spinner"></div></li>
        </ul>
    </div>

    <div class="card">
        <h3 class="card-title"><i data-lucide="door-open"></i> Today's Available Rooms</h3>
        <div id="today-rooms" class="today-room-list">
            <div class="spinner"></div>
        </div>
        <a href="/public/search-rooms.php" class="card-bottom-link">View full availability <i data-lucide="arrow-right"></i></a>
    </div>
</div>

<div class="card" style="margin-top:20px">
    <div class="table-toolbar">
        <h3 class="card-title"><i data-lucide="calendar-days"></i> Recent Bookings</h3>
        <a href="/public/my-bookings.php" class="notification-link">View all bookings</a>
    </div>
    <ul class="activity-list" id="recent-bookings">
        <li class="activity-item"><div class="spinner"></div></li>
    </ul>
</div>

<div style="margin-top:24px;display:flex;gap:12px;flex-wrap:wrap">
    <a href="/public/new-booking.php" class="btn btn-primary"><i data-lucide="plus"></i> New Booking</a>
    <a href="/public/search-rooms.php" class="btn btn-secondary"><i data-lucide="search"></i> Search Rooms</a>
    <a href="/public/calendar.php" class="btn btn-secondary"><i data-lucide="calendar"></i> View Calendar</a>
    <a href="/public/forum.php" class="btn btn-secondary"><i data-lucide="messages-square"></i> Forum</a>
</div>
<?php endif; ?>

<script>
const userRole = '<?= sanitize($user['role']) ?>';
const statusStep = { pending: 2, approved: 3, completed: 4, rejected: 2, cancelled: 2 };

function statusClass(status) {
    return `status-${status === 'completed' ? 'completed' : status || 'pending'}`;
}

function renderStatusBadge(status) {
    return `<span class="status-pill ${statusClass(status)}">${ClassReserve.escapeHtml(status || 'pending')}</span>`;
}

function selectedFacilities() {
    return Array.from(document.getElementById('dash-facilities').selectedOptions).map(option => option.value);
}

async function runDashboardSearch() {
    const date = document.getElementById('dash-search-date').value;
    const start = document.getElementById('dash-start-time').value;
    const end = document.getElementById('dash-end-time').value;
    const capacity = document.getElementById('dash-capacity').value;
    const results = document.getElementById('dash-search-results');

    if (!date || !start || !end) {
        alert('Please choose date, start time, and end time.');
        return;
    }

    results.innerHTML = '<div class="spinner"></div>';
    const params = new URLSearchParams({
        action: 'search',
        date,
        start_time: start,
        end_time: end,
        min_capacity: capacity,
        max_capacity: 500
    });
    const facilities = selectedFacilities();
    if (facilities.length) params.set('facilities', facilities.join(','));

    try {
        const data = await ClassReserve.api('/api/bookings.php?' + params);
        if (!data.rooms.length) {
            results.innerHTML = '<div class="empty-state">No rooms available for this time slot.</div>';
            return;
        }
        results.innerHTML = data.rooms.slice(0, 4).map(room => `
            <div class="dash-room-result">
                <div>
                    <strong>${ClassReserve.escapeHtml(room.name)}</strong>
                    <span>${ClassReserve.escapeHtml(room.building)} - ${ClassReserve.escapeHtml(room.capacity)} seats</span>
                </div>
                <a class="btn btn-primary btn-sm" href="/public/new-booking.php?${new URLSearchParams({
                    room_id: room.id,
                    date,
                    start_time: start,
                    end_time: end,
                    capacity
                })}">Book</a>
            </div>
        `).join('');
    } catch (err) {
        results.innerHTML = `<div class="empty-state">${ClassReserve.escapeHtml(err.message)}</div>`;
    }
}

function renderProgress(status) {
    const current = statusStep[status] || 1;
    const steps = ['Submitted', 'Pending', 'Approved', 'Completed'];
    return `
        <div class="booking-progress">
            ${steps.map((step, index) => `
                <div class="progress-step ${index + 1 <= current ? 'active' : ''}">
                    <span></span>
                    <small>${step}</small>
                </div>
            `).join('')}
        </div>
    `;
}

function renderUpcoming(bookings) {
    const target = document.getElementById('upcoming-booking');
    const now = new Date();
    const upcoming = bookings
        .filter(b => ['pending', 'approved'].includes(b.status) && new Date(b.end_datetime) >= now)
        .sort((a, b) => new Date(a.start_datetime) - new Date(b.start_datetime))[0];

    if (!upcoming) {
        target.innerHTML = '<div class="empty-state">You have no upcoming bookings.</div>';
        return;
    }

    const capacity = upcoming.attendees || upcoming.capacity || 30;
    target.innerHTML = `
        <div class="upcoming-card">
            <div class="upcoming-head">
                <div>
                    <strong>${ClassReserve.escapeHtml(upcoming.room_name)}</strong>
                    <span>${ClassReserve.escapeHtml(upcoming.title || 'Booking')}</span>
                </div>
                ${renderStatusBadge(upcoming.status)}
            </div>
            <div class="booking-meta-grid">
                <span><i data-lucide="calendar"></i> ${ClassReserve.formatDate(upcoming.start_datetime)}</span>
                <span><i data-lucide="clock"></i> ${ClassReserve.formatTime(upcoming.start_datetime)} - ${ClassReserve.formatTime(upcoming.end_datetime)}</span>
                <span><i data-lucide="users"></i> ${ClassReserve.escapeHtml(capacity)} Students</span>
            </div>
            <h4>Booking Progress</h4>
            ${renderProgress(upcoming.status)}
            <div class="upcoming-actions">
                <a href="/public/my-bookings.php" class="btn btn-secondary btn-sm">View Details</a>
                <button class="btn btn-ghost btn-sm" id="cancel-upcoming" data-id="${upcoming.id}">Cancel Request</button>
            </div>
        </div>
    `;
    document.getElementById('cancel-upcoming')?.addEventListener('click', async () => {
        if (!confirm('Cancel this booking request?')) return;
        await ClassReserve.api('/api/bookings.php?action=cancel', {
            method: 'POST',
            body: JSON.stringify({ id: Number(upcoming.id) })
        });
        await loadDashboard();
        await ClassReserve.loadNotifications();
    });
    lucide.createIcons();
}

function renderNotifications(notifications) {
    const target = document.getElementById('dashboard-notifications') || document.getElementById('recent-notifications');
    if (!notifications.length) {
        target.innerHTML = '<li class="empty-state">No notifications</li>';
        return;
    }
    target.innerHTML = notifications.slice(0, 3).map(n => `
        <li class="activity-item">
            <span class="activity-dot ${ClassReserve.notificationDotClass(n.type)}"></span>
            <div>
                <strong>${ClassReserve.escapeHtml(n.title)}</strong><br>
                <span class="muted-text">${ClassReserve.escapeHtml(n.message)}</span>
            </div>
        </li>
    `).join('');
}

function renderRecentBookings(bookings) {
    const target = document.getElementById('recent-bookings');
    if (!bookings.length) {
        target.innerHTML = '<li class="empty-state">No bookings yet</li>';
        return;
    }
    target.innerHTML = bookings.map(b => `
        <li class="activity-item">
            <span class="activity-dot dot-${b.status === 'approved' ? 'success' : b.status === 'pending' ? 'pending' : 'error'}"></span>
            <div style="flex:1">
                <strong>${ClassReserve.escapeHtml(b.title || 'Booking')}</strong> - ${ClassReserve.escapeHtml(b.room_name || '')}<br>
                <span class="muted-text">${ClassReserve.formatDate(b.start_datetime)} ${ClassReserve.formatTime(b.start_datetime)}</span>
            </div>
            ${renderStatusBadge(b.status)}
        </li>
    `).join('');
}

async function loadTodayRooms() {
    const target = document.getElementById('today-rooms');
    const today = new Date().toISOString().split('T')[0];
    const slots = [
        ['10:00', '12:00'],
        ['13:00', '15:00'],
        ['16:00', '18:00']
    ];

    const rows = [];
    for (const slot of slots) {
        const params = new URLSearchParams({
            action: 'search',
            date: today,
            start_time: slot[0],
            end_time: slot[1],
            min_capacity: 1,
            max_capacity: 500
        });
        try {
            const data = await ClassReserve.api('/api/bookings.php?' + params);
            if (data.rooms[0]) rows.push({ room: data.rooms[0], slot });
        } catch (err) {
            console.error(err);
        }
    }

    if (!rows.length) {
        target.innerHTML = '<div class="empty-state">No rooms available today.</div>';
        return;
    }

    target.innerHTML = rows.map(({ room, slot }) => `
        <div class="today-room-row">
            <div>
                <strong>${ClassReserve.escapeHtml(room.name)}</strong>
                <span>${ClassReserve.formatTime(today + ' ' + slot[0])} - ${ClassReserve.formatTime(today + ' ' + slot[1])}</span>
            </div>
            <span class="status-pill status-approved">Available</span>
        </div>
    `).join('');
}

async function loadDashboard() {
    const data = await ClassReserve.api('/api/dashboard.php');
    const s = data.stats;
    document.getElementById('stat-rooms').textContent = s.rooms ?? 0;
    document.getElementById('stat-bookings').textContent = s.bookings ?? 0;
    document.getElementById('stat-pending').textContent = s.pending ?? 0;
    document.getElementById('stat-notices').textContent = s.notices ?? s.issues ?? 0;

    const bookings = await ClassReserve.api('/api/bookings.php?action=list');
    renderUpcoming(bookings.bookings || []);
    renderRecentBookings(data.recent_bookings || []);
    renderNotifications(data.recent_notifications || []);
}

function emptyRow(cols, message) {
    return `<tr><td colspan="${cols}" class="empty-state">${message}</td></tr>`;
}

function roomText(booking) {
    const room = ClassReserve.escapeHtml(booking.room_name || 'Room');
    const building = ClassReserve.escapeHtml(booking.building || '');
    return `<strong>${room}</strong>${building ? `<br><span class="muted-text">${building}</span>` : ''}`;
}

function timeRange(booking) {
    return `${ClassReserve.formatTime(booking.start_datetime)} - ${ClassReserve.formatTime(booking.end_datetime)}`;
}

function dateTimeRange(booking) {
    return `${ClassReserve.formatDate(booking.start_datetime)}<br><span class="muted-text">${timeRange(booking)}</span>`;
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
        todayEl.innerHTML = todaySchedule.length === 0
            ? emptyRow(3, 'No faculty bookings scheduled today')
            : todaySchedule.map(b => `
                <tr>
                    <td><strong>${ClassReserve.escapeHtml(b.title || 'Booking')}</strong><br><span class="muted-text">${ClassReserve.escapeHtml(b.description || '')}</span></td>
                    <td>${roomText(b)}</td>
                    <td>${timeRange(b)}<br>${ClassReserve.statusBadge(b.status)}</td>
                </tr>
            `).join('');

        const upcomingEl = document.getElementById('upcoming-bookings-body');
        upcomingEl.innerHTML = upcomingBookings.length === 0
            ? emptyRow(4, 'No upcoming faculty bookings')
            : upcomingBookings.map(b => `
                <tr>
                    <td><strong>${ClassReserve.escapeHtml(b.title || 'Booking')}</strong><br><span class="muted-text">${ClassReserve.escapeHtml(b.description || '')}</span></td>
                    <td>${roomText(b)}</td>
                    <td>${dateTimeRange(b)}</td>
                    <td>${ClassReserve.statusBadge(b.status)}</td>
                </tr>
            `).join('');

        const pendingEl = document.getElementById('pending-requests-body');
        pendingEl.innerHTML = pendingRequests.length === 0
            ? emptyRow(5, 'No pending student or club requests')
            : pendingRequests.map(b => `
                <tr>
                    <td><strong>${ClassReserve.escapeHtml(b.user_name || '')}</strong><br><span class="role-badge ${roleClass(b.user_role)}">${roleLabel(b.user_role)}</span></td>
                    <td>${roomText(b)}</td>
                    <td><strong>${ClassReserve.escapeHtml(b.title || 'Booking')}</strong></td>
                    <td>${dateTimeRange(b)}</td>
                    <td><span class="role-badge ${roleClass(b.user_role)}">Tier ${ClassReserve.escapeHtml(String(b.priority || ''))}</span></td>
                </tr>
            `).join('');

        renderNotifications(data.recent_notifications || []);
        lucide.createIcons();
    } catch (err) {
        document.getElementById('today-schedule-body').innerHTML = emptyRow(3, 'Could not load dashboard data');
        document.getElementById('upcoming-bookings-body').innerHTML = emptyRow(4, 'Could not load dashboard data');
        document.getElementById('pending-requests-body').innerHTML = emptyRow(5, 'Could not load dashboard data');
        console.error(err);
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    if (userRole === 'faculty') {
        const tabButtons = document.querySelectorAll('#dashboard-tabs .auth-tab');
        const tabPanels = document.querySelectorAll('.tab-panel');
        const showTab = target => {
            tabButtons.forEach(tab => tab.classList.toggle('active', tab.dataset.target === target));
            tabPanels.forEach(panel => panel.classList.toggle('hidden', panel.id !== target));
        };
        tabButtons.forEach(tab => tab.addEventListener('click', () => showTab(tab.dataset.target)));
        await loadFacultyDashboard();
        return;
    }

    const today = new Date().toISOString().split('T')[0];
    document.getElementById('dash-search-date').min = today;
    document.getElementById('dash-search-date').value = today;
    document.getElementById('dash-search-btn').addEventListener('click', runDashboardSearch);

    try {
        await Promise.all([loadDashboard(), loadTodayRooms()]);
        lucide.createIcons();
    } catch (err) {
        console.error(err);
    }
});
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/includes/layout.php';
