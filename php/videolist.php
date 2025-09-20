<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://clara.acormiz.com');
header('Access-Control-Allow-Headers: Content-Type');

// Get our database config and connect to the database
$config = require 'db.php';
$dsn = "mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$pdo = new PDO($dsn, $config['user'], $config['pass'], $options);

// Query the karaokes and videos tables to get all video information in one go
$sql_query = "
    SELECT
        k.VIDEOID,
        k.TIME,
        k.NUM,
        v.TITLE
    FROM
        karaokes k
    LEFT JOIN
        videos v ON k.VIDEOID = v.VIDEOID
    ORDER BY
        k.TIME DESC
";
$videos = [];
try {
    $stmt = $pdo->query($sql_query);
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "❌ Query failed: " . $e->getMessage();
    // In a real app, you'd want to log this error.
    // For now, we'll just show the error and stop.
    exit;
}

// Format the date for each video
foreach ($videos as &$video) {
    // Set a default title if it's NULL (from the LEFT JOIN)
    $video['TITLE'] = $video['TITLE'] ?? 'Unknown Title';

    // Format the date aired to YYYY-MM-DD
    $date = DateTime::createFromFormat('Y-m-d H:i:s', $video['TIME']);
    if ($date) {
        $video['TIME'] = $date->format('Y-m-d');
    } else {
        $video['TIME'] = 'Unknown';
    }
}
unset($video); // break the reference with the last element

// --- Output JSON ---
echo json_encode([
    'count' => count($videos),
    'items' => $videos,
]);

// Close the connection
$pdo = null;
?>