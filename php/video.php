<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://clara.acormiz.com');
header('Access-Control-Allow-Headers: Content-Type');

// Get videoID from request
$videoID = $_REQUEST['videoID'] ?? '';
if ($videoID === '') {
    echo json_encode(['error' => 'No videoID provided']);
    exit;
}

try {
    $pdo = require 'db.php';

    // --- SQL INJECTION MITIGATION ---
    // Get the list of all tables in the database
    $stmt = $pdo->query("SHOW TABLES");
    $all_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get the list of all valid video table names from the karaokes table
    $stmt = $pdo->query("SELECT VIDEOID FROM karaokes");
    $videoIDs_from_karaokes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // The valid video tables are the intersection of the actual tables and the video IDs from the karaokes table
    $valid_video_tables = array_intersect($videoIDs_from_karaokes, $all_tables);

    // Check if the requested videoID is a valid table
    if (!in_array($videoID, $valid_video_tables)) {
        echo json_encode(['error' => 'Invalid videoID']);
        exit;
    }
    // --- END SQL INJECTION MITIGATION ---

    // Get all of our relevent information for this videoID
    $table = preg_replace('/[^a-zA-Z0-9_-]/', '', $videoID);

    // First information from the videos table
    $sql_query = "SELECT * FROM videos WHERE VIDEOID = :videoid LIMIT 1";
    $stmt = $pdo->prepare($sql_query);
    $stmt->execute(['videoid' => $videoID]);
    $video_info = $stmt->fetch(PDO::FETCH_ASSOC);

    // Next information from the karaokes table
    $sql_query = "SELECT * FROM karaokes WHERE VIDEOID = :videoid LIMIT 1";
    $stmt = $pdo->prepare($sql_query);
    $stmt->execute(['videoid' => $videoID]);
    $karaoke_info = $stmt->fetch(PDO::FETCH_ASSOC);

    // Last get the song list from its own table
    $songs = [];
    $sql_query = "SELECT * FROM `$table`";
    $stmt = $pdo->query($sql_query);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $songs[] = $row;
    }

    // Format our date to YYYY-MM-DD
    $date_aired = 'Unknown';
    if (isset($karaoke_info['TIME'])) {
        $date = DateTime::createFromFormat('Y-m-d H:i:s', $karaoke_info['TIME']);
        if ($date) {
            $date_aired = $date->format('Y-m-d');
        }
    }

    // Combine all data into a single array
    $response_data = [
        'video_info' => $video_info,
        'karaoke_info' => $karaoke_info,
        'songs' => $songs,
        'date_aired' => $date_aired,
    ];

    // Output the data as JSON
    echo json_encode($response_data);

} catch (PDOException $e) {
    // Return a JSON error message
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// Close the connection
$pdo = null;
?>