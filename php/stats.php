<?php
// Get our database config and connect to the database
$config = require 'db.php';
$dsn = "mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$pdo = new PDO($dsn, $config['user'], $config['pass'], $options);

// Function to clean up artist names
function clean_artist_name($artist)
{
    // Remove parentheses (), brackets [], and braces {}
    $artist = preg_replace('/\s*[\(\[\{][^)\]\}]*[\)\]\}]/', '', $artist);

    // Cut off at dash, slash, or comma
    $artist = preg_replace('/\s*[-\/,].*/', '', $artist);

    // Remove "feat.", "featuring", "ft." (case-insensitive)
    $artist = preg_replace('/\s+(feat\.?|featuring|ft\.)\s+.*/i', '', $artist);

    // Normalize spaces
    $artist = trim($artist);
    $artist = preg_replace('/\s+/', ' ', $artist); // collapse multiple spaces

    return $artist;
}

// Get total number of videos
$sql_query = "SELECT COUNT(*) AS total FROM karaokes";

try {
    $stmt = $pdo->query($sql_query);                 // run the query
    $row = $stmt->fetch(PDO::FETCH_ASSOC);            // fetch as associative array
    $total = $row['total'];                                 // access the count

} catch (PDOException $e) {
    echo "❌ Query failed: " . $e->getMessage();
}

// Get total number of songs
$sql_query = "SELECT NUM FROM karaokes";

try {
    $stmt = $pdo->query($sql_query);                 // run the query
    $song_count = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {   // fetch as associative array
        $song_count += $row['NUM'];
    }
} catch (PDOException $e) {
    echo "❌ Query failed: " . $e->getMessage();
}

// Get an array of every karaoke videoID
$sql_query = "SELECT VIDEOID FROM karaokes";

try {
    $stmt = $pdo->query($sql_query);                 // run the query
    $videoIDs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {   // fetch as associative array
        $videoIDs[] = $row['VIDEOID'];
    }
} catch (PDOException $e) {
    echo "❌ Query failed: " . $e->getMessage();
}

// Make sure videoIDs are unique
$videoIDs = array_unique($videoIDs);

// Get the top 10 artists
// Loop through each videoID and get the artists and put in an array with their count
$artists = [];
foreach ($videoIDs as $videoID) {
    $table = preg_replace('/[^a-zA-Z0-9_-]/', '', $videoID);
    $sql_query = "SELECT ARTIST FROM `$table`";
    try {
        $stmt = $pdo->query($sql_query);                 // run the query
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {     // fetch as associative array
            $artist = strtolower($row['ARTIST']);
            $artist = clean_artist_name($artist);

            // If artist contains: "and", "&", or "with" then split and count each separately
            if (preg_match('/\s+(and|&|with)\s+/', $artist)) {
                $sub_artists = preg_split('/\s+(and|&|with)\s+/', clean_artist_name($row['ARTIST']));
                foreach ($sub_artists as $sub_artist) {
                    $sub_artist = $sub_artist_ori = trim($sub_artist);
                    $sub_artist = strtolower($sub_artist);
                    if ($sub_artist === '') {
                        continue;
                    }
                    if (isset($artists[$sub_artist])) {
                        $artists[$sub_artist]['count']++;
                    } else {
                        $sub_artist_trim = clean_artist_name($sub_artist_ori);
                        $artists[$sub_artist] = array(
                            'count' => 1,
                            'artist' => $sub_artist_trim
                        );
                    }
                }
                continue; // Skip the rest of this loop
            }

            if (isset($artists[$artist])) {
                $artists[$artist]['count']++;
            } else {
                $artist_trim = clean_artist_name($row['ARTIST']);
                $artists[$artist] = array(
                    'count' => 1,
                    'artist' => $artist_trim
                );
            }
        }
    } catch (PDOException $e) {
        echo "❌ Query failed: " . $e->getMessage();
    }
}

// Sort the artists by count in descending order limit to top 10, ignore "Unknown Artist"
arsort($artists);
$top_artists = [];
$i = 0;
foreach ($artists as $artist => $details) {
    if ($artist != "unknown artist") {
        $top_artists[$artist] = array(
            'count' => $details['count'],
            'artist' => $details['artist'] // Store the original artist name
        );
        $i++;
        if ($i == 10) {
            break;
        }
    }
}

// Get the top 10 songs
// Loop through each videoID and get the songs and put in an array with their count
// Create an array to hold each song, its count, and the artist
$songs = [];
foreach ($videoIDs as $videoID) {
    $table = preg_replace('/[^a-zA-Z0-9_-]/', '', $videoID);
    $sql_query = "SELECT TITLE, ARTIST FROM `$table`";
    try {
        $stmt = $pdo->query($sql_query);                 // run the query
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {     // fetch as associative array
            $title = strtolower($row['TITLE']);
            if (isset($songs[$title])) {
                $songs[$title]['count']++;
            } else {
                $songs[$title] = array(
                    'count' => 1,
                    'title' => $row['TITLE'], // Preserve original casing
                    'artist' => $row['ARTIST']
                );
            }
        }
    } catch (PDOException $e) {
        echo "❌ Query failed: " . $e->getMessage();
    }
}

// Sort the songs by count in descending order limit to top 10
arsort($songs);
$top_songs = [];
$i = 0;
foreach ($songs as $song => $details) {
    $top_songs[$song] = array(
        'count' => $details['count'],
        'artist' => $details['artist'],
        'title' => $details['title']
    );
    $i++;
    if ($i == 10) {
        break;
    }
}

// Get the total duration of all songs
$total_duration = 0;

foreach ($videoIDs as $videoID) {
    $table = preg_replace('/[^a-zA-Z0-9_-]/', '', $videoID);
    $sql_query = "SELECT DURATION FROM `$table`";
    try {
        $stmt = $pdo->query($sql_query);                 // run the query
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {     // fetch as associative array
            $total_duration += $row['DURATION'];
        }
    } catch (PDOException $e) {
        echo "❌ Query failed: " . $e->getMessage();
    }
}

// Convert the duration from seconds to days, hours, minutes, and seconds
$days = floor($total_duration / 86400);
$hours = floor(($total_duration % 86400) / 3600);
$minutes = floor(($total_duration % 3600) / 60);
$seconds = $total_duration % 60;

// Output the statistics
echo "<h3>Database Statistics</h3>";
echo "<ul>";
echo "<li>Total Videos: <strong>$total</strong></li>";
echo "<li>Total Songs: <strong>$song_count</strong></li>";
echo "<li>Total Duration of All Songs: <strong>$days days, $hours hours, $minutes minutes, $seconds seconds</strong></li>";
echo "</ul>";
echo "<h4>Top 10 Artists</h4>";
echo "<ol>";
foreach ($top_artists as $artist => $details) {
    echo "<li><a href='../pages/search.html?q=" . urlencode(htmlspecialchars($details['artist'])) .
        "' class='video-link' target='_self' title='Search for " . htmlspecialchars($details['artist']) . "'>" . htmlspecialchars($details['artist']) .
        "</a> - " . $details['count'] . " songs</li>";
}
echo "</ol>";
echo "<h4>Top 10 Songs</h4>";
echo "<ol>";
foreach ($top_songs as $song => $details) {
    echo "<li><a href='../pages/search.html?q=" . urlencode(htmlspecialchars($details['title'])) .
        "' class='video-link' target='_self' title='Search for " . htmlspecialchars($details['title']) . "'>" . htmlspecialchars($details['title']) .
        " by " . htmlspecialchars($details['artist']) . "</a> - " . $details['count'] . " videos</li>";
}
echo "</ol>";

// Close the connection
$pdo = null;

?>