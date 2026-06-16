<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/faculty_helpers.php';

requireFacultyUser();
header('Location: /faculty/rooms.php?tab=suggestions');
exit;
