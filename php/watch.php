<?php
$video_id = $_GET['v'] ?? null;
$start_time = (int)($_GET['t'] ?? 0);
$track = $_GET['track'] ?? null;

if (!$video_id) {
    header('Location: /');
    exit;
}

try {
    $pdo = require 'db.php';

    // Get all of our relevent information for this video ID
    $table = preg_replace('/[^a-zA-Z0-9_-]/', '', $video_id);

    // First information from the videos table
    $sql_query = "SELECT * FROM videos WHERE VIDEOID = :videoid LIMIT 1";
    $stmt = $pdo->prepare($sql_query);
    $stmt->execute(['videoid' => $video_id]);
    $video_info = $stmt->fetch(PDO::FETCH_ASSOC);

    // Next information from the karaokes table
    $sql_query = "SELECT * FROM karaokes WHERE VIDEOID = :videoid LIMIT 1";
    $stmt = $pdo->prepare($sql_query);
    $stmt->execute(['videoid' => $video_id]);
    $karaoke_info = $stmt->fetch(PDO::FETCH_ASSOC);

    // Last get the song information from the songs table
    $songs = [];
    $sql_query = "SELECT * FROM songs WHERE VIDEOID = :videoid AND TRACK = :track ORDER BY TRACK ASC";
    $stmt = $pdo->prepare($sql_query);
    $stmt->execute(['videoid' => $video_id, 'track' => $track]);
    $song_info = $stmt->fetch(PDO::FETCH_ASSOC);

    // Format our date to YYYY-MM-DD
    $date_aired = 'Unknown';
    if (isset($karaoke_info['TIME'])) {
        $date = DateTime::createFromFormat('Y-m-d H:i:s', $karaoke_info['TIME']);
        if ($date) {
            $date_aired = $date->format('Y-m-d');
        }
    }
} catch (PDOException $e) {
    // Return a JSON error message
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// Close the connection
$pdo = null;

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>Video Player</title>
    <link rel="stylesheet" href="../assets/css/style.css" type="text/css" />
    <link href="../assets/css/video-js.css" rel="stylesheet">
    <script src="../assets/js/video.min.js"></script>
</head>

<body style="padding: 1.25rem">
    <div id="video-container">
        <div class="video-item">
            <h3 class="video-title">
                <?php
                if (!$track) {
                    echo $video_info['TITLE'];
                } else {
                    echo $song_info['TITLE'] ?>. " - " .<?= $song_info['ARTIST'];
                                                    }
                                                        ?>
            </h3>
            <div class="video-body">
                <div class="video-thumb"><img src="../assets/images/video_thumbnails/<?= htmlspecialchars($video_id) ?>.jpg" alt="Video Thumbnail" width="200px" height="113px" title="<?= $video_info['TITLE'] ?>" /></div>
                <div class="video-info">
                    <p>
                        <?php if (!$track): ?>
                            <strong>Date Aired:</strong> <?= $date_aired ?><br />
                            <strong># of Songs:</strong> <?= $karaoke_info['NUM'] ?><br />
                            <strong>Video Link:</strong>
                            <a
                                href="../pages/video.html?videoID=<?= htmlspecialchars($video_id) ?>"
                                class="video-link"
                                target="_self">Song List</a>
                        <?php else: ?>
                            <strong>Video Title:</strong> <?= $video_info['TITLE'] ?><br />
                            <strong>Date Aired:</strong> <?= $date_aired ?><br />
                            <strong>Video Link:</strong>
                            <a
                                href="../pages/video.html?videoID=<?= htmlspecialchars($video_id) ?>"
                                class="video-link"
                                target="_self">Song List</a>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="table-container player-container" style="max-height: calc(100vh - 275px)">
            <video id="player" class="video-js vjs-default-skin" controls preload="auto">
                <source src="https://hls.claraoke-db.com/<?= htmlspecialchars($video_id) ?>/master.m3u8" type="application/x-mpegURL">
            </video>
        </div>
    </div>


    <script>
        const player = videojs('player', {
            fill: true,
        });
        const startTime = <?= $start_time ?>;

        player.ready(() => {
            if (startTime > 0) {
                player.one('loadedmetadata', () => {
                    player.currentTime(startTime);
                    player.play();
                });
            } else {
                player.one('loadedmetadata', () => {
                    player.play();
                });
            }
        });

        player.on('loadedmetadata', () => {
            const videoWidth = player.videoWidth();
            const videoHeight = player.videoHeight();
            const aspectRatio = videoHeight / videoWidth;
            const container = document.querySelector('.player-container');

            const updateSize = () => {
                const containerWidth = container.clientWidth;
                const calculatedHeight = containerWidth * aspectRatio;
                const maxHeight = window.innerHeight - 275;
                container.style.height = `${Math.min(calculatedHeight, maxHeight)}px`;
            };

            new ResizeObserver(updateSize).observe(container);
            window.addEventListener('resize', updateSize);
            updateSize();
        });
    </script>
</body>

</html>