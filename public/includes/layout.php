<?php
/** @var string $pageTitle */
/** @var string $pageContent */
/** @var bool $hideNav */

require_once __DIR__ . '/bootstrap.php';

$user = currentUser();
$hideNav = $hideNav ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle ?? 'ClassReserve') ?> — ClassReserve</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/assets/css/tailwind.css">
    <link rel="stylesheet" href="/public/assets/css/styles.css">
    <meta name="csrf-token" content="<?= sanitize(getCsrfToken()) ?>">
</head>
<body class="<?= $hideNav ? 'no-nav' : 'has-nav' ?>">
<?php if (!$hideNav && $user): ?>
<aside id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <div class="brand-mark">
            <i data-lucide="school"></i>
        </div>
        <div class="brand-text">
            <span class="brand-name">ClassReserve</span>
            <span class="brand-portal"><?= sanitize(rolePortal($user['role'])) ?></span>
        </div>
        <button id="sidebar-toggle" class="sidebar-toggle" aria-label="Toggle sidebar">
            <i data-lucide="panel-left-close"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <?php foreach (navItems($user['role']) as $item): ?>
            <?php $active = str_contains($_SERVER['REQUEST_URI'], basename($item['href'])) ? 'active' : ''; ?>
            <a href="<?= $item['href'] ?>" class="nav-item <?= $active ?>">
                <i data-lucide="<?= $item['icon'] ?>"></i>
                <span><?= sanitize($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="#" id="logout-btn" class="nav-item logout">
            <i data-lucide="log-out"></i>
            <span>Sign Out</span>
        </a>
    </div>
</aside>
<?php endif; ?>

<main class="main-content">
    <?php if (!$hideNav && $user): ?>
        <div class="topbar">
            <div class="notification-menu">
                <button id="notification-toggle" class="notification-toggle" type="button" aria-label="Open notifications" aria-expanded="false">
                    <i data-lucide="bell"></i>
                    <span id="notification-badge" class="notification-badge hidden">0</span>
                </button>
                <div id="notification-panel" class="notification-panel" aria-hidden="true">
                    <div class="notification-panel-header">
                        <div>
                            <h2>Notifications</h2>
                            <p>Booking and account updates</p>
                        </div>
                        <button id="notification-mark-read" class="notification-link" type="button">Mark all read</button>
                    </div>
                    <div id="notification-list" class="notification-list">
                        <div class="notification-empty">Loading notifications...</div>
                    </div>
                </div>
            </div>
            <div class="user-chip topbar-user-chip">
                <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                <div class="user-info">
                    <span class="user-name"><?= sanitize($user['name']) ?></span>
                    <span class="role-badge role-<?= sanitize($user['role']) ?>"><?= sanitize(roleLabel($user['role'])) ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?= $pageContent ?? '' ?>
</main>

<script src="/public/assets/js/app.js"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>
</body>
</html>
