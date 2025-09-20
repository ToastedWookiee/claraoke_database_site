<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

// Get our database config and connect to the database
$config = require 'db.php';
$dsn = "mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$pdo = new PDO($dsn, $config['user'], $config['pass'], $options);

// Always fetch in UTC
$pdo->exec("SET time_zone = '+00:00'");
$stmt = $pdo->query("SELECT last_update FROM db_updates ORDER BY last_update DESC LIMIT 1");
$lastUpdate = $stmt->fetchColumn();

echo json_encode(['last_update' => $lastUpdate]); // e.g. "2025-07-01 13:23:28"

// Close the connection
$pdo = null;
?>