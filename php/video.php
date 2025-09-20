<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Video List</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <script>
        function fitText(el) {
            let fontSize = parseInt(window.getComputedStyle(el).fontSize);
            const minFontSize = 10; // minimum font size in px

            // Reduce font size until the text fits inside the element
            while (el.scrollHeight > el.clientHeight && fontSize > minFontSize) {
                fontSize--;
                el.style.fontSize = fontSize + 'px';
            }
        }

        // Apply to all h3.fit-text elements
        document.querySelectorAll('h3.video-title').forEach(fitText);

        // Re-fit on window resize
        window.addEventListener('resize', () => {
            document.querySelectorAll('h3.video-title').forEach(el => {
                el.style.fontSize = ''; // reset before recalculating
                fitText(el);
            });
        });

    </script>
</head>

<body style="padding: 1.25rem">
    <?php
    // Get videoID from request
    $videoID = $_REQUEST['videoID'] ?? '';
    if ($videoID === '') {
        echo "<h2>No videoID provided</h2>";
        exit;
    }

    // Get our database config and connect to the database
    $config = require 'db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);

    // Get all of our relevent information for this videoID
    $table = preg_replace('/[^a-zA-Z0-9_-]/', '', $videoID);

    // First information from the videos table
    $sql_query = "SELECT * FROM videos WHERE VIDEOID = :videoid LIMIT 1";
    try {
        $stmt = $pdo->prepare($sql_query);
        $stmt->execute(['videoid' => $videoID]);
        $video_info = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "❌ Query failed: " . $e->getMessage();
        exit;
    }

    // Next information from the karaokes table
    $sql_query = "SELECT * FROM karaokes WHERE VIDEOID = :videoid LIMIT 1";
    try {
        $stmt = $pdo->prepare($sql_query);
        $stmt->execute(['videoid' => $videoID]);
        $karaoke_info = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "❌ Query failed: " . $e->getMessage();
        exit;
    }

    // Last get the song list from its own table
    $songs = [];
    $sql_query = "SELECT * FROM `$table`";
    try {
        $stmt = $pdo->query($sql_query);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $songs[] = $row;
        }
    } catch (PDOException $e) {
        echo "❌ Query failed: " . $e->getMessage();
        exit;
    }

    // Format our date to YYYY-MM-DD
    $date_aired = 'Unknown';
    if (isset($karaoke_info['TIME'])) {
        $date = DateTime::createFromFormat('Y-m-d H:i:s', $karaoke_info['TIME']);
        if ($date) {
            $date_aired = $date->format('Y-m-d');
        }
    }

    // Display Results
    // Use HTML with inline PHP to show our results
    ?>

    <div class="video-item">
        <h3 class="video-title">
            <?php echo htmlspecialchars($video_info['TITLE'], ENT_QUOTES, 'UTF-8') ?>
        </h3>
        <div class="video-body">
            <div class="video-thumb">
                <img src="https://img.youtube.com/vi/<?php echo $videoID; ?>/hqdefault.jpg" alt="Video Thumbnail"
                    height="150px" title="<?php echo $video_info['TITLE']; ?>" />
            </div>
            <div class="video-info">
                <p><strong>Date Aired:</strong> <?php echo $date_aired; ?><br />
                    <strong># of Songs:</strong> <?php echo $karaoke_info['NUM']; ?><br />
                    <strong>Video Link:</strong> <a href="https://www.youtube.com/watch?v=<?php echo $videoID; ?>"
                        class="video-link" target="_blank" rel="noopener">Watch Video</a>
                </p>
            </div>
        </div>
    </div>
    <div class="video-table-wrap">
        <div class="song-list">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col" style="min-width: 150px;">Song Title</th>
                        <th scope="col" width="300px">Artist</th>
                        <th scope="col" width="140px" style="text-align: center;">Link to Watch</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Loop through our songs and display them
                    foreach ($songs as $song) {
                        $title = htmlspecialchars($song['TITLE'] ?? 'Unknown Title', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                        $artist = htmlspecialchars($song['ARTIST'] ?? 'Unknown Artist', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                        // Format the timestamp to fit Youtube's URL format
                        $timestamp = preg_replace("/(\d{2}):(\d{2}):(\d{2})/", "$1h$2m$3s", $song['STARTTIME']);
                        $link = "https://www.youtube.com/watch?v={$videoID}&t={$timestamp}";

                        echo "<td><span class='truncate' title='{$title}'>{$title}</span></td>";
                        echo "<td><span class='truncate-300' title='{$artist}'>{$artist}</span></td>";
                        echo "<td style='text-align: center;'><a href='{$link}' class='video-link' target='_blank' rel='noopener'>Link</a></td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php
    // Close the connection
    $pdo = null;
    ?>
</body>

</html>