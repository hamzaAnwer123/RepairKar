<?php
/**
 * Admin — Service Listings (Gigs) Management. Requires role='admin'.
 * Every service listing with search and an active/inactive filter.
 * Supports toggling a listing's visibility and deleting it. Actions
 * POST to api/admin/toggle-gig.php and api/admin/delete-gig.php,
 * which independently re-check role='admin' server-side.
 * All database values are escaped with htmlspecialchars() before
 * being echoed.
 */

$requiredRole = 'admin';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/db.php';

// ---- Filters (GET) ----
$statusFilter = (string) ($_GET['status'] ?? 'all');
if (!in_array($statusFilter, ['all', 'active', 'inactive'], true)) {
    $statusFilter = 'all';
}
$q = trim((string) ($_GET['q'] ?? ''));
$perPage = 15;
$page = max(1, (int) ($_GET['page'] ?? 1));

// ---- Counts for the status filter chips ----
$activeCount = (int) $pdo->query("SELECT COUNT(*) FROM gigs WHERE active = 1")->fetchColumn();
$totalGigs   = (int) $pdo->query("SELECT COUNT(*) FROM gigs")->fetchColumn();
$inactiveCount = $totalGigs - $activeCount;

// ---- WHERE clauses (shared by the count and the list query) ----
$where  = [];
$params = [];
if ($statusFilter === 'active') {
    $where[] = "g.active = 1";
} elseif ($statusFilter === 'inactive') {
    $where[] = "g.active = 0";
}
if ($q !== '') {
    // Distinct placeholder per column — PDO with native prepared
    // statements (EMULATE_PREPARES=false) rejects a named parameter
    // used more than once in the same statement (HY093).
    $like = '%' . $q . '%';
    $where[] = "(g.title LIKE :q_title OR m.shop_name LIKE :q_shop OR mu.name LIKE :q_owner)";
    $params['q_title'] = $like;
    $params['q_shop']  = $like;
    $params['q_owner'] = $like;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ---- Pagination ----
$countStmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM gigs g
     JOIN mechanics m ON m.id = g.mechanic_id
     JOIN users mu ON mu.id = m.user_id
     {$whereSql}"
);
$countStmt->execute($params);
$totalRows  = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

$listStmt = $pdo->prepare(
    "SELECT g.id, g.title, g.description, g.price_min, g.price_max,
            g.photo_path, g.active, g.created_at,
            m.shop_name, mu.name AS owner_name
     FROM gigs g
     JOIN mechanics m ON m.id = g.mechanic_id
     JOIN users mu ON mu.id = m.user_id
     {$whereSql}
     ORDER BY g.created_at DESC
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $key => $value) {
    $listStmt->bindValue(':' . $key, $value);
}
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$gigs = $listStmt->fetchAll();

$adminPageTitle  = 'Services — RepairKar Admin';
$adminActivePage = 'gigs';
require_once __DIR__ . '/../includes/admin-head.php';
?>
<body class="bg-slate-50 text-slate-800 antialiased">

<div class="md:flex md:min-h-screen">

