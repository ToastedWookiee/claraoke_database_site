(function () {
  const yearEl = document.getElementById('year');
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  const mainframe = document.getElementById('mainframe');
  const wrapper = document.querySelector('.iframe-wrap');
  const loader = document.getElementById('iframeLoading');
  const navLinks = document.querySelectorAll('.nav__link');
  const menuToggle = document.querySelector('.menu-toggle');
  const sidebar = document.getElementById('sidebar');

  const history = [];

  window.hideIframeLoader = function () {
    if (wrapper) wrapper.classList.remove('loading');
    if (loader) loader.setAttribute('aria-hidden', 'true');
  };

  function showIframeLoader() {
    if (wrapper) wrapper.classList.add('loading');
    if (loader) loader.setAttribute('aria-hidden', 'false');
  }

  function updateBackButton() {
    const backBtn = document.getElementById('back-btn');
    if (backBtn) {
      backBtn.style.display = history.length > 1 ? 'inline-flex' : 'none';
    }
  }

  window.goBack = function () {
    if (history.length > 1) {
      history.pop();
      mainframe.src = history[history.length - 1];
      updateBackButton();
    }
  };

  // Show loader when navigating via sidebar links
  mainframe.addEventListener('load', () => {
    // Track navigation using the actual iframe URL, not mainframe.src
    let currentUrl;
    try {
      currentUrl = mainframe.contentWindow.location.href;
    } catch (err) {
      currentUrl = mainframe.src;
    }

    if (history.length === 0 || history[history.length - 1] !== currentUrl) {
      history.push(currentUrl);
      console.log('History updated:', history);
      updateBackButton();
    }

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
      if (!linkPath) return false; // skip elements with no href or data-page
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

    // Close sidebar when a nav link is clicked (on mobile)
    const navLinks = sidebar.querySelectorAll('a');

    navLinks.forEach((link) => {
      link.addEventListener('click', () => {
        // Only close if sidebar is open (mobile view)
        if (sidebar.classList.contains('is-open')) {
          sidebar.classList.remove('is-open');
          menuToggle.setAttribute('aria-expanded', 'false');
        }
      });
    });
  }

  // Initial loader brief hint
  showIframeLoader();
})();
