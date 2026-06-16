<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$pageTitle = 'Calendar';
ob_start();
?>
<div class="page-header">
    <h1>Calendar</h1>
    <p>View bookings and maintenance schedules</p>
</div>

<div class="calendar-toolbar">
    <div class="view-toggle">
        <button class="view-btn active" data-view="month">Month</button>
        <button class="view-btn" data-view="week">Week</button>
        <button class="view-btn" data-view="day">Day</button>
    </div>

    <div class="cal-nav">
        <button class="btn btn-ghost btn-sm" id="cal-prev"><i data-lucide="chevron-left"></i></button>
        <h2 id="cal-title"></h2>
        <button class="btn btn-ghost btn-sm" id="cal-next"><i data-lucide="chevron-right"></i></button>
        <button class="btn btn-secondary btn-sm" id="cal-today">Today</button>
    </div>

    <div class="filter-pills">
        <button class="filter-pill active" data-filter="all">All</button>
        <button class="filter-pill" data-filter="approved">Approved</button>
        <button class="filter-pill" data-filter="pending">Pending</button>
        <button class="filter-pill" data-filter="student">Student</button>
        <button class="filter-pill" data-filter="club">Club</button>
        <button class="filter-pill" data-filter="faculty">Faculty</button>
        <button class="filter-pill" data-filter="maintenance">Maintenance</button>
    </div>
</div>

<div class="card" style="padding:0;overflow:hidden">
    <div class="calendar-grid" id="calendar-grid"></div>
</div>

<div class="drawer-overlay" id="event-drawer-overlay"></div>
<div class="drawer" id="event-drawer">
    <button class="drawer-close" id="drawer-close"><i data-lucide="x"></i></button>
    <div id="drawer-content"></div>
</div>

<script>
const Calendar = {
    current: new Date(),
    view: 'month',
    filter: 'all',
    events: [],

    init() {
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.view = btn.dataset.view;
                this.render();
            });
        });
        document.querySelectorAll('.filter-pill').forEach(pill => {
            pill.addEventListener('click', () => {
                document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
                this.filter = pill.dataset.filter;
                this.loadEvents();
            });
        });
        document.getElementById('cal-prev').addEventListener('click', () => this.navigate(-1));
        document.getElementById('cal-next').addEventListener('click', () => this.navigate(1));
        document.getElementById('cal-today').addEventListener('click', () => { this.current = new Date(); this.loadEvents(); });
        document.getElementById('drawer-close').addEventListener('click', () => this.closeDrawer());
        document.getElementById('event-drawer-overlay').addEventListener('click', () => this.closeDrawer());
        this.loadEvents();
    },

    navigate(dir) {
        if (this.view === 'month') this.current.setMonth(this.current.getMonth() + dir);
        else if (this.view === 'week') this.current.setDate(this.current.getDate() + dir * 7);
        else this.current.setDate(this.current.getDate() + dir);
        this.loadEvents();
    },

    async loadEvents() {
        const start = new Date(this.current.getFullYear(), this.current.getMonth(), 1);
        const end = new Date(this.current.getFullYear(), this.current.getMonth() + 1, 0);
        const params = new URLSearchParams({
            action: 'calendar',
            start: start.toISOString().split('T')[0],
            end: end.toISOString().split('T')[0],
            filter: this.filter
        });
        try {
            const data = await ClassReserve.api('/api/bookings.php?' + params);
            this.events = data.events;
            this.render();
        } catch (err) { console.error(err); }
    },

    render() {
        const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        document.getElementById('cal-title').textContent = `${months[this.current.getMonth()]} ${this.current.getFullYear()}`;

        const grid = document.getElementById('calendar-grid');
        const year = this.current.getFullYear();
        const month = this.current.getMonth();
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const today = new Date();

        let html = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].map(d => `<div class="cal-header-cell">${d}</div>`).join('');

        for (let i = 0; i < firstDay; i++) {
            html += `<div class="cal-day other-month"><span class="cal-day-num"></span></div>`;
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
            const isToday = today.getFullYear() === year && today.getMonth() === month && today.getDate() === day;
            const dayEvents = this.events.filter(e => e.start.startsWith(dateStr));

            html += `<div class="cal-day${isToday ? ' today' : ''}">
                <span class="cal-day-num">${day}</span>
                ${dayEvents.map(e => `
                    <div class="event-chip ${ClassReserve.eventChipClass(e)}" data-event='${JSON.stringify(e).replace(/'/g,"&#39;")}'>
                        ${e.title}
                    </div>
                `).join('')}
            </div>`;
        }

        grid.innerHTML = html;
        grid.querySelectorAll('.event-chip').forEach(chip => {
            chip.addEventListener('click', (e) => {
                e.stopPropagation();
                this.openDrawer(JSON.parse(chip.dataset.event));
            });
        });
        lucide.createIcons();
    },

    openDrawer(event) {
        document.getElementById('drawer-content').innerHTML = `
            <h2 style="margin-bottom:16px;font-size:1.3rem">${event.title}</h2>
            <div style="display:flex;gap:8px;margin-bottom:16px">
                ${ClassReserve.statusBadge(event.status)}
                <span class="badge badge-${event.role}">${event.role || 'event'}</span>
            </div>
            <div class="receipt-card">
                <div class="receipt-row"><span class="receipt-label">Room</span><span class="receipt-value">${event.room_name || '—'}</span></div>
                <div class="receipt-row"><span class="receipt-label">Building</span><span class="receipt-value">${event.building || '—'}</span></div>
                <div class="receipt-row"><span class="receipt-label">Start</span><span class="receipt-value">${ClassReserve.formatDate(event.start)} ${ClassReserve.formatTime(event.start)}</span></div>
                <div class="receipt-row"><span class="receipt-label">End</span><span class="receipt-value">${ClassReserve.formatDate(event.end)} ${ClassReserve.formatTime(event.end)}</span></div>
                ${event.user_name ? `<div class="receipt-row"><span class="receipt-label">Booked By</span><span class="receipt-value">${event.user_name}</span></div>` : ''}
                ${event.description ? `<div class="receipt-row"><span class="receipt-label">Description</span><span class="receipt-value">${event.description}</span></div>` : ''}
            </div>
            <button class="btn btn-secondary" style="margin-top:20px;width:100%" onclick="Calendar.closeDrawer()">
                <i data-lucide="arrow-left"></i> Back to Calendar
            </button>
        `;
        document.getElementById('event-drawer').classList.add('open');
        document.getElementById('event-drawer-overlay').classList.add('open');
        lucide.createIcons();
    },

    closeDrawer() {
        document.getElementById('event-drawer').classList.remove('open');
        document.getElementById('event-drawer-overlay').classList.remove('open');
    }
};

document.addEventListener('DOMContentLoaded', () => Calendar.init());
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/includes/layout.php';
