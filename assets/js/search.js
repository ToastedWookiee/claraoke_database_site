(function () {
  const form = document.getElementById("searchForm");
  const input = document.getElementById("searchQuery");
  const resultsBody = document.getElementById("resultsBody");
  const progress = document.getElementById("progress");
  const resultsStats = document.getElementById("resultsStats");

  // Resize the table, max 1025px wide, 4 columns, col 1+2 flexible max width 860px together
  // col3 100px, col4 65px
  // Total min width = 250 + 150 + 100 + 65 = 565px
  // Total max width = 560 + 300 + 100 + 65 = 1025px
  function resizeTable() {
    const container = document.getElementById("resultsContainer");
    if (!container) return;
    const table = document.getElementById("resultsTable");
    if (!table) return;

    const containerWidth = Math.min(container.offsetWidth, 1025);

    const col3Width = 100;
    const col4Width = 65;
    const maxCol1Col2 = 860;
    const minCol1Width = 250;
    const maxCol1Width = 500;

    // Create a canvas for measuring text width
    const ctx = document.createElement("canvas").getContext("2d");
    ctx.font = getComputedStyle(table).font; // use table font

    // find max content width in col1
    let maxContentWidth = 0;
    table.querySelectorAll("tr").forEach((row) => {
      const cell = row.children[0];
      if (!cell) return;
      const text = cell.innerText || cell.textContent;
      const width = ctx.measureText(text).width + 20; // add some padding
      if (width > maxContentWidth) maxContentWidth = width;
    });

    // clamp col1 width between min and max
    let col1Width = Math.min(
      Math.max(maxContentWidth, minCol1Width),
      maxCol1Width
    );

    // remaining width for col2
    let remaining = containerWidth - col3Width - col4Width;
    let col2Width = remaining - col1Width;

    // ensure combined col1+col2 does not exceed maxCol1Col2
    if (col1Width + col2Width > maxCol1Col2) {
      col2Width = maxCol1Col2 - col1Width;
    }
    col2Width = Math.max(col2Width, 0);

    // apply widths
    table.querySelectorAll("tr").forEach((row) => {
      const cells = row.children;
      if (cells.length < 4) return;
      cells[0].style.width = col1Width + "px";
      cells[1].style.width = col2Width + "px";
      cells[2].style.width = col3Width + "px";
      cells[3].style.width = col4Width + "px";
    });
  }

  function setLoading(on) {
    if (on) progress.classList.add("is-active");
    else progress.classList.remove("is-active");
  }

  let hasSearched = false;

  function renderRows(items) {
    resultsBody.innerHTML = "";

    if (!items || !items.length) {
      resultsBody.innerHTML = `<tr><td colspan="4">${
        hasSearched ? "No results" : "Enter a query to begin."
      }</td></tr>`;
      return;
    }

    hasSearched = true;

    for (const item of items) {
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td class="col-song"><span class="truncate" title="${item.song} / ${item.artist}">${item.song} / ${item.artist}</span></td>
        <td class="col-video"><span class="truncate" title="${item.video}"><a href="../pages/video.html?videoID=${item.videoid}" target="_self">${item.video}</target></span></td>
        <td class="col-date">${item.date}</td>
        <td class="col-link"><a href="${item.link}" class="video-link" target="_blank" rel="noopener">Link</a></td>
      `;
      resultsBody.appendChild(tr);
    }
  }

  function displayStats(data) {
    if (!data || !data.items || data.items.length === 0) {
      resultsStats.innerHTML = "";
      return;
    }
    const total = data.count || 0;
    const query = data.query || "";
    resultsStats.innerHTML = `<strong>${total}</strong> result${
      total !== 1 ? "s" : ""
    } for "<em>${query}</em>"`;
  }

  async function doSearch(q) {
    if (!q) return; // optional: ignore empty queries

    hasSearched = true; // mark that a search has been performed

    setLoading(true);
    try {
      const res = await fetch("../php/search.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ q }),
      });
      if (!res.ok) throw new Error("Network error");
      const data = await res.json();
      displayStats(data || []);
      renderRows(data.items || []);
      resizeTable(); // Resize table after rendering new rows
    } catch (err) {
      console.error(err);
      displayStats([]);
      renderRows([]);
    } finally {
      setLoading(false);
    }
  }

  if (form) {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      doSearch(input.value.trim());
    });
  }

  window.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById("searchQuery");

    const params = new URLSearchParams(window.location.search);
    const rawQuery = params.get("q");
    if (rawQuery) {
      // decodeURIComponent converts %27 → '
      const query = decodeURIComponent(rawQuery);

      // Optional: convert HTML entities to normal characters
      const temp = document.createElement("textarea");
      temp.innerHTML = query; // decode HTML entities like &#039;
      const finalQuery = temp.value;

      document.getElementById("searchQuery").value = finalQuery;

      doSearch(finalQuery);
    }
  });
})();

document.addEventListener("DOMContentLoaded", () => {
  const table = document.getElementById("resultsTable");
  const tbody = document.getElementById("resultsBody");

  // ========================
  // Sorting logic
  // ========================
  if (!table) return;

  const headers = table.querySelectorAll("thead th");

  headers.forEach((header, index) => {
    const type = header.getAttribute("data-type");
    const sortable = header.getAttribute("data-sortable");

    // Only enable sorting for date columns or string columns marked sortable
    if (type === "date" || (type === "string" && sortable === "true")) {
      header.style.cursor = "pointer";

      header.addEventListener("click", () => {
        const rows = Array.from(tbody.querySelectorAll("tr"));
        const isAsc = header.classList.contains("sorted-asc");

        rows.sort((a, b) => {
          let aText = a.children[index].textContent.trim();
          let bText = b.children[index].textContent.trim();

          if (type === "number") {
            let aNum = parseFloat(aText) || 0;
            let bNum = parseFloat(bText) || 0;
            return isAsc ? bNum - aNum : aNum - bNum;
          }

          if (type === "date") {
            let aDate = new Date(aText);
            let bDate = new Date(bText);
            return isAsc ? bDate - aDate : aDate - bDate;
          }

          if (type === "string") {
            return isAsc
              ? bText.localeCompare(aText)
              : aText.localeCompare(bText);
          }

          return 0;
        });

        // Reset arrows
        headers.forEach((h) => h.classList.remove("sorted-asc", "sorted-desc"));

        // Toggle sort direction
        header.classList.toggle("sorted-asc", !isAsc);
        header.classList.toggle("sorted-desc", isAsc);

        // Apply sorted rows
        rows.forEach((row) => tbody.appendChild(row));
      });
    }
  });
});
