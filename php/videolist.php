<?php
// Get our database config and connect to the database
$config = require 'db.php';
$dsn = "mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$pdo = new PDO($dsn, $config['user'], $config['pass'], $options);

// Query the karaokes table for all videos, ordered by date aired descending
$sql_query = "SELECT * FROM karaokes ORDER BY `TIME` DESC";
$videos = [];
try {
    $stmt = $pdo->query($sql_query);                 // run the query
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {   // fetch as associative array
        $videos[] = $row;
    }
} catch (PDOException $e) {
    echo "❌ Query failed: " . $e->getMessage();
}

// Get video information for each video
foreach ($videos as &$video) {
    $table = preg_replace('/[^a-zA-Z0-9_-]/', '', $video['VIDEOID']);
    $sql_query = "SELECT * FROM videos WHERE VIDEOID = :videoid LIMIT 1";
    try {
        $stmt = $pdo->prepare($sql_query);
        $stmt->execute(['videoid' => $video['VIDEOID']]);
        $video_info = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($video_info) {
            $video['TITLE'] = $video_info['TITLE'];
        }

        // Format the date aired to YYYY-MM-DD
        $date = DateTime::createFromFormat('Y-m-d H:i:s', $video['TIME']);
        if ($date) {
            $video['TIME'] = $date->format('Y-m-d');
        } else {
            $video['TIME'] = 'Unknown';
        }

        // Display our results
        echo "<tr>";
        echo "<td><a href='../php/video.php?videoID={$video['VIDEOID']}'><img style= 'vertical-align:middle' src='https://img.youtube.com/vi/{$video['VIDEOID']}/default.jpg' alt='Thumbnail' title='{$video['TITLE']}' height='50'></a></td>";
        echo "<td style='padding-left: 0.75rem; text-align: left;'>";
        echo "<span class='truncate' title='{$video['TITLE']}'>";
        echo "<a href='../php/video.php?videoID={$video['VIDEOID']}' target='_self'>{$video['TITLE']}</a>";
        echo "</span></td>";
        echo "<td style='text-align: center'>{$video['TIME']}</td>";
        echo "<td style='text-align: center'>{$video['NUM']}</td>";
        echo "<td style='text-align: center'><a href='https://www.youtube.com/watch?v={$video['VIDEOID']}' class='video-link' target='_blank' rel='noopener'>Link</a></td>";
        echo "</tr>";

    } catch (PDOException $e) {
        echo "❌ Query failed: " . $e->getMessage();
    }
}

// Close the connection
$pdo = null;
?>