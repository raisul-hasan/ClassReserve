<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$pageTitle = 'New Booking';
$user = currentUser();
$priorityLabels = ['student' => 'Tier 1 — Student', 'club' => 'Tier 2 — Club', 'faculty' => 'Tier 3 — Faculty'];
ob_start();
?>
<div class="page-header">
    <h1>New Booking</h1>
    <p>Find a room and submit your reservation request</p>
</div>

<div class="wizard-steps">
    <div class="wizard-step active" data-step="1">Find Room</div>
    <div class="wizard-step" data-step="2">Select Room</div>
    <div class="wizard-step" data-step="3">Details</div>
</div>

<!-- Step 1 -->
<div class="wizard-panel active" id="step-1">
    <div class="card">
        <h3 class="card-title">Step 1: Find Room</h3>
        <div class="grid gap-4" style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div class="form-group">
                <label for="booking-date">Date</label>
                <input type="date" id="booking-date" class="form-input" required>
            </div>
            <div class="form-group">
                <label>Capacity Range</label>
                <input type="range" id="capacity-min" class="range-slider" min="5" max="100" value="10">
                <input type="range" id="capacity-max" class="range-slider" min="10" max="200" value="80">
                <div class="capacity-display"><span id="cap-min">10</span> — <span id="cap-max">80</span> seats</div>
            </div>
            <div class="form-group">
                <label for="start-time">Start Time</label>
                <input type="time" id="start-time" class="form-input" value="09:00" required>
            </div>
            <div class="form-group">
                <label for="end-time">End Time</label>
                <input type="time" id="end-time" class="form-input" value="11:00" required>
            </div>
        </div>
        <button class="btn btn-primary mt-4" id="search-rooms-btn" style="margin-top:16px">
            <i data-lucide="search"></i> Search Available Rooms
        </button>
    </div>
</div>

<!-- Step 2 -->
<div class="wizard-panel" id="step-2">
    <div class="card">
        <h3 class="card-title">Step 2: Select Room</h3>
        <div class="room-grid" id="room-results">
            <div class="empty-state">Search for rooms in Step 1</div>
        </div>
        <div style="margin-top:16px;display:flex;gap:12px">
            <button class="btn btn-secondary" onclick="goToStep(1)">Back</button>
            <button class="btn btn-primary" id="select-room-btn" disabled>Continue</button>
        </div>
    </div>
</div>

<!-- Step 3 -->
<div class="wizard-panel" id="step-3">
    <div class="card">
        <h3 class="card-title">Step 3: Booking Details</h3>
        <form id="booking-form">
            <div class="form-group">
                <label for="booking-title">Event Title</label>
                <input type="text" id="booking-title" class="form-input" required placeholder="e.g. Study Group Session">
            </div>
            <div class="form-group">
                <label for="booking-desc">Description</label>
                <textarea id="booking-desc" class="form-textarea" placeholder="Describe your event..."></textarea>
            </div>
            <div class="form-group">
                <label for="attendees">Expected Attendees</label>
                <input type="number" id="attendees" class="form-input" min="1" value="10">
            </div>
            <div class="form-group">
                <label>Supporting Document</label>
                <div class="drop-zone" id="drop-zone">
                    <i data-lucide="upload-cloud"></i>
                    <p>Drag &amp; drop a file here, or click to browse</p>
                    <input type="file" id="file-input" hidden>
                    <p id="file-name" style="font-size:.8rem;margin-top:8px"></p>
                </div>
            </div>
            <div class="priority-tier">
                <i data-lucide="shield"></i>
                Priority: <?= sanitize($priorityLabels[$user['role']] ?? 'Tier 1 — Student') ?>
            </div>
            <div style="margin-top:20px;display:flex;gap:12px">
                <button type="button" class="btn btn-secondary" onclick="goToStep(2)">Back</button>
                <button type="submit" class="btn btn-primary"><i data-lucide="send"></i> Submit Booking</button>
            </div>
        </form>
    </div>
</div>

<script>
let selectedRoom = null;
let searchParams = {};

document.getElementById('booking-date').min = new Date().toISOString().split('T')[0];
document.getElementById('booking-date').value = new Date().toISOString().split('T')[0];

