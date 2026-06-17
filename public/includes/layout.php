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
<<<<<<< HEAD
    <link rel="stylesheet" href="/public/assets/css/tailwind.css">
    <link rel="stylesheet" href="/public/assets/css/styles.css">
    <meta name="csrf-token" content="<?= sanitize(getCsrfToken()) ?>">
</head>
<body class="<?= $hideNav ? 'no-nav' : 'has-nav' ?>">
<?php if ($hideNav || !$user): ?>
<button id="theme-toggle" class="theme-toggle theme-toggle-floating" type="button" data-theme-toggle aria-label="Switch theme" aria-live="polite">
    <i data-lucide="sun"></i>
    <span>Light</span>
</button>
<?php endif; ?>
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

    <?php $currentPath = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '', '/') ?: '/'; ?>
    <nav class="sidebar-nav">
        <?php foreach (navItems($user['role']) as $item): ?>
            <?php
                $itemPath = rtrim(parse_url($item['href'], PHP_URL_PATH) ?: $item['href'], '/') ?: '/';
                $matchPaths = array_map(static fn ($path) => rtrim(parse_url($path, PHP_URL_PATH) ?: $path, '/') ?: '/', $item['matches'] ?? []);
                $active = $currentPath === $itemPath || $currentPath === $itemPath . '.php' || in_array($currentPath, $matchPaths, true) ? 'active' : '';
            ?>
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
=======
    <link rel="stylesheet" href="<?= getBaseUrl() ?>/public/assets/css/tailwind.css">
    <link rel="stylesheet" href="<?= getBaseUrl() ?>/public/assets/css/styles.css">
    <meta name="csrf-token" content="<?= sanitize(getCsrfToken()) ?>">
    <meta name="base-url" content="<?= sanitize(getBaseUrl()) ?>">
</head>
<body class="<?= $hideNav ? 'no-nav' : 'has-nav' ?>">
<?php if (!$hideNav && $user): ?>
<div class="app-shell">
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
                <?php 
                $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                $active = (basename($requestPath) === basename($item['href'])) ? 'active' : ''; 
                ?>
                <a href="<?= getBaseUrl() . $item['href'] ?>" class="sidebar-link <?= $active ?>">
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

    <div class="main-content-wrapper">
        <header class="topbar">
            <div class="topbar-search">
                <input type="text" placeholder="Search rooms, bookings, or users..." disabled style="opacity: 0.6; cursor: not-allowed;">
                <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--cr-slate);"></i>
            </div>
            
>>>>>>> origin/Riche01
            <button id="theme-toggle" class="theme-toggle" type="button" data-theme-toggle aria-label="Switch theme" aria-live="polite">
                <i data-lucide="sun"></i>
                <span>Light</span>
            </button>
<<<<<<< HEAD
=======
            
>>>>>>> origin/Riche01
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
<<<<<<< HEAD
=======
            
>>>>>>> origin/Riche01
            <div class="user-chip topbar-user-chip">
                <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                <div class="user-info">
                    <span class="user-name"><?= sanitize($user['name']) ?></span>
                    <span class="role-badge role-<?= sanitize($user['role']) ?>"><?= sanitize(roleLabel($user['role'])) ?></span>
                </div>
            </div>
<<<<<<< HEAD
        </div>
    <?php endif; ?>
    <?= $pageContent ?? '' ?>
</main>

<script src="/public/assets/js/app.js"></script>
=======
        </header>
        
        <main class="main-content">
            <?= $pageContent ?? '' ?>
        </main>
    </div>
</div>
<?php else: ?>
    <?php if ($hideNav || !$user): ?>
    <button id="theme-toggle" class="theme-toggle theme-toggle-floating" type="button" data-theme-toggle aria-label="Switch theme" aria-live="polite">
        <i data-lucide="sun"></i>
        <span>Light</span>
    </button>
    <?php endif; ?>
    <main class="main-content">
        <?= $pageContent ?? '' ?>
    </main>
<?php endif; ?>

<script src="<?= getBaseUrl() ?>/public/assets/js/app.js"></script>
>>>>>>> origin/Riche01
<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>
</body>
</html>
