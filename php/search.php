<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://clara.acormiz.com');
header('Access-Control-Allow-Headers: Content-Type');

$total_start = microtime(true);

$pdo_start = microtime(true);
$pdo = require 'db.php';
$pdo_end = microtime(true);

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

  // --- SQL INJECTION MITIGATION ---
  // Get the list of all tables in the database
  $query1_start = microtime(true);
  $stmt = $pdo->query("SHOW TABLES");
  $query1_end = microtime(true);
  $fetch1_start = microtime(true);
  $all_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
  $fetch1_end = microtime(true);

  // Get list of all videoIDs from the karaokes table
  $query2_start = microtime(true);
  $stmt = $pdo->query("SELECT VIDEOID FROM karaokes");
  $query2_end = microtime(true);
  $fetch2_start = microtime(true);
  $videoIDs_from_karaokes = $stmt->fetchAll(PDO::FETCH_COLUMN);
  $fetch2_end = microtime(true);

  // The valid video tables are the intersection of the actual tables and the video IDs from the karaokes table
  $valid_video_tables = array_intersect($videoIDs_from_karaokes, $all_tables);
  // --- END SQL INJECTION MITIGATION ---

  // --- PERFORMANCE OPTIMIZATION ---
  // Pre-fetch all video information for the valid tables to avoid N+1 queries
  $video_map_start = microtime(true);
  $video_info_map = [];
  if (!empty($valid_video_tables)) {
    // Create placeholders for the IN clause.
    // array_values is used to reset keys in case array_intersect resulted in non-sequential keys.
    $placeholders = implode(',', array_fill(0, count($valid_video_tables), '?'));
    $sql_video_info = "SELECT VIDEOID, TITLE, TIME FROM videos WHERE VIDEOID IN ($placeholders)";

    $exec3_start = microtime(true);
    $stmt_video_info = $pdo->prepare($sql_video_info);
    $stmt_video_info->execute(array_values($valid_video_tables));
    $exec3_end = microtime(true);

    $fetch3_start = microtime(true);
    while ($video_row = $stmt_video_info->fetch(PDO::FETCH_ASSOC)) {
      $video_info_map[$video_row['VIDEOID']] = $video_row;
    }
    $fetch3_end = microtime(true);
  }
  $video_map_end = microtime(true);
  // --- END PERFORMANCE OPTIMIZATION ---

  // Loop through each videoID and search its table for matching songs or artists
  $search_loop_time = 0;
  foreach ($valid_video_tables as $videoID) {
    $search_loop_start = microtime(true);
    // Sanitize table name as a secondary defense measure. The primary defense is the whitelist validation above.
    $table = preg_replace('/[^a-zA-Z0-9_-]/', '', $videoID);
    $sql_query = "SELECT * FROM `$table` WHERE ";
    $sql_query .= implode(' AND ', $conds);
    try {
      $exec4_start = microtime(true);
      $stmt = $pdo->prepare($sql_query);
      $stmt->execute($params);
      $exec4_end = microtime(true);

      $fetch4_start = microtime(true);
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Get video Title and Date from the pre-fetched map
        $video_info = $video_info_map[$videoID] ?? null;

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
    $fetch4_end = microtime(true);

    $search_loop_end = microtime(true);
    $search_loop_time += $search_loop_end - $search_loop_start;
  }
}

$total_end = microtime(true);

echo json_encode([
  'query' => $query,
  'count' => count($results),
  'items' => $results
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

error_log(json_encode([
  'connect_ms' => ($pdo_end - $pdo_start) * 1000,
  'query1_ms' => ($query1_end - $query1_start) * 1000,
  'fetch1_ms' => ($fetch1_end - $fetch1_start) * 1000,
  'query2_ms' => ($query2_end - $query2_start) * 1000,
  'fetch2_ms' => ($fetch2_end - $fetch2_start) * 1000,
  'exec3_ms' => ($exec3_end - $exec3_start) * 1000,
  'fetch3_ms' => ($fetch3_end - $fetch3_start) * 1000,
  'exec4_ms' => ($exec4_end - $exec4_start) * 1000,
  'fetch4_ms' => ($fetch4_end - $fetch4_start) * 1000,
  'video_map_ms' => ($video_map_end - $video_map_start) * 1000,
  'search_loop_ms' => $search_loop_time * 1000,
  'total_ms' => ($total_end - $total_start) * 1000,
]));

// Close connection
$pdo = null;

?>