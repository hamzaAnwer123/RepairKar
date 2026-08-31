/**
 * RepairKar — Unified Navbar Handler
 * Manages authentication state, user profile dropdown, and mobile navigation.
 */
document.addEventListener('DOMContentLoaded', function () {
  // Determine relative base path based on directory location
  const path = window.location.pathname.replace(/\\/g, '/');
  const isSubdir = path.includes('/user/') || path.includes('/mechanic/') || path.includes('/admin/');
  const basePath = isSubdir ? '../' : '';

  // Elements
  const guestActions = document.getElementById('guest-actions');
  const accountActions = document.getElementById('account-actions');
  const accountName = document.getElementById('account-name');
  const accountIcon = document.getElementById('account-icon');
  const desktopLogin = document.getElementById('desktop-login');
  const guestSignup = document.getElementById('guest-signup');
  const mobileLogin = document.getElementById('mobile-login');
  const mobileSignup = document.getElementById('mobile-signup');
  const mobileAccountSection = document.getElementById('mobile-account-section');
  const mobileAccountName = document.getElementById('mobile-account-name');
  const mobileAccountIcon = document.getElementById('mobile-account-icon');
  const profileToggle = document.getElementById('profile-toggle');
  const dropdownMenu = document.getElementById('profile-dropdown-menu');
  const dropdownArrow = document.getElementById('dropdown-arrow');
  const dashboardLink = document.getElementById('dashboard-link');
  const viewProfileLink = document.getElementById('view-profile-link');
  const mobileDashboardLink = document.getElementById('mobile-dashboard-link');
  const mobileViewProfileLink = document.getElementById('mobile-view-profile-link');
  const logoutBtn = document.getElementById('logout-btn');
  const mobileLogoutBtn = document.getElementById('mobile-logout-btn');
  const mobileMenuBtn = document.getElementById('mobile-menu-btn');
  const mobileMenu = document.getElementById('mobile-menu');
  const iconOpen = document.getElementById('icon-open');
  const iconClose = document.getElementById('icon-close');

  // ---- Mobile Menu Toggle ----
  if (mobileMenuBtn && mobileMenu) {
    mobileMenuBtn.addEventListener('click', function () {
      const isOpen = mobileMenu.classList.contains('open');
      mobileMenu.classList.toggle('open');
      if (iconOpen) iconOpen.classList.toggle('hidden');
      if (iconClose) iconClose.classList.toggle('hidden');
      mobileMenuBtn.setAttribute('aria-expanded', String(!isOpen));
    });
  }

  // ---- Profile Dropdown Toggle ----
  if (profileToggle && dropdownMenu) {
    profileToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      const isOpen = dropdownMenu.classList.contains('open');
      dropdownMenu.classList.toggle('open');
      if (dropdownArrow) {
        dropdownArrow.style.transform = isOpen ? '' : 'rotate(180deg)';
      }
      profileToggle.setAttribute('aria-expanded', String(!isOpen));
    });

    // Close dropdown on outside click
    document.addEventListener('click', function () {
      dropdownMenu.classList.remove('open');
      if (dropdownArrow) dropdownArrow.style.transform = '';
      profileToggle.setAttribute('aria-expanded', 'false');
    });

    // Prevent closing when clicking inside the dropdown
    dropdownMenu.addEventListener('click', function (e) {
      e.stopPropagation();
    });
  }

  // ---- Logout Handler ----
  function handleLogout() {
    fetch(basePath + 'api/logout.php', { method: 'POST', credentials: 'same-origin' })
      .then(function () {
        window.location.href = basePath + 'index.html';
      })
      .catch(function () {
        window.location.href = basePath + 'index.html';
      });
  }

  if (logoutBtn) logoutBtn.addEventListener('click', handleLogout);
  if (mobileLogoutBtn) mobileLogoutBtn.addEventListener('click', handleLogout);

  // ---- Fetch Authenticated User ----
  fetch(basePath + 'api/current-user.php', { credentials: 'same-origin' })
    .then(function (response) {
      if (!response.ok) throw new Error('guest');
      return response.json();
    })
    .then(function (data) {
      if (!['user', 'mechanic', 'admin'].includes(data.role)) {
        showGuestState();
        return;
      }

      const isMechanic = data.role === 'mechanic';
      const isAdmin = data.role === 'admin';
      const dashboardPath = isAdmin
        ? basePath + 'admin/index.php'
        : (isMechanic ? basePath + 'mechanic/dashboard.html' : basePath + 'user/dashboard.html');
      const profilePath = isAdmin
        ? basePath + 'admin/index.php'
        : (isMechanic ? basePath + 'mechanic/profile.html' : basePath + 'user/profile.html');
      const displayName = data.name || (isAdmin ? 'Admin' : (isMechanic ? 'Mechanic' : 'User'));

      const defaultPhoto = isMechanic
        ? basePath + 'assets/images/placeholders/default-mechnaic-profile.png'
        : basePath + 'assets/images/placeholders/default-user-profile.png';
      const userPhoto = (data.photo && typeof data.photo === 'string' && data.photo.trim() !== '')
        ? data.photo.trim()
        : defaultPhoto;

      /**
       * Render ONE clean circular avatar image.
       * - Finds the wrapper <span> that holds the <i> icon (or existing avatar).
       * - Removes ALL existing stale images and the icon.
       * - Inserts a single <img> with overflow-hidden, fully contained.
       */
      function renderAvatarImage(iconEl, imgId) {
        if (!iconEl) return;

        // Remove any previously injected avatar with this id (prevents duplicates on hot reload)
        var old = document.getElementById(imgId);
        if (old) old.remove();

        // The container is the <span> wrapping the <i> icon
        var container = (iconEl.tagName && iconEl.tagName.toLowerCase() === 'i')
          ? iconEl.parentElement
          : iconEl;

        if (!container) return;

        // Clear the container completely (removes icon + any stale img)
        container.innerHTML = '';

        // Style the container: fixed size circle, no overflow, no background brand color
        container.style.cssText = 'width:32px;height:32px;min-width:32px;border-radius:9999px;overflow:hidden;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:#f1f5f9;border:1.5px solid #e2e8f0;';

        // Create the single avatar image
        var img = document.createElement('img');
        img.id = imgId;
        img.alt = displayName;
        img.style.cssText = 'width:100%;height:100%;object-fit:cover;display:block;border-radius:9999px;';
        img.src = userPhoto;
        img.onerror = function () {
          if (this.src !== defaultPhoto) {
            this.src = defaultPhoto;
          }
        };
        container.appendChild(img);
      }

      // ---- Desktop Avatar & Name ----
      if (accountName) accountName.textContent = displayName;
      renderAvatarImage(accountIcon, 'account-avatar');

      if (dashboardLink) dashboardLink.href = dashboardPath;
      if (viewProfileLink) viewProfileLink.href = profilePath;

      // ---- Mobile Avatar & Name ----
      if (mobileAccountName) mobileAccountName.textContent = displayName;
      renderAvatarImage(mobileAccountIcon, 'mobile-account-avatar');

      if (mobileDashboardLink) mobileDashboardLink.href = dashboardPath;
      if (mobileViewProfileLink) mobileViewProfileLink.href = profilePath;

      // Show authenticated state
      if (guestActions) {
        guestActions.classList.add('hidden');
        guestActions.classList.remove('md:flex');
      }
      if (accountActions) {
        accountActions.classList.remove('hidden');
        accountActions.classList.add('md:flex');
      }
      if (desktopLogin) desktopLogin.classList.add('hidden');
      if (mobileLogin) mobileLogin.classList.add('hidden');
      if (mobileSignup) mobileSignup.classList.add('hidden');
      if (mobileAccountSection) mobileAccountSection.classList.remove('hidden');
    })
    .catch(function () {
      showGuestState();
    });

  function showGuestState() {
    if (guestActions) {
      guestActions.classList.remove('hidden');
      guestActions.classList.add('md:flex');
    }
    if (accountActions) {
      accountActions.classList.add('hidden');
      accountActions.classList.remove('md:flex', 'flex');
    }
    if (desktopLogin) desktopLogin.classList.remove('hidden');
    if (mobileLogin) mobileLogin.classList.remove('hidden');
    if (mobileSignup) mobileSignup.classList.remove('hidden');
    if (mobileAccountSection) mobileAccountSection.classList.add('hidden');
  }
});
