(function () {
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
      const thumbnail_url = `assets/images/video_thumbnails/${encodeURIComponent(item.VIDEOID)}.jpg`;

      const mainRow = document.createElement('tr');
      mainRow.className = 'main-row';
      mainRow.innerHTML = `
        <td class="col-thumb"><img class="list-thumbnail" src="${thumbnail_url}" alt="Thumbnail" title="${item.TITLE}" width="67px" height="38px" loading="lazy"></td>
        <td class="col-title" style="padding-left: 0.75rem; text-align: left;">
          <span class="truncate" title="${item.TITLE}">
            ${item.TITLE}
          </span>
        </td>
        <td class="col-date" style="text-align: center">${item.TIME}</td>
        <td class="col-songs" style="text-align: center">${item.NUM}</td>
        <td class="col-link"><a class="full-link" onclick="navigateTo('watch', {v: '${item.VIDEOID}'})" style="justify-content: center">Watch</a></td>
      `;

      const expandableRow = document.createElement('tr');
      expandableRow.className = 'expandable-row';
      expandableRow.innerHTML = `
        <td colspan="5">
          <div><strong>Date Aired:</strong> ${item.TIME}</div>
          <div><strong># of Songs:</strong> ${item.NUM}</div>
          <div class="expandable-actions">
            <button class="btn btn--outline" onclick="navigateTo('watch', {v: '${item.VIDEOID}'})">
              Watch Video
            </button>
            <button class="btn btn--outline" onclick="navigateTo('video', {v: '${item.VIDEOID}'})">
              View Song List
            </button>
          </div>
        </td>
      `;

      tbody.appendChild(mainRow);
      tbody.appendChild(expandableRow);

      mainRow.addEventListener('click', () => {
        // Toggle expansion
        expandableRow.classList.toggle('is-visible');

        // On desktop, we might want to navigate if they click the title area?
        // But clicking a row to expand is standard now.
      });
    }
  }

  async function loadVideos() {
    try {
      const res = await fetch('php/videolist.php', { cache: 'no-store' });
      if (!res.ok) throw new Error('Network error');
      const data = await res.json();
      renderRows(data.items || []);
    } catch (err) {
      tbody.innerHTML = `<tr><td colspan="5">Error loading video list: ${err.message}</td></tr>`;
    }
  }

  // ========================
  // Sorting logic
  // ========================
  if (!table) return;
  const headers = table.querySelectorAll('thead th');

  headers.forEach((header, index) => {
    const type = header.getAttribute('data-type');
    if (!type) return;

    header.style.cursor = 'pointer';

    header.addEventListener('click', () => {
      const rows = Array.from(tbody.querySelectorAll('.main-row'));

      // Determine current direction
      const isAsc = header.classList.contains('sorted-asc');
      const nextAsc = !isAsc;

      rows.sort((a, b) => {
        let aText = a.children[index].textContent.trim();
        let bText = b.children[index].textContent.trim();

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

        if (type === 'string') {
          return nextAsc ? aText.localeCompare(bText) : bText.localeCompare(aText);
        }

        return 0;
      });

      // Reset classes
      headers.forEach((h) => h.classList.remove('sorted-asc', 'sorted-desc'));

      // Apply new direction
      header.classList.add(nextAsc ? 'sorted-asc' : 'sorted-desc');

      rows.forEach((row) => {
        const expandable = row.nextElementSibling;
        tbody.appendChild(row);
        if (expandable && expandable.classList.contains('expandable-row')) {
          tbody.appendChild(expandable);
        }
      });
    });
  });

  loadVideos();
})();
