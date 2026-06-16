<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$pageTitle = 'Search Rooms';
ob_start();
?>
<div class="page-header">
    <h1>Search Rooms</h1>
    <p>Check available rooms by date, time, and capacity before booking</p>
</div>

<div class="card" style="margin-bottom:20px">
    <h3 class="card-title">Room Availability</h3>
    <div class="feature-grid">
        <div class="form-group">
            <label for="search-date">Date</label>
            <input type="date" id="search-date" class="form-input" required>
        </div>
        <div class="form-group">
            <label for="search-start">Start Time</label>
            <input type="time" id="search-start" class="form-input" value="09:00" required>
        </div>
        <div class="form-group">
            <label for="search-end">End Time</label>
            <input type="time" id="search-end" class="form-input" value="11:00" required>
        </div>
        <div class="form-group">
            <label for="search-capacity">Minimum Capacity</label>
            <input type="number" id="search-capacity" class="form-input" min="1" value="10">
        </div>
    </div>
    <button class="btn btn-primary" id="room-search-btn"><i data-lucide="search"></i> Search Rooms</button>
</div>

<div class="room-grid" id="search-results">
    <div class="empty-state"><i data-lucide="search"></i><p>Search to see available rooms</p></div>
</div>

<script>
const today = new Date().toISOString().split('T')[0];
document.getElementById('search-date').min = today;
document.getElementById('search-date').value = today;

document.getElementById('room-search-btn').addEventListener('click', async () => {
    const date = document.getElementById('search-date').value;
    const start = document.getElementById('search-start').value;
    const end = document.getElementById('search-end').value;
    const minCapacity = document.getElementById('search-capacity').value || 1;
    const results = document.getElementById('search-results');

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
        min_capacity: minCapacity,
        max_capacity: 500
    });

    try {
        const data = await ClassReserve.api('/api/bookings.php?' + params);
        if (!data.rooms.length) {
            results.innerHTML = '<div class="empty-state"><i data-lucide="door-closed"></i><p>No rooms available for these criteria</p></div>';
        } else {
            results.innerHTML = data.rooms.map(room => `
                <div class="room-card">
                    <span class="room-type-badge">${ClassReserve.escapeHtml(room.type)}</span>
                    <div class="room-name">${ClassReserve.escapeHtml(room.name)}</div>
                    <div class="room-meta">${ClassReserve.escapeHtml(room.building)} - Floor ${ClassReserve.escapeHtml(room.floor)}</div>
                    <div class="room-capacity"><i data-lucide="users"></i> ${ClassReserve.escapeHtml(room.capacity)} seats</div>
                    <a class="btn btn-primary btn-sm" style="margin-top:14px" href="/public/new-booking.php?${new URLSearchParams({
                        room_id: room.id,
                        date,
                        start_time: start,
                        end_time: end,
                        capacity: minCapacity
                    })}">Book Room</a>
                </div>
            `).join('');
        }
        lucide.createIcons();
    } catch (err) {
        results.innerHTML = `<div class="empty-state">${ClassReserve.escapeHtml(err.message)}</div>`;
    }
});
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/includes/layout.php';
