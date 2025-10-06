document.addEventListener('DOMContentLoaded', () => {
  const table = document.getElementById('videoTable');
  const tbody = document.getElementById('videolist');

  // ========================
  // Load data via AJAX
  // ========================
  function renderRows(items) {
    tbody.innerHTML = ''; // Clear existing rows

    if (!items || !items.length) {
      tbody.innerHTML = `<tr><td colspan="5">No videos found.</td></tr>`;
      return;
    }

    for (const item of items) {
      const video_url = `../pages/video.html?videoID=${encodeURIComponent(item.VIDEOID)}`;
      const youtube_url = `https://www.youtube.com/watch?v=${encodeURIComponent(item.VIDEOID)}`;
      const thumbnail_url = `https://img.youtube.com/vi/${encodeURIComponent(item.VIDEOID)}/default.jpg`;

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><a href="${video_url}"><img style="vertical-align:middle" src="${thumbnail_url}" alt="Thumbnail" title="${item.TITLE}" height="50"></a></td>
        <td style="padding-left: 0.75rem; text-align: left;">
          <span class="truncate" title="${item.TITLE}">
            <a href="${video_url}" target="_self">${item.TITLE}</a>
          </span>
        </td>
        <td style="text-align: center">${item.TIME}</td>
        <td style="text-align: center">${item.NUM}</td>
        <td style="text-align: center"><a href="${youtube_url}" class="video-link" target="_blank" rel="noopener">Link</a></td>
      `;
      tbody.appendChild(tr);
    }
  }

  async function loadVideos() {
    try {
      const res = await fetch('../php/videolist.php', { cache: 'no-store' });
      if (!res.ok) throw new Error('Network error');
      const data = await res.json();
      renderRows(data.items || []);
    } catch (err) {
      tbody.innerHTML = `<tr><td colspan="5">Error loading video list: ${err.message}</td></tr>`;
    }
  }

  loadVideos();

  // ========================
  // Sorting logic
  // ========================
  if (!table) return;
  const headers = table.querySelectorAll('thead th');

  let colIndex = 0;
  headers.forEach((header) => {
    const type = header.getAttribute('data-type');
    const span = parseInt(header.getAttribute('colspan') || '1', 10);
    const thisColIndex = colIndex;
    colIndex += span;

    if (type === 'number' || type === 'date') {
      header.style.cursor = 'pointer';

      header.addEventListener('click', () => {
        const rows = Array.from(tbody.querySelectorAll('tr'));

        // Determine current direction
        const isAsc = header.classList.contains('sorted-asc');
        const isDesc = header.classList.contains('sorted-desc');
        const nextAsc = !isAsc; // flip if currently asc
        const nextDesc = isAsc || !isDesc;

        rows.sort((a, b) => {
          let aText = a.children[thisColIndex].textContent.trim();
          let bText = b.children[thisColIndex].textContent.trim();

          if (type === 'number') {
            let aNum = parseFloat(aText) || 0;
            let bNum = parseFloat(bText) || 0;
            return nextAsc ? aNum - bNum : bNum - aNum;
          }

          if (type === 'date') {
            let aDate = new Date(aText);
            let bDate = new Date(bText);
            return nextAsc ? aDate - bDate : bDate - aDate;
          }

          return 0;
        });

        // Reset classes
        headers.forEach((h) => h.classList.remove('sorted-asc', 'sorted-desc'));

        // Apply new direction
        if (nextAsc) {
          header.classList.add('sorted-asc');
        } else {
          header.classList.add('sorted-desc');
        }

        rows.forEach((row) => tbody.appendChild(row));
      });
    }
  });
});
