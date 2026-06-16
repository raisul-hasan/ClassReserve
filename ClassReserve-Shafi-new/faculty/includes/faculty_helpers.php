<?php

declare(strict_types=1);

require_once __DIR__ . '/../../public/includes/bootstrap.php';

function requireFacultyUser(): array
{
    return requireUserRole(['faculty']);
}

function facultyIdentifier(string $name): string
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function facultyTableExists(PDO $db, string $table): bool
{
    static $cache = [];
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }

    try {
        $stmt = $db->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$table]);
        $cache[$table] = (bool) $stmt->fetchColumn();
    } catch (Throwable) {
        $cache[$table] = false;
    }

    return $cache[$table];
}

function facultyColumnNames(PDO $db, string $table): array
{
    static $cache = [];
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }

    $columns = [];
    try {
        foreach ($db->query('SHOW COLUMNS FROM ' . facultyIdentifier($table)) as $column) {
            $columns[$column['Field']] = true;
        }
    } catch (Throwable) {
        $columns = [];
    }

    $cache[$table] = $columns;
    return $columns;
}

function facultyHasColumn(PDO $db, string $table, string $column): bool
{
    $columns = facultyColumnNames($db, $table);
    return !empty($columns[$column]);
}

function facultyBookingTimeColumns(PDO $db): array
{
    if (facultyHasColumn($db, 'bookings', 'start_datetime') && facultyHasColumn($db, 'bookings', 'end_datetime')) {
        return ['start_datetime', 'end_datetime'];
    }

    return ['start_time', 'end_time'];
}

function facultyMaintenanceSource(PDO $db): ?array
{
    if (facultyTableExists($db, 'maintenance') && facultyHasColumn($db, 'maintenance', 'start_datetime')) {
        return ['maintenance', 'start_datetime', 'end_datetime'];
    }

    if (facultyTableExists($db, 'maintenance_blocks') && facultyHasColumn($db, 'maintenance_blocks', 'start_time')) {
        return ['maintenance_blocks', 'start_time', 'end_time'];
    }

    return null;
}

function facultyNormalizeWindow(string $date, string $startTime, string $endTime): ?array
{
    if ($date === '' || $startTime === '' || $endTime === '') {
        return null;
    }

    $startTimestamp = strtotime($date . ' ' . $startTime);
    $endTimestamp = strtotime($date . ' ' . $endTime);
    if ($startTimestamp === false || $endTimestamp === false || $endTimestamp <= $startTimestamp) {
        return null;
    }

    return [
        date('Y-m-d H:i:s', $startTimestamp),
        date('Y-m-d H:i:s', $endTimestamp),
    ];
}

function facultyDistinctRoomValues(PDO $db, string $column): array
{
    if (!facultyHasColumn($db, 'rooms', $column)) {
        return [];
    }

    $quoted = facultyIdentifier($column);
    $stmt = $db->query("SELECT DISTINCT $quoted AS value FROM rooms WHERE $quoted IS NOT NULL AND $quoted <> '' ORDER BY $quoted");
    return array_column($stmt->fetchAll(), 'value');
}

