<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$pageTitle = 'New Booking';
$user = currentUser();
$isFaculty = ($user['role'] ?? '') === 'faculty';
$facultyClassTypes = [
    'Regular Class',
    'Extra Class',
    'Makeup Class',
    'Lab Class',
    'Exam',
    'Quiz',
    'Presentation',
    'Viva',
    'Seminar',
    'Workshop',
    'Faculty Meeting',
    'Department Meeting',
    'Consultation Hour',
    'Other',
];
$priorityLabels = ['student' => 'Tier 1 — Student', 'club' => 'Tier 2 — Club', 'faculty' => 'Tier 3 — Faculty'];
ob_start();
?>
<div class="page-header">
    <h1><?= $isFaculty ? 'Faculty Booking' : 'New Booking' ?></h1>
    <p><?= $isFaculty ? 'Reserve an available room for an academic or department activity' : 'Find a room and submit your reservation request' ?></p>
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
            <div class="form-group">
                <label for="room-building">Building</label>
                <select id="room-building" class="form-select">
                    <option value="">All Buildings</option>
                    <option value="Science Block">Science Block</option>
                    <option value="Arts Block">Arts Block</option>
                    <option value="Engineering Block">Engineering Block</option>
                    <option value="Library Annex">Library Annex</option>
                </select>
            </div>
            <div class="form-group">
                <label for="room-type">Room Type</label>
                <select id="room-type" class="form-select">
                    <option value="">All Types</option>
                    <option value="Lecture">Lecture</option>
                    <option value="Lab">Lab</option>
                    <option value="Seminar">Seminar</option>
                    <option value="Auditorium">Auditorium</option>
                </select>
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
            <div id="booking-error" class="alert alert-error hidden"></div>
            <div id="selected-room-summary" class="alert alert-info hidden"></div>
            <div class="form-group">
                <label for="booking-title">Event Title</label>
                <input type="text" id="booking-title" class="form-input" required placeholder="<?= $isFaculty ? 'e.g. CSE 220 Makeup Class' : 'e.g. Study Group Session' ?>">
            </div>
            <?php if ($isFaculty): ?>
            <div class="grid gap-4" style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group">
                    <label for="course-code">Course Code</label>
                    <input type="text" id="course-code" class="form-input" required placeholder="e.g. CSE 220">
                </div>
                <div class="form-group">
                    <label for="section">Section</label>
                    <input type="text" id="section" class="form-input" required placeholder="e.g. A">
                </div>
                <div class="form-group">
                    <label for="batch">Batch</label>
                    <input type="text" id="batch" class="form-input" required placeholder="e.g. 57">
                </div>
                <div class="form-group">
                    <label for="department">Department</label>
                    <input type="text" id="department" class="form-input" required placeholder="e.g. CSE">
                </div>
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label for="booking-purpose"><?= $isFaculty ? 'Class Type' : 'Booking Purpose / Type' ?></label>
                <select id="booking-purpose" class="form-select" required>
                    <?php if ($user['role'] === 'faculty'): ?>
                        <option value="">Select class type</option>
                        <?php foreach ($facultyClassTypes as $classType): ?>
                            <option value="<?= sanitize($classType) ?>"><?= sanitize($classType) ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="Study Session">Study Session</option>
                        <option value="Club Event">Club Event</option>
                        <option value="Group Project">Group Project</option>
                        <option value="Other">Other</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="booking-desc">Description</label>
                <textarea id="booking-desc" class="form-textarea" <?= $isFaculty ? 'required' : '' ?> placeholder="<?= $isFaculty ? 'Add course, department, or agenda details' : 'Describe your event...' ?>"></textarea>
            </div>
            <div class="form-group">
                <label for="attendees">Expected Attendees</label>
                <input type="number" id="attendees" class="form-input" min="1" value="10" required>
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
const isFacultyBooking = <?= json_encode($isFaculty) ?>;

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

function getBookingDateTime(date, time) {
    return new Date(`${date}T${time}`);
}

function showBookingError(message) {
    const error = document.getElementById('booking-error');
    error.textContent = message;
    error.classList.remove('hidden');
}

function clearBookingError() {
    const error = document.getElementById('booking-error');
    error.textContent = '';
    error.classList.add('hidden');
}

function updateSelectedRoomSummary() {
    const summary = document.getElementById('selected-room-summary');
    const attendees = document.getElementById('attendees');

    if (!selectedRoom) {
        summary.classList.add('hidden');
        summary.textContent = '';
        attendees.removeAttribute('max');
        return;
    }

    attendees.max = selectedRoom.capacity;
    summary.textContent = `Selected room: ${selectedRoom.name}. Capacity: ${selectedRoom.capacity} participants.`;
    summary.classList.remove('hidden');
}

