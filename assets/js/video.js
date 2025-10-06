document.addEventListener('DOMContentLoaded', () => {
  const videoContainer = document.getElementById('video-container');
  const videoTitle = document.getElementById('video-title');
  const videoThumbnail = document.getElementById('video-thumbnail');
  const videoDate = document.getElementById('video-date');
  const videoSongsCount = document.getElementById('video-songs-count');
  const videoLink = document.getElementById('video-link');
  const songListBody = document.getElementById('song-list-body');

  const urlParams = new URLSearchParams(window.location.search);
  const videoID = urlParams.get('videoID');

  if (!videoID) {
    videoContainer.innerHTML = '<h2>No videoID provided</h2>';
    return;
  }

  async function loadVideo() {
    try {
      const res = await fetch(`../php/video.php?videoID=${videoID}`, {
        cache: 'no-store',
      });
      if (!res.ok) {
        throw new Error('Network response was not ok');
      }
      const data = await res.json();

      if (data.error) {
        throw new Error(data.error);
      }

      videoTitle.textContent = data.video_info.TITLE || 'Unknown Title';

      const thumbnail_url = `https://img.youtube.com/vi/${encodeURIComponent(videoID)}/hqdefault.jpg`;
      videoThumbnail.innerHTML = `<img src="${thumbnail_url}" alt="Video Thumbnail" height="150px" title="${data.video_info.TITLE || ''}" />`;

      videoDate.textContent = data.date_aired || 'Unknown';
      videoSongsCount.textContent = data.karaoke_info.NUM || 0;
      videoLink.href = `https://www.youtube.com/watch?v=${encodeURIComponent(videoID)}`;

      songListBody.innerHTML = ''; // Clear existing rows
      if (data.songs && data.songs.length > 0) {
        data.songs.forEach((song) => {
          const timestamp = song.STARTTIME.replace(
            /(\d{2}):(\d{2}):(\d{2})/,
            '$1h$2m$3s'
          );

          const song_link = `https://www.youtube.com/watch?v=${encodeURIComponent(videoID)}&t=${encodeURIComponent(timestamp)}`;

          const row = document.createElement('tr');
          row.innerHTML = `
                        <td><span class="truncate" title="${song.TITLE || ''}">${song.TITLE || 'Unknown Title'}</span></td>
                        <td><span class="truncate-300" title="${song.ARTIST || ''}">${song.ARTIST || 'Unknown Artist'}</span></td>
                        <td style="text-align: center;"><a href="${song_link}" class="video-link" target="_blank" rel="noopener">Link</a></td>
                    `;
          songListBody.appendChild(row);
        });
      } else {
        const row = document.createElement('tr');
        row.innerHTML = `<td colspan="3">No songs found for this video.</td>`;
        songListBody.appendChild(row);
      }
    } catch (err) {
      videoContainer.innerHTML = `<h2>Error loading video: ${err.message}</h2>`;
    }
  }

  loadVideo();
});
