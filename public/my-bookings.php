<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$pageTitle = 'My Bookings';
ob_start();
?>
<div class="page-header">
    <h1>My Bookings</h1>
    <p>Track pending, approved, rejected, and cancelled reservations</p>
</div>

<div class="card">
    <div class="table-toolbar">
        <h3 class="card-title">Booking History</h3>
        <a href="/public/new-booking.php" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> New Booking</a>
    </div>
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Room</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Check-in</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="bookings-body">
                <tr><td colspan="7"><div class="spinner"></div></td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
async function loadMyBookings() {
    const body = document.getElementById('bookings-body');
    const showCodes = localStorage.getItem('classreserve.showCheckinCodes') !== '0';
    try {
        const data = await ClassReserve.api('/api/bookings.php?action=list');
        if (!data.bookings.length) {
            body.innerHTML = '<tr><td colspan="7" class="empty-state">No bookings yet</td></tr>';
            return;
        }

        body.innerHTML = data.bookings.map(booking => `
            <tr id="booking-row-${booking.id}">
                <td>
                    <strong>${ClassReserve.escapeHtml(booking.title || 'Untitled booking')}</strong>
                    ${booking.description ? `<br><span class="muted-text">${ClassReserve.escapeHtml(booking.description)}</span>` : ''}
                </td>
                <td>${ClassReserve.escapeHtml(booking.room_name || '')}</td>
                <td>${ClassReserve.formatDate(booking.start_datetime)}</td>
                <td>${ClassReserve.formatTime(booking.start_datetime)} - ${ClassReserve.formatTime(booking.end_datetime)}</td>
                <td>${ClassReserve.statusBadge(booking.status)}</td>
                <td>${showCodes && booking.checkin_code ? `<code>${ClassReserve.escapeHtml(booking.checkin_code)}</code>` : '<span class="muted-text">Hidden</span>'}</td>
                <td>
                    ${['pending', 'approved'].includes(booking.status)
                        ? `<button class="btn btn-ghost btn-sm cancel-booking" data-id="${booking.id}">Cancel</button>`
                        : '<span class="muted-text">No action</span>'}
                </td>
            </tr>
        `).join('');
    } catch (err) {
        body.innerHTML = `<tr><td colspan="7" class="empty-state">${ClassReserve.escapeHtml(err.message)}</td></tr>`;
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    await loadMyBookings();

    const highlightId = new URLSearchParams(window.location.search).get('highlight');
    if (highlightId) {
        const row = document.getElementById('booking-row-' + highlightId);
        if (row) {
            row.classList.add('row-highlight');
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    document.getElementById('bookings-body').addEventListener('click', async (event) => {
        const button = event.target.closest('.cancel-booking');
        if (!button) return;
        if (!confirm('Cancel this booking?')) return;

        try {
            await ClassReserve.api('/api/bookings.php?action=cancel', {
                method: 'POST',
                body: JSON.stringify({ id: Number(button.dataset.id) })
            });
            await loadMyBookings();
            await ClassReserve.loadNotifications();
        } catch (err) {
            alert(err.message);
        }
    });
});
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/includes/layout.php';