document.getElementById('search-rooms-btn').addEventListener('click', async () => {
    const date = document.getElementById('booking-date').value;
    const startTime = document.getElementById('start-time').value;
    const endTime = document.getElementById('end-time').value;
    if (!date || !startTime || !endTime) { alert('Date, start time, and end time are required.'); return; }
    if (getBookingDateTime(date, endTime) <= getBookingDateTime(date, startTime)) {
        alert('Invalid time');
        return;
    }

    const building = document.getElementById('room-building').value;
    const roomType = document.getElementById('room-type').value;
    searchParams = { date, startTime, endTime };
    selectedRoom = null;
    document.getElementById('select-room-btn').disabled = true;
    updateSelectedRoomSummary();
    const params = new URLSearchParams({
        action: 'search', date, start_time: startTime, end_time: endTime,
        min_capacity: document.getElementById('capacity-min').value,
        max_capacity: document.getElementById('capacity-max').value,
        building: building,
        type: roomType
    });

    try {
        const data = await ClassReserve.api('/api/bookings.php?' + params);
        const grid = document.getElementById('room-results');
        if (data.rooms.length === 0) {
            grid.innerHTML = '<div class="empty-state"><i data-lucide="door-closed"></i><p>No rooms available for these criteria</p></div>';
        } else {
            grid.innerHTML = data.rooms.map(r => `
                <div class="room-card" data-id="${r.id}" data-name="${ClassReserve.escapeHtml(r.name)}" data-capacity="${Number(r.capacity || 0)}">
                    <span class="room-type-badge">${ClassReserve.escapeHtml(r.type || 'Room')}</span>
                    <div class="room-name">${ClassReserve.escapeHtml(r.name)}</div>
                    <div class="room-meta">${ClassReserve.escapeHtml(r.building || '')} - Floor ${ClassReserve.escapeHtml(r.floor || '')}</div>
                    <div class="room-capacity"><i data-lucide="users"></i> ${Number(r.capacity || 0)} seats</div>
                </div>
            `).join('');
            lucide.createIcons();
            grid.querySelectorAll('.room-card').forEach(card => {
                card.addEventListener('click', () => {
                    grid.querySelectorAll('.room-card').forEach(c => c.classList.remove('selected'));
                    card.classList.add('selected');
                    selectedRoom = {
                        id: card.dataset.id,
                        name: card.dataset.name,
                        capacity: Number(card.dataset.capacity || 0)
                    };
                    document.getElementById('select-room-btn').disabled = false;
                    updateSelectedRoomSummary();
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
    clearBookingError();

    const title = document.getElementById('booking-title').value.trim();
    const description = document.getElementById('booking-desc').value.trim();
    const purpose = document.getElementById('booking-purpose').value.trim();
    const attendees = Number(document.getElementById('attendees').value);
    const academicDetails = isFacultyBooking ? {
        courseCode: document.getElementById('course-code').value.trim(),
        section: document.getElementById('section').value.trim(),
        batch: document.getElementById('batch').value.trim(),
        department: document.getElementById('department').value.trim()
    } : {};

    if (!selectedRoom) {
        showBookingError('Room selection is required.');
        goToStep(2);
        return;
    }

    if (!searchParams.date || !searchParams.startTime || !searchParams.endTime) {
        showBookingError('Date, start time, and end time are required.');
        goToStep(1);
        return;
    }

    if (getBookingDateTime(searchParams.date, searchParams.endTime) <= getBookingDateTime(searchParams.date, searchParams.startTime)) {
        showBookingError('Invalid time');
        return;
    }

    if (!title || !purpose || (isFacultyBooking && !description)) {
        showBookingError('Please complete all required fields.');
        return;
    }

    if (isFacultyBooking && (!academicDetails.courseCode || !academicDetails.section || !academicDetails.batch || !academicDetails.department)) {
        showBookingError('Course code, section, batch, and department are required.');
        return;
    }

    if (!Number.isFinite(attendees) || attendees <= 0) {
        showBookingError('Expected participants must be positive.');
        return;
    }

    if (attendees > selectedRoom.capacity) {
        showBookingError('Capacity not enough');
        return;
    }

    const fd = new FormData();
    fd.append('title', title);
    fd.append('description', description);
    fd.append('room_id', selectedRoom.id);
    fd.append('start_datetime', `${searchParams.date} ${searchParams.startTime}:00`);
    fd.append('end_datetime', `${searchParams.date} ${searchParams.endTime}:00`);
    fd.append('attendees', String(attendees));
    fd.append('purpose', purpose);
    if (isFacultyBooking) {
        fd.append('course_code', academicDetails.courseCode);
        fd.append('section', academicDetails.section);
        fd.append('batch', academicDetails.batch);
        fd.append('department', academicDetails.department);
    }
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
            title: title,
            room: selectedRoom.name,
            date: searchParams.date,
            start: searchParams.startTime,
            end: searchParams.endTime,
            code: data.checkin_code
        });
        window.location.href = '/public/booking-confirmation.php?' + q;
    } catch (err) { showBookingError(err.message); }
});
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/includes/layout.php';
