(function () {
  const yearEl = document.getElementById('year');
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  const mainframe = document.getElementById('mainframe');
  const wrapper = document.querySelector('.iframe-wrap');
  const loader = document.getElementById('iframeLoading');
  const navLinks = document.querySelectorAll('.nav__link');
  const menuToggle = document.querySelector('.menu-toggle');
  const sidebar = document.getElementById('sidebar');

  window.hideIframeLoader = function () {
    if (wrapper) wrapper.classList.remove('loading');
    if (loader) loader.setAttribute('aria-hidden', 'true');
  };

  function showIframeLoader() {
    if (wrapper) wrapper.classList.add('loading');
    if (loader) loader.setAttribute('aria-hidden', 'false');
  }

  // Show loader when navigating via sidebar links
  mainframe.addEventListener('load', () => {
    let path;
    try {
      const url = new URL(mainframe.contentWindow.location.href);
      // Get only the filename (strip folder)
      path = url.pathname.split('/').pop();
    } catch (err) {
      console.warn('Cannot read iframe URL:', err);
      return;
    }

    // Remove 'is-active' from all links
    navLinks.forEach((a) => a.classList.remove('is-active'));

    // Set active only if a matching nav link exists
    const matchedLink = Array.from(navLinks).find((a) => {
      const linkPath = a.getAttribute('href') || a.dataset.page;
      // Strip folder from nav link href as well
      const linkFilename = linkPath.split('/').pop();
      return linkFilename === path;
    });

    if (matchedLink) {
      matchedLink.classList.add('is-active');
    }
  });

  // Also show loader on iframe navigation (e.g., internal links)
  if (mainframe) {
    mainframe.addEventListener('loadstart', showIframeLoader);
  }

  // Mobile menu
  if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', () => {
      const isOpen = sidebar.classList.toggle('is-open');
      menuToggle.setAttribute('aria-expanded', String(isOpen));
    });
  }

  // Initial loader brief hint
  showIframeLoader();
})();
