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
  // Build boolean query
  $search_terms = array_map(fn($word) => '+' . $word, explode(' ', $query));
  $boolean_query = implode(' ', $search_terms);

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

  // Loop through each videoID and search its table for matching songs or artists
  $search_loop_time = 0;
  $search_loop = 0;

  // Query to get VIDEOID tables that have matching search terms
  $sql = "SELECT VIDEOID FROM karaokes
          WHERE MATCH(SEARCHTITLE, SEARCHARTIST) AGAINST (? IN BOOLEAN MODE)";

  $search_loop_start = microtime(true);

  $exec3_start = microtime(true);
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$boolean_query]);
  $exec3_end = microtime(true);

  $fetch3_start = microtime(true);
  $matching_videoids = $stmt->fetchAll(PDO::FETCH_COLUMN);
  $fetch3_end = microtime(true);

  foreach ($matching_videoids as $videoID) {
    // --- SQL INJECTION MITIGATION ---
    // Check if the requested videoID is a valid table
    if (!in_array($videoID, $valid_video_tables)) {
      continue;
    }
    // --- END SQL INJECTION MITIGATION ---

    // Sanitize table name as a secondary defense measure. The primary defense is the whitelist validation above.
    $table = preg_replace('/[^a-zA-Z0-9_-]/', '', $videoID);

    # $sql_query = "SELECT * FROM `$table` WHERE MATCH(TITLE, ARTIST) AGAINST (? IN BOOLEAN MODE)";
    $sql_query = "
        SELECT 
          s.TITLE AS song,
          s.ARTIST AS artist,
          s.STARTTIME AS time,
          v.TITLE AS video,
          v.VIDEOID AS videoid,
          v.TIME AS date
        FROM `$table` AS s
        LEFT JOIN videos AS v ON s.VIDEOID = v.VIDEOID WHERE MATCH(s.TITLE, s.ARTIST) AGAINST (? IN BOOLEAN MODE)
        ORDER BY v.TIME DESC;
    ";

    try {
      $exec4_start = microtime(true);
      $stmt = $pdo->prepare($sql_query);
      $stmt->execute([$boolean_query]);
      $exec4_end = microtime(true);

      $fetch4_start = microtime(true);
      $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $fetch4_end = microtime(true);

      foreach ($res as $row) {
        // Format the date
        $date = 'Unknown';
        if (isset($row['date'])) {
          $dt = DateTime::createFromFormat('Y-m-d H:i:s', $row['date']);
          if ($dt) {
            $date = $dt->format('Y-m-d');
          }
        }

        // Format the song start time for use in youtube link
        $timestamp = preg_replace("/(\d{2}):(\d{2}):(\d{2})/", "$1h$2m$3s", $row['time']);

        $results[] = [
          'song' => $row['song'],
          'artist' => $row['artist'],
          'video' => $row['video'] ?? 'Unknown',
          'videoid' => $row['videoid'],
          'date' => $date,
          'link' => "https://www.youtube.com/watch?v={$videoID}&t={$timestamp}",
        ];
      }
    } catch (PDOException $e) {
      // Ignore errors here (such as table not existing)
    }

    $search_loop++;
  }
  $search_loop_end = microtime(true);
  $search_loop_time = $search_loop_end - $search_loop_start;
}

echo json_encode([
  'query' => $query,
  'count' => count($results),
  'items' => $results
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$total_end = microtime(true);

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
  'search_loops' => $search_loop,
  'search_loop_ms' => $search_loop_time * 1000,
  'search_loop_avg_ms' => $search_loop_time * 1000 / $search_loop,
  'total_ms' => ($total_end - $total_start) * 1000,
]));

// Close connection
$pdo = null;

?>