/**
 * RepairKar — Global Page Loader & Transition Controller
 * Automatically manages loading screens and page transitions across all pages.
 */
(function () {
  'use strict';

  // Prevent multiple initializations
  if (window.__rkLoaderInitialized) return;
  window.__rkLoaderInitialized = true;

  // Insert Loader HTML into document if not present
  function ensureLoaderDOM() {
    if (document.getElementById('rk-page-loader')) return;

    const progress = document.createElement('div');
    progress.id = 'rk-page-progress';

    const loader = document.createElement('div');
    loader.id = 'rk-page-loader';
    loader.setAttribute('role', 'status');
    loader.setAttribute('aria-live', 'polite');
    loader.setAttribute('aria-label', 'Loading page');
    loader.innerHTML = `
      <div class="rk-loader-content">
        <div class="rk-loader-spinner-wrapper">
          <div class="rk-loader-ring-outer"></div>
          <div class="rk-loader-ring"></div>
          <i class="fa-solid fa-wrench rk-loader-icon" aria-hidden="true"></i>
        </div>
        <div class="rk-loader-brand">Repair<span>Kar</span></div>
        <div class="rk-loader-text">Loading<span class="rk-loader-dots"><span></span><span></span><span></span></span></div>
      </div>
    `;

    // Prepend as very first elements in body or documentElement
    if (document.body) {
      document.body.prepend(progress);
      document.body.prepend(loader);
    } else {
      document.addEventListener('DOMContentLoaded', function () {
        document.body.prepend(progress);
        document.body.prepend(loader);
      });
    }
  }

  // Inject DOM immediately
  ensureLoaderDOM();

  // Progress simulation
  let progressWidth = 20;
  const progressInterval = setInterval(function () {
    const progressEl = document.getElementById('rk-page-progress');
    if (progressEl) {
      progressWidth = Math.min(progressWidth + Math.random() * 15, 85);
      progressEl.style.width = progressWidth + '%';
    }
  }, 120);

  // Hide loader
  function hideLoader() {
    clearInterval(progressInterval);
    const progressEl = document.getElementById('rk-page-progress');
    const loaderEl = document.getElementById('rk-page-loader');

    if (progressEl) {
      progressEl.style.width = '100%';
      setTimeout(function () {
        progressEl.style.opacity = '0';
      }, 200);
    }

    if (loaderEl) {
      loaderEl.classList.add('loaded');
      setTimeout(function () {
        if (loaderEl.parentNode) {
          // Keep in DOM with pointer-events: none / opacity 0 for reuse in transitions
        }
      }, 400);
    }
  }

  // Trigger hide on page full load
  if (document.readyState === 'complete') {
    hideLoader();
  } else {
    window.addEventListener('load', hideLoader);
    // Safety fallback timeout in case a slow asset blocks the load event
    setTimeout(hideLoader, 1400);
  }

  // Smooth page navigation transitions for internal links
  document.addEventListener('click', function (e) {
    const link = e.target.closest('a');
    if (!link) return;

    const href = link.getAttribute('href');
    const target = link.getAttribute('target');

    // Skip empty links, hashes, javascript:, external links, or new tabs
    if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:') || target === '_blank') {
      return;
    }

    // Check if same origin / relative
    if (link.hostname && link.hostname !== window.location.hostname) {
      return;
    }

    // Show top progress bar on navigation click
    const progressEl = document.getElementById('rk-page-progress');
    if (progressEl) {
      progressEl.style.opacity = '1';
      progressEl.style.width = '40%';
    }
  });

  // Expose global controller
  window.RepairKarLoader = {
    show: function () {
      const loaderEl = document.getElementById('rk-page-loader');
      const progressEl = document.getElementById('rk-page-progress');
      if (loaderEl) loaderEl.classList.remove('loaded');
      if (progressEl) {
        progressEl.style.opacity = '1';
        progressEl.style.width = '30%';
      }
    },
    hide: hideLoader
  };
})();