function facultyAvailableRooms(PDO $db, string $start, string $end, array $filters = []): array
{
    [$bookingStart, $bookingEnd] = facultyBookingTimeColumns($db);

    $sql = 'SELECT r.* FROM rooms r WHERE 1=1';
    $params = [];

    if (facultyHasColumn($db, 'rooms', 'status')) {
        $sql .= " AND r.status = 'available'";
    }

    $minCapacity = max(0, (int) ($filters['min_capacity'] ?? 0));
    $maxCapacity = (int) ($filters['max_capacity'] ?? 0);
    if ($minCapacity > 0) {
        $sql .= ' AND r.capacity >= ?';
        $params[] = $minCapacity;
    }
    if ($maxCapacity > 0) {
        $sql .= ' AND r.capacity <= ?';
        $params[] = $maxCapacity;
    }

    $building = trim((string) ($filters['building'] ?? ''));
    if ($building !== '' && facultyHasColumn($db, 'rooms', 'building')) {
        $sql .= ' AND r.building = ?';
        $params[] = $building;
    }

    $type = trim((string) ($filters['type'] ?? ''));
    if ($type !== '' && facultyHasColumn($db, 'rooms', 'type')) {
        $sql .= ' AND r.type = ?';
        $params[] = $type;
    }

    $sql .= ' AND r.id NOT IN (
        SELECT room_id FROM bookings
        WHERE status IN (\'pending\', \'approved\')
          AND ' . facultyIdentifier($bookingStart) . ' < ?
          AND ' . facultyIdentifier($bookingEnd) . ' > ?
    )';
    $params[] = $end;
    $params[] = $start;

    $maintenance = facultyMaintenanceSource($db);
    if ($maintenance !== null) {
        [$table, $maintenanceStart, $maintenanceEnd] = $maintenance;
        $sql .= ' AND r.id NOT IN (
            SELECT room_id FROM ' . facultyIdentifier($table) . '
            WHERE ' . facultyIdentifier($maintenanceStart) . ' < ?
              AND ' . facultyIdentifier($maintenanceEnd) . ' > ?
        )';
        $params[] = $end;
        $params[] = $start;
    }

    $sql .= ' ORDER BY r.capacity ASC, r.name ASC';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function facultyRoomSelectFields(PDO $db): string
{
    $fields = ['r.name AS room_name'];
    $fields[] = facultyHasColumn($db, 'rooms', 'building') ? 'r.building' : "'' AS building";
    $fields[] = facultyHasColumn($db, 'rooms', 'floor') ? 'r.floor' : 'NULL AS floor';
    $fields[] = facultyHasColumn($db, 'rooms', 'type') ? 'r.type AS room_type' : "'' AS room_type";
    $fields[] = facultyHasColumn($db, 'rooms', 'capacity') ? 'r.capacity AS room_capacity' : '0 AS room_capacity';

    return implode(', ', $fields);
}

function facultyBookingSelectSql(PDO $db): string
{
    [$startColumn, $endColumn] = facultyBookingTimeColumns($db);

    return 'SELECT b.*, b.' . facultyIdentifier($startColumn) . ' AS start_at, b.' . facultyIdentifier($endColumn) . ' AS end_at, '
        . facultyRoomSelectFields($db)
        . ', u.name AS user_name, u.role AS user_role
        FROM bookings b
        JOIN rooms r ON r.id = b.room_id
        JOIN users u ON u.id = b.user_id';
}

function facultyFetchOwnBookings(PDO $db, int $userId, string $where = '', array $params = [], string $order = 'ASC', ?int $limit = null): array
{
    [$startColumn] = facultyBookingTimeColumns($db);
    $direction = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
    $limitSql = $limit !== null ? ' LIMIT ' . max(1, $limit) : '';

    $sql = facultyBookingSelectSql($db)
        . ' WHERE b.user_id = ? ' . $where
        . ' ORDER BY b.' . facultyIdentifier($startColumn) . ' ' . $direction . $limitSql;

    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge([$userId], $params));

    return $stmt->fetchAll();
}

function facultyStatusPill(string $status): string
{
    $class = match ($status) {
        'approved' => 'status-approved',
        'pending' => 'status-pending',
        'rejected' => 'status-rejected',
        'cancelled' => 'status-cancelled',
        default => '',
    };

    return '<span class="status-pill ' . $class . '">' . sanitize($status) . '</span>';
}

function facultyRoleBadge(string $role): string
{
    return '<span class="role-badge role-' . sanitize($role) . '">' . sanitize(roleLabel($role)) . '</span>';
}

function facultyDateTime(string $value): string
{
    $timestamp = strtotime($value);
    return $timestamp === false ? $value : date('M j, Y g:i A', $timestamp);
}

function facultyDateOnly(string $value): string
{
    $timestamp = strtotime($value);
    return $timestamp === false ? $value : date('M j, Y', $timestamp);
}

function facultyTimeRange(string $start, string $end): string
{
    $startTimestamp = strtotime($start);
    $endTimestamp = strtotime($end);
    if ($startTimestamp === false || $endTimestamp === false) {
        return $start . ' - ' . $end;
    }

    return date('g:i A', $startTimestamp) . ' - ' . date('g:i A', $endTimestamp);
}

function facultyDurationHours(string $start, string $end): float
{
    $startTimestamp = strtotime($start);
    $endTimestamp = strtotime($end);
    if ($startTimestamp === false || $endTimestamp === false || $endTimestamp <= $startTimestamp) {
        return 0.0;
    }

    return round(($endTimestamp - $startTimestamp) / 3600, 2);
}

function facultyBookingType(array $booking): string
{
    return trim((string) ($booking['purpose'] ?? '')) !== '' ? (string) $booking['purpose'] : 'Other';
}