<?php require_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <!-- ================= MAIN ================= -->
  <div class="flex-1 min-w-0">

    <header class="sticky top-0 z-30 bg-white border-b border-slate-100">
      <div class="flex items-center gap-3 px-4 md:px-8 py-4">
        <button type="button" id="open-drawer-btn" aria-label="Open menu" class="md:hidden w-9 h-9 rounded-full hover:bg-slate-100 flex items-center justify-center shrink-0">
          <i class="fa-solid fa-bars text-slate-600" aria-hidden="true"></i>
        </button>
        <h1 class="flex-1 text-lg md:text-2xl font-extrabold text-slate-900">Services</h1>
        <span class="text-xs text-slate-400 hidden sm:inline">Logged in as <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></span>
      </div>
    </header>

    <main class="px-4 md:px-8 py-5 md:py-6 space-y-5 pb-10">

      <!-- ---- Status filter chips + search ---- -->
      <div class="fade-in-up flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex flex-wrap gap-2">
          <?php
            $chips = [
                ['all',      'All',       $totalGigs],
                ['active',   'Active',    $activeCount],
                ['inactive', 'Hidden',    $inactiveCount],
            ];
          ?>
          <?php foreach ($chips as [$chipStatus, $chipLabel, $chipCount]): ?>
            <?php $chipActive = ($statusFilter === $chipStatus); ?>
            <a href="gigs.php?status=<?= $chipStatus ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>"
               class="rounded-full px-3.5 py-1.5 text-xs font-semibold border transition <?= $chipActive ? 'bg-brand text-white border-brand' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300' ?>">
              <?= $chipLabel ?> <span class="<?= $chipActive ? 'text-white/80' : 'text-slate-400' ?>"><?= number_format($chipCount) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
        <form method="get" action="gigs.php" class="sm:ml-auto flex gap-2">
          <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
          <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search service or shop…"
                 class="w-full sm:w-64 rounded-xl border border-slate-200 px-4 py-2 text-sm focus:ring-2 focus:ring-brand focus:outline-none">
          <button type="submit" class="btn-tap rounded-xl bg-brand hover:bg-brand-dark text-white text-sm font-semibold px-4" aria-label="Search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
          </button>
        </form>
      </div>

      <?php if (empty($gigs)): ?>

        <div class="fade-in-up rounded-2xl bg-white border border-slate-100 p-12 text-center">
          <i class="fa-solid fa-screwdriver-wrench text-3xl text-slate-300" aria-hidden="true"></i>
          <p class="mt-3 text-sm font-medium text-slate-600">No service listings found.</p>
          <p class="text-xs text-slate-400 mt-1">Try a different search or status filter.</p>
        </div>

      <?php else: ?>

        <div class="fade-in-up-d1 rounded-2xl bg-white border border-slate-100 overflow-hidden">
          <div class="px-4 py-3 border-b border-slate-100">
            <p class="text-xs text-slate-400"><?= number_format($totalRows) ?> listing<?= $totalRows === 1 ? '' : 's' ?> · page <?= (int) $page ?> of <?= (int) $totalPages ?></p>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left text-[11px] text-slate-400 uppercase tracking-wide border-b border-slate-100">
                  <th class="px-4 py-3 font-medium">Service</th>
                  <th class="px-4 py-3 font-medium">Shop</th>
                  <th class="px-4 py-3 font-medium">Price Range</th>
                  <th class="px-4 py-3 font-medium">Created</th>
                  <th class="px-4 py-3 font-medium text-center">Visibility</th>
                  <th class="px-4 py-3 font-medium text-right">Actions</th>
                </tr>
              </thead>
              <tbody id="gigs-tbody" class="divide-y divide-slate-50">
                <?php foreach ($gigs as $i => $g): ?>
                  <tr class="row-enter row-hover row-clear hover:bg-slate-50" data-gig-id="<?= (int) $g['id'] ?>" data-gig-active="<?= (int) $g['active'] ?>" style="animation-delay: <?= $i * 30 ?>ms">
                    <td class="px-4 py-3">
                      <div class="flex items-center gap-2.5">
                        <?php if (!empty($g['photo_path'])): ?>
                          <img src="<?= htmlspecialchars($g['photo_path']) ?>" alt="" width="36" height="36" class="w-9 h-9 rounded-lg object-cover bg-slate-100 shrink-0">
                        <?php else: ?>
                          <span class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-wrench text-slate-400 text-xs" aria-hidden="true"></i>
                          </span>
                        <?php endif; ?>
                        <span class="font-medium text-slate-900 truncate max-w-[220px]"><?= htmlspecialchars($g['title']) ?></span>
                      </div>
                    </td>
                    <td class="px-4 py-3">
                      <span class="block text-slate-600 truncate max-w-[160px]"><?= htmlspecialchars($g['shop_name']) ?></span>
                      <span class="block text-xs text-slate-400"><?= htmlspecialchars($g['owner_name']) ?></span>
                    </td>
                    <td class="px-4 py-3 text-slate-600 whitespace-nowrap">Rs <?= number_format((float) $g['price_min']) ?> – <?= number_format((float) $g['price_max']) ?></td>
                    <td class="px-4 py-3 text-xs text-slate-400 whitespace-nowrap"><?= htmlspecialchars(date('M j, Y', strtotime($g['created_at']))) ?></td>
                    <td class="px-4 py-3 text-center">
                      <button type="button" class="toggle-gig-btn btn-tap relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition focus:outline-none <?= (int) $g['active'] === 1 ? 'bg-brand' : 'bg-slate-300' ?>"
                              data-gig-id="<?= (int) $g['id'] ?>" role="switch" aria-checked="<?= (int) $g['active'] === 1 ? 'true' : 'false' ?>" aria-label="Toggle listing visibility">
                        <span class="toggle-knob inline-block h-4 w-4 transform rounded-full bg-white shadow transition <?= (int) $g['active'] === 1 ? 'translate-x-[18px]' : 'translate-x-0.5' ?>"></span>
                      </button>
                      <span class="toggle-label block text-[10px] font-semibold uppercase tracking-wide mt-1 <?= (int) $g['active'] === 1 ? 'text-brand' : 'text-slate-400' ?>">
                        <?= (int) $g['active'] === 1 ? 'Visible' : 'Hidden' ?>
                      </span>
                    </td>
                    <td class="px-4 py-3">
                      <div class="flex items-center justify-end">
                        <button type="button" class="delete-gig-btn btn-tap rounded-lg border border-red-100 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-semibold px-3 py-1.5"
                                data-gig-id="<?= (int) $g['id'] ?>" data-gig-title="<?= htmlspecialchars($g['title']) ?>">
                          <i class="fa-regular fa-trash-can" aria-hidden="true"></i> Delete
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <?php if ($totalPages > 1): ?>
            <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between">
              <?php if ($page > 1): ?>
                <a href="gigs.php?status=<?= $statusFilter ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>&page=<?= $page - 1 ?>" class="btn-tap rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                  <i class="fa-solid fa-chevron-left" aria-hidden="true"></i> Previous
                </a>
              <?php else: ?>
                <span class="rounded-lg border border-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-300">Previous</span>
              <?php endif; ?>
              <span class="text-xs text-slate-400">Page <?= (int) $page ?> of <?= (int) $totalPages ?></span>
              <?php if ($page < $totalPages): ?>
                <a href="gigs.php?status=<?= $statusFilter ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>&page=<?= $page + 1 ?>" class="btn-tap rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                  Next <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </a>
              <?php else: ?>
                <span class="rounded-lg border border-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-300">Next</span>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>

      <?php endif; ?>

    </main>
  </div>
