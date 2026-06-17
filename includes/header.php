<?php
require_once __DIR__ . '/auth.php';
$user = currentUser();
$pageTitle = $pageTitle ?? 'Classroom Availability';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?></title>
<<<<<<< HEAD
    <script>
        (() => {
            try {
                const storedTheme = localStorage.getItem('classreserve.theme');
                const theme = storedTheme === 'light' || storedTheme === 'dark' ? storedTheme : 'dark';
                document.documentElement.dataset.theme = theme;
                document.documentElement.style.colorScheme = theme;
            } catch {
                document.documentElement.dataset.theme = 'dark';
            }
        })();
    </script>
=======
>>>>>>> origin/Riche01
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <a href="/index.php">Classroom Availability</a>
        </div>
        <?php if ($user): ?>
        <ul class="nav-links">
            <?php if (in_array($user['role'], ['student', 'club'], true)): ?>
                <li><a href="/student/dashboard.php">Dashboard</a></li>
                <li><a href="/student/search.php">Search Rooms</a></li>
                <li><a href="/student/bookings.php">My Bookings</a></li>
            <?php elseif ($user['role'] === 'faculty'): ?>
                <li><a href="/faculty/dashboard.php">Dashboard</a></li>
                <li><a href="/faculty/reserve.php">Reserve Room</a></li>
                <li><a href="/faculty/approvals.php">Approvals</a></li>
            <?php elseif ($user['role'] === 'admin'): ?>
                <li><a href="/admin/dashboard.php">Dashboard</a></li>
                <li><a href="/admin/bookings.php">All Bookings</a></li>
                <li><a href="/admin/rooms.php">Rooms</a></li>
                <li><a href="/admin/maintenance.php">Maintenance</a></li>
            <?php endif; ?>
            <li><a href="/calendar.php">Calendar</a></li>
<<<<<<< HEAD
            <li>
                <button class="theme-switch" type="button" data-theme-toggle aria-label="Switch theme" aria-live="polite">
                    <span class="theme-switch-track" aria-hidden="true"><span class="theme-switch-thumb"></span></span>
                    <span class="theme-switch-label">Light</span>
                </button>
            </li>
=======
>>>>>>> origin/Riche01
            <li class="nav-user">
                <span><?= sanitize($user['name']) ?> (<?= sanitize(roleLabel($user['role'])) ?>)</span>
                <a href="/logout.php" class="btn btn-sm btn-outline">Logout</a>
            </li>
        </ul>
        <?php endif; ?>
    </nav>
    <main class="container">
        <?php if ($msg = flash('success')): ?>
            <div class="alert alert-success"><?= sanitize($msg) ?></div>
        <?php endif; ?>
        <?php if ($msg = flash('error')): ?>
            <div class="alert alert-error"><?= sanitize($msg) ?></div>
        <?php endif; ?>
