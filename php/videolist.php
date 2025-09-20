<?php
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

// --- Display Results ---
// Loop through the videos and display them in a table
foreach ($videos as $video) {
    // Sanitize all output to prevent XSS
    $videoID_safe = htmlspecialchars($video['VIDEOID'], ENT_QUOTES, 'UTF-8');
    $title_safe = htmlspecialchars($video['TITLE'], ENT_QUOTES, 'UTF-8');
    $time_safe = htmlspecialchars($video['TIME'], ENT_QUOTES, 'UTF-8');
    $num_safe = htmlspecialchars($video['NUM'], ENT_QUOTES, 'UTF-8');

    // Properly encode URL parameters
    $video_url = '../php/video.php?videoID=' . urlencode($video['VIDEOID']);
    $youtube_url = 'https://www.youtube.com/watch?v=' . urlencode($video['VIDEOID']);
    $thumbnail_url = 'https://img.youtube.com/vi/' . urlencode($video['VIDEOID']) . '/default.jpg';

    echo "<tr>";
    echo "<td><a href='{$video_url}'><img style='vertical-align:middle' src='{$thumbnail_url}' alt='Thumbnail' title='{$title_safe}' height='50'></a></td>";
    echo "<td style='padding-left: 0.75rem; text-align: left;'>";
    echo "<span class='truncate' title='{$title_safe}'>";
    echo "<a href='{$video_url}' target='_self'>{$title_safe}</a>";
    echo "</span></td>";
    echo "<td style='text-align: center'>{$time_safe}</td>";
    echo "<td style='text-align: center'>{$num_safe}</td>";
    echo "<td style='text-align: center'><a href='{$youtube_url}' class='video-link' target='_blank' rel='noopener'>Link</a></td>";
    echo "</tr>";
}

// Close the connection
$pdo = null;
?>