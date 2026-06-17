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
                <li><a href="/public/dashboard.php">Dashboard</a></li>
                <li><a href="/student/search.php">Search Rooms</a></li>
                <li><a href="/student/bookings.php">My Bookings</a></li>
            <?php elseif ($user['role'] === 'faculty'): ?>
                <li><a href="/public/dashboard.php">Dashboard</a></li>
                <li><a href="/faculty/reserve.php">Reserve Room</a></li>
                <li><a href="/faculty/approvals.php">Approvals</a></li>
            <?php elseif ($user['role'] === 'admin'): ?>
                <li><a href="/admin/dashboard.php">Dashboard</a></li>
                <li><a href="/admin/bookings.php">All Bookings</a></li>
                <li><a href="/admin/rooms.php">Rooms</a></li>
                <li><a href="/admin/maintenance.php">Maintenance</a></li>
            <?php endif; ?>
            <li><a href="/calendar.php">Calendar</a></li>
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
