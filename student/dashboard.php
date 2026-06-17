<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireRole(['student', 'club']);
header('Location: /public/dashboard.php');
exit;
