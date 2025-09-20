<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

// Connect to the database
$config = require 'db.php';
$dsn = "mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4";
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_EMULATE_PREPARES => false,
];
$pdo = new PDO($dsn, $config['user'], $config['pass'], $options);

// Get the search query from the request, check if we have a POST, else fallback to GET
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$query = '';

if (isset($data['q'])) {
  $query = trim($data['q']);
} else {
  $query = trim($_GET['q'] ?? '');
}

$results = [];

if ($query !== '') {
  $keywords = preg_split('/\s+/', $query);
  $conds = [];
  $params = [];

  foreach ($keywords as $word) {
    $conds[] = "(title LIKE ? OR artist LIKE ?)";
    $params[] = "%$word%";
    $params[] = "%$word%";
  }

  // Get list of all videoIDs from the karaokes table
  $videoIDs = [];
  $sql_query = "SELECT VIDEOID FROM karaokes";
  try {
    $stmt = $pdo->query($sql_query);                 // run the query
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {   // fetch as associative array
      $videoIDs[] = $row['VIDEOID'];
    }
  } catch (PDOException $e) {
    echo "❌ Query failed: " . $e->getMessage();
    exit;
  }
  $videoIDs = array_unique($videoIDs);

  // Loop through each videoID and search its table for matching songs or artists
  foreach ($videoIDs as $videoID) {
    $table = preg_replace('/[^a-zA-Z0-9_-]/', '', $videoID);
    $sql_query = "SELECT * FROM `$table` WHERE ";
    $sql_query .= implode(' AND ', $conds);
    try {
      $stmt = $pdo->prepare($sql_query);
      $stmt->execute($params);
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Get video Title and Date from videos table
        $video_info = null;
        $sql_video = "SELECT * FROM videos WHERE VIDEOID = :videoid LIMIT 1";
        try {
          $stmt_video = $pdo->prepare($sql_video);
          $stmt_video->execute(['videoid' => $videoID]);
          $video_info = $stmt_video->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
          // Ignore errors here
        }
        $title = $video_info['TITLE'] ?? 'Unknown';
        $date = 'Unknown';
        if (isset($video_info['TIME'])) {
          $dt = DateTime::createFromFormat('Y-m-d H:i:s', $video_info['TIME']);
          if ($dt) {
            $date = $dt->format('Y-m-d');
          }
        }

        // Format the song start time for use in youtube link
        $timestamp = preg_replace("/(\d{2}):(\d{2}):(\d{2})/", "$1h$2m$3s", $row['STARTTIME']);

        $results[] = [
          'song' => $row['TITLE'],
          'artist' => $row['ARTIST'],
          'video' => $title,
          'videoid' => $videoID,
          'date' => $date,
          'link' => "https://www.youtube.com/watch?v={$videoID}&t={$timestamp}",
        ];
      }
    } catch (PDOException $e) {
      // Ignore errors here (such as table not existing)
    }
  }
}

echo json_encode([
  'query' => $query,
  'count' => count($results),
  'items' => $results
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Close connection
$pdo = null;

?>