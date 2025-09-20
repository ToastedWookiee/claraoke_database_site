(function () {
  const form = document.getElementById("searchForm");
  const input = document.getElementById("searchQuery");
  const resultsBody = document.getElementById("resultsBody");
  const progress = document.getElementById("progress");
  const resultsStats = document.getElementById("resultsStats");

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
        <td class="col-video"><span class="truncate" title="${item.video}"><a href="../php/video.php?videoID=${item.videoid}" target="_self">${item.video}</target></span></td>
        <td>${item.date}</td>
        <td style="text-align:center;"><a href="${item.link}" class="video-link" target="_blank" rel="noopener">Link</a></td>
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
    const initialQuery = params.get("q");

    if (initialQuery) {
      searchInput.value = initialQuery;
      doSearch(initialQuery); // your AJAX search function
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