['capacity-min', 'capacity-max'].forEach(id => {
    document.getElementById(id).addEventListener('input', () => {
        document.getElementById('cap-min').textContent = document.getElementById('capacity-min').value;
        document.getElementById('cap-max').textContent = document.getElementById('capacity-max').value;
    });
});

function goToStep(n) {
    document.querySelectorAll('.wizard-step').forEach((s, i) => {
        s.classList.toggle('active', i + 1 === n);
        s.classList.toggle('done', i + 1 < n);
    });
    document.querySelectorAll('.wizard-panel').forEach((p, i) => p.classList.toggle('active', i + 1 === n));
}

document.getElementById('search-rooms-btn').addEventListener('click', async () => {
    const date = document.getElementById('booking-date').value;
    const startTime = document.getElementById('start-time').value;
    const endTime = document.getElementById('end-time').value;
    if (!date || !startTime || !endTime) { alert('Please fill all fields'); return; }

    searchParams = { date, startTime, endTime };
    const params = new URLSearchParams({
        action: 'search', date, start_time: startTime, end_time: endTime,
        min_capacity: document.getElementById('capacity-min').value,
        max_capacity: document.getElementById('capacity-max').value
    });

    try {
        const data = await ClassReserve.api('/api/bookings.php?' + params);
        const grid = document.getElementById('room-results');
        if (data.rooms.length === 0) {
            grid.innerHTML = '<div class="empty-state"><i data-lucide="door-closed"></i><p>No rooms available for these criteria</p></div>';
        } else {
            grid.innerHTML = data.rooms.map(r => `
                <div class="room-card" data-id="${r.id}" data-name="${r.name}">
                    <span class="room-type-badge">${r.type}</span>
                    <div class="room-name">${r.name}</div>
                    <div class="room-meta">${r.building} · Floor ${r.floor}</div>
                    <div class="room-capacity"><i data-lucide="users"></i> ${r.capacity} seats</div>
                </div>
            `).join('');
            lucide.createIcons();
            grid.querySelectorAll('.room-card').forEach(card => {
                card.addEventListener('click', () => {
                    grid.querySelectorAll('.room-card').forEach(c => c.classList.remove('selected'));
                    card.classList.add('selected');
                    selectedRoom = { id: card.dataset.id, name: card.dataset.name };
                    document.getElementById('select-room-btn').disabled = false;
                });
            });
        }
        goToStep(2);
    } catch (err) { alert(err.message); }
});

document.getElementById('select-room-btn').addEventListener('click', () => {
    if (selectedRoom) goToStep(3);
});

const dropZone = document.getElementById('drop-zone');
const fileInput = document.getElementById('file-input');
dropZone.addEventListener('click', () => fileInput.click());
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    fileInput.files = e.dataTransfer.files;
    document.getElementById('file-name').textContent = e.dataTransfer.files[0]?.name || '';
});
fileInput.addEventListener('change', () => {
    document.getElementById('file-name').textContent = fileInput.files[0]?.name || '';
});

document.getElementById('booking-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('title', document.getElementById('booking-title').value);
    fd.append('description', document.getElementById('booking-desc').value);
    fd.append('room_id', selectedRoom.id);
    fd.append('start_datetime', `${searchParams.date} ${searchParams.startTime}:00`);
    fd.append('end_datetime', `${searchParams.date} ${searchParams.endTime}:00`);
    fd.append('csrf_token', ClassReserve.csrfToken);
    if (fileInput.files[0]) fd.append('attachment', fileInput.files[0]);

    try {
        const res = await fetch('/api/bookings.php?action=create', {
            method: 'POST',
            headers: { 'X-CSRF-Token': ClassReserve.csrfToken },
            body: fd
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error);
        const q = new URLSearchParams({
            id: data.booking_id,
            title: document.getElementById('booking-title').value,
            room: selectedRoom.name,
            date: searchParams.date,
            start: searchParams.startTime,
            end: searchParams.endTime,
            code: data.checkin_code
        });
        window.location.href = '/public/booking-confirmation.php?' + q;
    } catch (err) { alert(err.message); }
});
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/includes/layout.php';
