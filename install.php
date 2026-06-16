<?php
/**
 * ClassReserve — Database Setup
 * Run once: php install.php
 * Or import db/classreserve.sql manually via phpMyAdmin / MySQL CLI.
 */

echo "ClassReserve Installer\n";
echo str_repeat('=', 40) . "\n";

$config = require __DIR__ . '/config/database.php';

try {
    $dsn = "mysql:host={$config['host']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $sql = file_get_contents(__DIR__ . '/db/classreserve.sql');
    $pdo->exec($sql);

    echo "Database 'classreserve' created successfully.\n";
    echo "\nDemo accounts:\n";
    echo "  Admin:   admin@classreserve.local / password\n";
    echo "  Faculty: faculty@classreserve.local / faculty123\n";
    echo "  Club:    club@classreserve.local / student123\n";
    echo "  Student: student@classreserve.local / student123\n";
    echo "\nStart your PHP server and visit /public/login.php\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
