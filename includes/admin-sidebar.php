<?php
/**
 * Shared sidebar (desktop) + mobile drawer for every admin page.
 * The including page must set $adminActivePage BEFORE requiring this
 * file — one of: dashboard | mechanics | users | bookings | reviews |
 * gigs | messages. Badge counts are computed here so they show up
 * consistently on every page.
 */
$adminActivePage = isset($adminActivePage) ? $adminActivePage : '';

require_once __DIR__ . '/functions.php';
ensureContactMessagesTable($pdo);

$pendingVerifications = (int) $pdo->query("SELECT COUNT(*) FROM mechanics WHERE verified = 0 AND rejected = 0")->fetchColumn();
$newMessagesCount     = (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn();

$adminNavItems = [
    ['dashboard', 'index.php',     'fa-table-cells-large',  'Dashboard', null],
    ['mechanics', 'mechanics.php', 'fa-user-check',         'Mechanics', $pendingVerifications],
    ['users',     'users.php',     'fa-users',              'Users',     null],
    ['bookings',  'bookings.php',  'fa-calendar-check',     'Bookings',  null],
    ['reviews',   'reviews.php',   'fa-star',               'Reviews',   null],
    ['gigs',      'gigs.php',      'fa-screwdriver-wrench', 'Services',  null],
    ['messages',  'messages.php',  'fa-envelope',           'Messages',  $newMessagesCount],
];
?>
<!-- ================= SIDEBAR (desktop) ================= -->
<aside class="hidden md:flex md:flex-col md:w-60 md:shrink-0 bg-slate-900 text-slate-300 md:sticky md:top-0 md:h-screen">
  <div class="flex items-center gap-2 px-5 py-5 border-b border-slate-800">
    <img src="../assets/images/logo/logo.png" alt="RepairKar" class="h-6 w-auto">
    <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Admin</span>
  </div>
  <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto" aria-label="Admin navigation">
    <?php foreach ($adminNavItems as $item): ?>
      <?php
        [$itemKey, $itemHref, $itemIcon, $itemLabel, $itemBadge] = $item;
        $itemActive = ($adminActivePage === $itemKey);
      ?>
      <a href="<?= $itemHref ?>" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm <?= $itemActive ? 'font-semibold bg-white/10 text-white' : 'font-medium text-slate-400 hover:bg-white/5 hover:text-white' ?>" <?= $itemActive ? 'aria-current="page"' : '' ?>>
        <i class="fa-solid <?= $itemIcon ?> w-4 text-center" aria-hidden="true"></i> <?= $itemLabel ?>
        <?php if ($itemBadge > 0): ?>
          <span class="badge-pop ml-auto text-[10px] font-bold bg-accent text-white rounded-full px-2 py-0.5"><?= (int) $itemBadge ?></span>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="px-3 py-4 border-t border-slate-800">
    <button type="button" id="admin-logout-btn" class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-400 hover:bg-white/5 hover:text-red-400">
      <i class="fa-solid fa-right-from-bracket w-4 text-center" aria-hidden="true"></i> Logout
    </button>
  </div>
</aside>

<!-- ================= MOBILE DRAWER ================= -->
<div id="mobile-drawer-backdrop" class="hidden md:hidden backdrop-in fixed inset-0 z-50 bg-black/40"></div>
<aside id="mobile-drawer" class="hidden md:hidden drawer-in fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-slate-300 flex flex-col">
  <div class="flex items-center justify-between px-4 py-4 border-b border-slate-800">
    <div class="flex items-center gap-2">
      <img src="../assets/images/logo/logo.png" alt="RepairKar" class="h-6 w-auto">
      <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Admin</span>
    </div>
    <button type="button" id="close-drawer-btn" aria-label="Close menu" class="w-8 h-8 rounded-full hover:bg-white/10 flex items-center justify-center">
      <i class="fa-solid fa-xmark text-slate-300" aria-hidden="true"></i>
    </button>
  </div>
  <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto" aria-label="Admin navigation (mobile)">
    <?php foreach ($adminNavItems as $item): ?>
      <?php
        [$itemKey, $itemHref, $itemIcon, $itemLabel, $itemBadge] = $item;
        $itemActive = ($adminActivePage === $itemKey);
      ?>
      <a href="<?= $itemHref ?>" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm <?= $itemActive ? 'font-semibold bg-white/10 text-white' : 'font-medium text-slate-400' ?>" <?= $itemActive ? 'aria-current="page"' : '' ?>>
        <i class="fa-solid <?= $itemIcon ?> w-4 text-center" aria-hidden="true"></i> <?= $itemLabel ?>
        <?php if ($itemBadge > 0): ?>
          <span class="ml-auto text-[10px] font-bold bg-accent text-white rounded-full px-2 py-0.5"><?= (int) $itemBadge ?></span>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="px-3 py-4 border-t border-slate-800">
    <button type="button" id="admin-logout-btn-mobile" class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-400">
      <i class="fa-solid fa-right-from-bracket w-4 text-center" aria-hidden="true"></i> Logout
    </button>
  </div>

<script>
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var openBtn   = document.getElementById('open-drawer-btn');
    var closeBtn  = document.getElementById('close-drawer-btn');
    var drawer    = document.getElementById('mobile-drawer');
    var backdrop  = document.getElementById('mobile-drawer-backdrop');

    function openDrawer()  { drawer.classList.remove('hidden'); backdrop.classList.remove('hidden'); }
    function closeDrawer() { drawer.classList.add('hidden'); backdrop.classList.add('hidden'); }
    if (openBtn)   openBtn.addEventListener('click', openDrawer);
    if (closeBtn)  closeBtn.addEventListener('click', closeDrawer);
    if (backdrop)  backdrop.addEventListener('click', closeDrawer);

    function logout() {
      fetch('../api/logout.php', { method: 'POST', credentials: 'same-origin' })
        .finally(function () { window.location.href = '../login.html'; });
    }
    var logoutBtn       = document.getElementById('admin-logout-btn');
    var logoutBtnMobile = document.getElementById('admin-logout-btn-mobile');
    if (logoutBtn)       logoutBtn.addEventListener('click', logout);
    if (logoutBtnMobile) logoutBtnMobile.addEventListener('click', logout);
  });
})();
</script>

</aside>
