document.addEventListener('DOMContentLoaded', () => {
  const stats = document.getElementById('stats');

  function displayStats(data) {
    // Format the duration time to days, hours, minutes, and seconds
    function formatDuration(duration) {
      const days = Math.floor(duration / 86400);
      const hours = Math.floor((duration % 86400) / 3600);
      const minutes = Math.floor((duration % 3600) / 60);
      const seconds = duration % 60;
      return `${days} days, ${hours} hours, ${minutes} minutes, ${seconds} seconds`;
    }

    // Artists list
    function listArtists(artists) {
      let list = '<ol>';
      for (const artist of artists) {
        list += `<li><a href='../pages/search.html?q=${encodeURIComponent(artist[1].artist)}' class='video-link' target='_self' title='Search for ${artist[1].artist}'>${artist[1].artist}</a> - ${artist[1].count} songs</li>`;
      }
      list += '</ol>';
      return list;
    }

    // Songs list
    function listSongs(songs) {
      let list = '<ol>';
      for (const song of songs) {
        list += `<li><a href='../pages/search.html?q=${encodeURIComponent(song[1].title)}' class='video-link' target='_self' title='Search for ${song[1].title}'>${song[1].title} by ${song[1].artist}</a> - ${song[1].count} times</li>`;
      }
      list += '</ol>';
      return list;
    }

    // Format the generation time of the stats
    const generationTime = new Date(parseFloat(data.generated_at) * 1000);
    const updateTime = generationTime
      .toISOString()
      .replace('T', ' ')
      .replace(/\..+/, '');

    // Create HTML element
    const section = document.createElement('section');

    section.innerHTML = `
            <span style="font-weight: bold; font-size: 1.25rem">Database Statistics</span> <small>(Generated ${updateTime} UTC)</small>
            <ul>
                <li>Total Videos: <strong>${data.total_videos}</strong></li>
                <li>Total Songs: <strong>${data.total_songs}</strong></li>
                <li>Total Duration of All Songs: <strong>${formatDuration(data.total_duration)}</strong></li>
            </ul>
            <h4>Top 10 Artists</h4>
            ${listArtists(data.top_artists)}
            <h4>Top 10 Songs</h4>
            ${listSongs(data.top_songs)}
        `;

    stats.appendChild(section);
  }

  async function loadVideos() {
    // Create a wrapper for the loading spinner
    const wrapper = document.createElement('div');
    wrapper.className = 'loading-wrapper';

    const spinner = document.createElement('span');
    spinner.className = 'spinner';

    const text = document.createElement('span');
    text.textContent = 'Loading stats...';

    wrapper.appendChild(spinner);
    wrapper.appendChild(text);

    stats.innerHTML = ''; // clear old content
    stats.appendChild(wrapper);
    stats.setAttribute('aria-busy', 'true');

    // Load out data from data/stats.json
    try {
      const res = await fetch('../data/stats.json', { cache: 'default' });
      if (!res.ok) throw new Error('Network error');
      const data = await res.json();
      displayStats(data || []);
      stats.removeChild(wrapper);
      stats.setAttribute('aria-busy', 'false');
    } catch (err) {
      stats.innerHTML = `Error loading stats. ${err.message}`;
    }
  }
  loadVideos();
});
