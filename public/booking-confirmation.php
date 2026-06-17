<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$pageTitle = 'Booking Confirmed';
ob_start();
?>
<div class="confirmation-page">
    <div class="confirmation-card">
        <div class="success-check"><i data-lucide="check"></i></div>
        <h1 style="font-size:1.8rem;margin-bottom:8px">Booking Submitted!</h1>
        <p style="color:var(--cr-slate);margin-bottom:0">Your reservation request has been received and is pending approval.</p>

        <div class="receipt-card">
            <div class="receipt-row">
                <span class="receipt-label">Event</span>
                <span class="receipt-value" id="rc-title"><?= sanitize($_GET['title'] ?? '—') ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Room</span>
                <span class="receipt-value" id="rc-room"><?= sanitize($_GET['room'] ?? '—') ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Date</span>
                <span class="receipt-value" id="rc-date"><?= sanitize($_GET['date'] ?? '—') ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Time</span>
                <span class="receipt-value"><?= sanitize(($_GET['start'] ?? '') . ' — ' . ($_GET['end'] ?? '')) ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Check-in Code</span>
                <span class="receipt-value" style="font-family:monospace;letter-spacing:2px"><?= sanitize($_GET['code'] ?? '—') ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Status</span>
                <span class="receipt-value"><span class="status-pill status-pending">Pending</span></span>
            </div>
        </div>

        <div style="margin-top:24px;display:flex;gap:12px;justify-content:center">
            <a href="/public/dashboard.php" class="btn btn-primary"><i data-lucide="layout-dashboard"></i> Dashboard</a>
            <a href="/public/calendar.php" class="btn btn-secondary"><i data-lucide="calendar"></i> View Calendar</a>
            <a href="/public/new-booking.php" class="btn btn-secondary"><i data-lucide="plus"></i> New Booking</a>
        </div>
    </div>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/includes/layout.php';
