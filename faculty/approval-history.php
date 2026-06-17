<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/faculty_helpers.php';

requireFacultyUser();
header('Location: /faculty/approvals.php?tab=history');
exit;
