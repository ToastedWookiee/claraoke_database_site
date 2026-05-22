document.addEventListener('DOMContentLoaded', () => {
  const videoContainer = document.getElementById('video-container');
  const videoTitle = document.getElementById('video-title');
  const videoThumbnail = document.getElementById('video-thumbnail');
  const videoDate = document.getElementById('video-date');
  const videoSongsCount = document.getElementById('video-songs-count');
  const videoLink = document.getElementById('video-link');
  const songListBody = document.getElementById('song-list-body');
  const tableContainer = document.getElementsByClassName('table-container')[0];
  const descLink = document.getElementById('desc-link');

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

      const thumbnail_url = `../assets/images/video_thumbnails/${encodeURIComponent(videoID)}.jpg`;
      videoThumbnail.innerHTML = `<img src="${thumbnail_url}" alt="Video Thumbnail" width="200px" height="113px" title="${data.video_info.TITLE || ''}" />`;

      videoDate.textContent = data.date_aired || 'Unknown';
      videoSongsCount.textContent = data.karaoke_info.NUM || 0;
      videoLink.href = `../php/watch.php?v=${encodeURIComponent(videoID)}`;

      descLink.addEventListener('click', async (e) => {
        e.preventDefault();
        const res = await fetch(`../php/description.php?videoID=${videoID}`);
        const data = await res.json();
        document.getElementById('description-body').textContent =
          data.description || 'No description available.';
        document.getElementById('description-modal').style.display = 'block';
      });

      // Close modal
      document
        .getElementById('description-close')
        .addEventListener('click', () => {
          document.getElementById('description-modal').style.display = 'none';
        });
      document
        .getElementById('description-overlay')
        .addEventListener('click', () => {
          document.getElementById('description-modal').style.display = 'none';
        });

      songListBody.innerHTML = ''; // Clear existing rows
      if (data.songs && data.songs.length > 0) {
        data.songs.forEach((song) => {
          const timestamp = song.STARTTIME.replace(
            /(\d{2}):(\d{2}):(\d{2})/,
            '$1h$2m$3s'
          );

          const song_link = `../php/watch.php?v=${encodeURIComponent(videoID)}&track=${song.TRACK}&t=${encodeURIComponent(song.START_SECONDS)}`;

          const row = document.createElement('tr');
          row.innerHTML = `
                        <td><span class="truncate" title="${song.TITLE || ''}">${song.TITLE || 'Unknown Title'}</span></td>
                        <td><span class="truncate" title="${song.ARTIST || ''}" style="min-width: 300px">${song.ARTIST || 'Unknown Artist'}</span></td>
                        <td class="clickable-cell"><a href="${song_link}" class="full-link" target="_self">Watch</a></td>
                    `;
          songListBody.appendChild(row);
        });

        if (!window.matchMedia('(min-width: 769px)').matches) {
          // Override style max-height
          tableContainer.style.maxHeight = 'calc(100vh - 350px)';
        }
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
