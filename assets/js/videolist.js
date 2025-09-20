document.addEventListener("DOMContentLoaded", () => {
  const table = document.getElementById("videoTable");
  const tbody = document.getElementById("videolist");

  // ========================
  // Load data via AJAX
  // ========================
  fetch("../php/videolist.php", { cache: "no-store" })
    .then((response) => {
      if (!response.ok) throw new Error("Network error");
      return response.text();
    })
    .then((html) => {
      tbody.innerHTML = html; // PHP returns <tr> rows only
    })
    .catch((err) => {
      tbody.innerHTML = `<tr><td colspan="5">Error loading video list: ${err.message}</td></tr>`;
    });

  // ========================
  // Sorting logic
  // ========================
  if (!table) return;
  const headers = table.querySelectorAll("thead th");

  let colIndex = 0;
  headers.forEach((header) => {
    const type = header.getAttribute("data-type");
    const span = parseInt(header.getAttribute("colspan") || "1", 10);
    const thisColIndex = colIndex;
    colIndex += span;

    if (type === "number" || type === "date") {
      header.style.cursor = "pointer";

      header.addEventListener("click", () => {
        const rows = Array.from(tbody.querySelectorAll("tr"));

        // Determine current direction
        const isAsc = header.classList.contains("sorted-asc");
        const isDesc = header.classList.contains("sorted-desc");
        const nextAsc = !isAsc; // flip if currently asc
        const nextDesc = isAsc || !isDesc;

        rows.sort((a, b) => {
          let aText = a.children[thisColIndex].textContent.trim();
          let bText = b.children[thisColIndex].textContent.trim();

          if (type === "number") {
            let aNum = parseFloat(aText) || 0;
            let bNum = parseFloat(bText) || 0;
            return nextAsc ? aNum - bNum : bNum - aNum;
          }

          if (type === "date") {
            let aDate = new Date(aText);
            let bDate = new Date(bText);
            return nextAsc ? aDate - bDate : bDate - aDate;
          }

          return 0;
        });

        // Reset classes
        headers.forEach((h) => h.classList.remove("sorted-asc", "sorted-desc"));

        // Apply new direction
        if (nextAsc) {
          header.classList.add("sorted-asc");
        } else {
          header.classList.add("sorted-desc");
        }

        rows.forEach((row) => tbody.appendChild(row));
      });
    }
  });
});
