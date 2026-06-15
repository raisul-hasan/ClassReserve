<?php
// Simple PDO connection helper
require_once __DIR__ . '/../config.php';

if (!empty($_SERVER['HTTP_ORIGIN'])) {
    $allowedOrigins = [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'http://localhost:5174',
        'http://127.0.0.1:5174',
    ];

    if (in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins, true)) {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    }
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Self-healing database migrations
    try {
        // failed_logins table
        $testFailed = $pdo->query("SHOW TABLES LIKE 'failed_logins'")->fetch();
        if (!$testFailed) {
            $pdo->exec("CREATE TABLE `failed_logins` (
              `ip_address` VARCHAR(45) NOT NULL,
              `email` VARCHAR(255) NOT NULL,
              `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              KEY `failed_logins_ip_email_idx` (`ip_address`, `email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }
        
        // bookings checkin columns
        $testBookings = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'checkin_code'")->fetch();
        if (!$testBookings) {
            $pdo->exec("ALTER TABLE bookings 
              ADD COLUMN `checkin_code` VARCHAR(50) DEFAULT NULL,
              ADD COLUMN `checked_in_at` DATETIME DEFAULT NULL,
              ADD COLUMN `no_show` TINYINT(1) NOT NULL DEFAULT 0,
              ADD UNIQUE KEY `checkin_code_idx` (`checkin_code`)");
        }

        // relational equipment tables
        $testEquipment = $pdo->query("SHOW TABLES LIKE 'equipment'")->fetch();
        if (!$testEquipment) {
            $pdo->exec("CREATE TABLE `equipment` (
              `id` INT NOT NULL AUTO_INCREMENT,
              `name` VARCHAR(255) NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

            $pdo->exec("CREATE TABLE `room_equipment` (
              `room_id` INT NOT NULL,
              `equipment_id` INT NOT NULL,
              PRIMARY KEY (`room_id`, `equipment_id`),
              CONSTRAINT `room_equipment_room_fk` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
              CONSTRAINT `room_equipment_equipment_fk` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

            // Seed default equipment
            $defaultEquipment = ['Projector', 'Whiteboard', 'Wi-Fi', 'AC', 'Computer', 'Sound system', 'Smart board', 'Lab computers'];
            $ins = $pdo->prepare("INSERT IGNORE INTO equipment (name) VALUES (?)");
            foreach ($defaultEquipment as $eq) {
                $ins->execute([$eq]);
            }

            // Move data from rooms.equipment if it exists
            $testRoomsEq = $pdo->query("SHOW COLUMNS FROM rooms LIKE 'equipment'")->fetch();
            if ($testRoomsEq) {
                $roomsStmt = $pdo->query("SELECT id, equipment FROM rooms");
                $roomsData = $roomsStmt->fetchAll();
                
                $getEqId = $pdo->prepare("SELECT id FROM equipment WHERE name = ?");
                $insRoomEq = $pdo->prepare("INSERT IGNORE INTO room_equipment (room_id, equipment_id) VALUES (?, ?)");
                
                foreach ($roomsData as $room) {
                    if (!empty($room['equipment'])) {
                        $eqList = explode(',', $room['equipment']);
                        foreach ($eqList as $eqName) {
                           $eqName = trim($eqName);
                           if ($eqName === '') continue;
                           
                           $ins->execute([$eqName]);
                           
                           $getEqId->execute([$eqName]);
                           $eqId = $getEqId->fetchColumn();
                           
                           if ($eqId) {
                               $insRoomEq->execute([$room['id'], $eqId]);
                           }
                        }
                    }
                }
                $pdo->exec("ALTER TABLE rooms DROP COLUMN equipment");
            }
        }
    } catch (Exception $e) {
        error_log("ClassReserve Migration Error: " . $e->getMessage());
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}