</div>

<!-- ================= DELETE CONFIRM MODAL ================= -->
<div id="delete-modal" class="hidden modal-backdrop-in fixed inset-0 z-50 bg-black/40 flex items-end sm:items-center justify-center p-4">
  <div class="modal-in bg-white rounded-2xl w-full max-w-sm p-5">
    <h2 class="text-base font-bold text-slate-900">Delete this listing?</h2>
    <p class="text-sm text-slate-500 mt-1.5">
      <span id="delete-gig-title" class="font-semibold text-slate-700"></span> will be removed permanently.
      Existing bookings that referenced it keep their history.
    </p>
    <p class="text-xs text-red-500 mt-2"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> This action cannot be undone.</p>
    <div class="flex gap-3 mt-4">
      <button type="button" id="delete-modal-cancel" class="btn-tap flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
      <button type="button" id="delete-modal-confirm" class="btn-tap flex-1 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2.5">
        <span id="delete-confirm-text">Delete Listing</span>
      </button>
    </div>
  </div>
</div>

<!-- ================= TOAST ================= -->
<div id="action-toast" class="hidden toast-in fixed bottom-5 left-1/2 -translate-x-1/2 z-50 bg-slate-900 text-white text-sm font-medium rounded-xl px-4 py-2.5 flex items-center gap-2">
  <i id="toast-icon" class="fa-solid fa-circle-check text-green-400" aria-hidden="true"></i>
  <span id="toast-text">Done</span>
</div>

<script>
(function () {
  const tbody              = document.getElementById('gigs-tbody');
  const deleteModal        = document.getElementById('delete-modal');
  const deleteGigTitle     = document.getElementById('delete-gig-title');
  const deleteModalCancel  = document.getElementById('delete-modal-cancel');
  const deleteModalConfirm = document.getElementById('delete-modal-confirm');
  const deleteConfirmText  = document.getElementById('delete-confirm-text');
  const actionToast        = document.getElementById('action-toast');
  const toastIcon          = document.getElementById('toast-icon');
  const toastText          = document.getElementById('toast-text');

  let pendingDeleteId = null;

  if (!tbody) return; // empty state — nothing else to wire up

  function showToast(message, isError) {
    toastText.textContent = message;
    toastIcon.className = 'fa-solid ' + (isError ? 'fa-circle-xmark text-red-400' : 'fa-circle-check text-green-400');
    actionToast.classList.remove('hidden');
    actionToast.classList.add('toast-in');
    setTimeout(function () { actionToast.classList.add('hidden'); }, 2500);
  }

  function removeRow(id) {
    const row = tbody.querySelector('[data-gig-id="' + id + '"]');
    if (!row) return;
    row.style.opacity = '0';
    row.style.maxHeight = row.offsetHeight + 'px';
    requestAnimationFrame(function () {
      row.style.maxHeight = '0px';
      row.style.overflow = 'hidden';
    });
    setTimeout(function () { row.remove(); }, 350);
  }

  // ---- Visibility toggle ----
  document.querySelectorAll('.toggle-gig-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const gigId = btn.dataset.gigId;
      btn.disabled = true;

      fetch('../api/admin/toggle-gig.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ gig_id: Number(gigId) })
      })
        .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
        .then(function (result) {
          if (!result.ok) throw new Error(result.data && result.data.message ? result.data.message : 'Could not update the listing.');
          const row = tbody.querySelector('[data-gig-id="' + gigId + '"]');
          if (row) {
            const nowActive = result.data.active ? 1 : 0;
            row.dataset.gigActive = nowActive;
            btn.className = 'toggle-gig-btn btn-tap relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition focus:outline-none ' + (nowActive ? 'bg-brand' : 'bg-slate-300');
            btn.setAttribute('aria-checked', nowActive ? 'true' : 'false');
            const knob = btn.querySelector('.toggle-knob');
            if (knob) knob.className = 'toggle-knob inline-block h-4 w-4 transform rounded-full bg-white shadow transition ' + (nowActive ? 'translate-x-[18px]' : 'translate-x-0.5');
            const label = row.querySelector('.toggle-label');
            if (label) {
              label.textContent = nowActive ? 'Visible' : 'Hidden';
              label.className = 'toggle-label block text-[10px] font-semibold uppercase tracking-wide mt-1 ' + (nowActive ? 'text-brand' : 'text-slate-400');
            }
          }
          showToast(result.data.active ? 'Listing is now visible' : 'Listing is now hidden', false);
        })
        .catch(function (err) {
          showToast(err.message, true);
        })
        .finally(function () {
          btn.disabled = false;
        });
    });
  });

  // ---- Delete ----
  document.querySelectorAll('.delete-gig-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      pendingDeleteId = btn.dataset.gigId;
      deleteGigTitle.textContent = btn.dataset.gigTitle; // untrusted — textContent only
      deleteModal.classList.remove('hidden');
    });
  });

  function closeModal() { deleteModal.classList.add('hidden'); pendingDeleteId = null; }
  deleteModalCancel.addEventListener('click', closeModal);
  deleteModal.addEventListener('click', function (e) { if (e.target === deleteModal) closeModal(); });

  deleteModalConfirm.addEventListener('click', function () {
    if (!pendingDeleteId) return;
    deleteModalConfirm.disabled = true;
    deleteConfirmText.textContent = 'Deleting...';

    fetch('../api/admin/delete-gig.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ gig_id: Number(pendingDeleteId) })
    })
      .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
      .then(function (result) {
        if (!result.ok) throw new Error(result.data && result.data.message ? result.data.message : 'Could not delete the listing.');
        removeRow(pendingDeleteId);
        closeModal();
        showToast('Listing deleted', false);
      })
      .catch(function (err) {
        closeModal();
        showToast(err.message, true);
      })
      .finally(function () {
        deleteModalConfirm.disabled = false;
        deleteConfirmText.textContent = 'Delete Listing';
      });
  });

})();
</script>

</body>
</html>
