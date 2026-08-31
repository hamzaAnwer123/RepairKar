<?php
/**
 * Admin — User Management. Requires role='admin'.
 * Lists every account (customers, mechanics, admins) with search, a
 * role filter and pagination. All database values are escaped with
 * htmlspecialchars() before being echoed — names, emails and cities
 * are untrusted content. The Delete action POSTs to
 * api/admin/delete-user.php, which independently re-checks
 * role='admin' server-side.
 */

$requiredRole = 'admin';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/db.php';

// ---- Filters (GET) ----
$allowedRoles = ['user', 'mechanic', 'admin'];
$roleFilter = (string) ($_GET['role'] ?? 'all');
if (!in_array($roleFilter, array_merge(['all'], $allowedRoles), true)) {
    $roleFilter = 'all';
}
$q = trim((string) ($_GET['q'] ?? ''));
$perPage = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));

// ---- Counts for the role filter chips ----
$roleCounts = ['user' => 0, 'mechanic' => 0, 'admin' => 0];
foreach ($pdo->query("SELECT role, COUNT(*) AS c FROM users GROUP BY role") as $row) {
    if (isset($roleCounts[$row['role']])) $roleCounts[$row['role']] = (int) $row['c'];
}
$totalAccounts = $roleCounts['user'] + $roleCounts['mechanic'] + $roleCounts['admin'];

// ---- WHERE clauses (shared by the count and the list query) ----
$where  = [];
$params = [];
if ($roleFilter !== 'all') {
    $where[] = "u.role = :role";
    $params['role'] = $roleFilter;
}
if ($q !== '') {
    // Distinct placeholder per column — PDO with native prepared
    // statements (EMULATE_PREPARES=false) rejects a named parameter
    // used more than once in the same statement (HY093).
    $like = '%' . $q . '%';
    $where[] = "(u.name LIKE :q_name OR u.phone LIKE :q_phone OR u.email LIKE :q_email OR u.city LIKE :q_city)";
    $params['q_name']  = $like;
    $params['q_phone'] = $like;
    $params['q_email'] = $like;
    $params['q_city']  = $like;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ---- Pagination ----
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u {$whereSql}");
$countStmt->execute($params);
$totalRows  = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

$listStmt = $pdo->prepare(
    "SELECT u.id, u.name, u.phone, u.email, u.role, u.city, u.photo_path,
            u.locked_until, u.created_at,
            m.shop_name,
            (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id) AS bookings_made,
            (SELECT COUNT(*) FROM bookings b2
               JOIN mechanics m2 ON m2.id = b2.mechanic_id
               WHERE m2.user_id = u.id) AS bookings_served
     FROM users u
     LEFT JOIN mechanics m ON m.user_id = u.id
     {$whereSql}
     ORDER BY u.created_at DESC
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $key => $value) {
    $listStmt->bindValue(':' . $key, $value);
}
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$users = $listStmt->fetchAll();

$roleBadgeStyles = [
    'user'     => 'bg-blue-50 text-blue-600',
    'mechanic' => 'bg-brand-light text-brand',
    'admin'    => 'bg-purple-50 text-purple-600',
];

$adminPageTitle  = 'Users — RepairKar Admin';
$adminActivePage = 'users';
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
        <h1 class="flex-1 text-lg md:text-2xl font-extrabold text-slate-900">Users</h1>
        <span class="text-xs text-slate-400 hidden sm:inline">Logged in as <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></span>
      </div>
    </header>

    <main class="px-4 md:px-8 py-5 md:py-6 space-y-5 pb-10">

      <!-- ---- Role filter chips + search ---- -->
      <div class="fade-in-up flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex flex-wrap gap-2">
          <?php
            $chips = [
                ['all',      'All',       $totalAccounts],
                ['user',     'Customers', $roleCounts['user']],
                ['mechanic', 'Mechanics', $roleCounts['mechanic']],
                ['admin',    'Admins',    $roleCounts['admin']],
            ];
          ?>
          <?php foreach ($chips as [$chipRole, $chipLabel, $chipCount]): ?>
            <?php $chipActive = ($roleFilter === $chipRole); ?>
            <a href="users.php?role=<?= $chipRole ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>"
               class="rounded-full px-3.5 py-1.5 text-xs font-semibold border transition <?= $chipActive ? 'bg-brand text-white border-brand' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300' ?>">
              <?= $chipLabel ?> <span class="<?= $chipActive ? 'text-white/80' : 'text-slate-400' ?>"><?= number_format($chipCount) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
        <form method="get" action="users.php" class="sm:ml-auto flex gap-2">
          <input type="hidden" name="role" value="<?= htmlspecialchars($roleFilter) ?>">
          <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search name, phone, email, city…"
                 class="w-full sm:w-64 rounded-xl border border-slate-200 px-4 py-2 text-sm focus:ring-2 focus:ring-brand focus:outline-none">
          <button type="submit" class="btn-tap rounded-xl bg-brand hover:bg-brand-dark text-white text-sm font-semibold px-4" aria-label="Search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
          </button>
        </form>
      </div>

      <?php if (empty($users)): ?>

        <div class="fade-in-up rounded-2xl bg-white border border-slate-100 p-12 text-center">
          <i class="fa-regular fa-user text-3xl text-slate-300" aria-hidden="true"></i>
          <p class="mt-3 text-sm font-medium text-slate-600">No accounts found.</p>
          <p class="text-xs text-slate-400 mt-1">Try a different search or role filter.</p>
        </div>

      <?php else: ?>

        <div class="fade-in-up-d1 rounded-2xl bg-white border border-slate-100 overflow-hidden">
          <div class="px-4 py-3 border-b border-slate-100">
            <p class="text-xs text-slate-400"><?= number_format($totalRows) ?> account<?= $totalRows === 1 ? '' : 's' ?> · page <?= (int) $page ?> of <?= (int) $totalPages ?></p>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left text-[11px] text-slate-400 uppercase tracking-wide border-b border-slate-100">
                  <th class="px-4 py-3 font-medium">Account</th>
                  <th class="px-4 py-3 font-medium">Role</th>
                  <th class="px-4 py-3 font-medium">City</th>
                  <th class="px-4 py-3 font-medium text-center">Bookings</th>
                  <th class="px-4 py-3 font-medium">Joined</th>
                  <th class="px-4 py-3 font-medium text-right">Actions</th>
                </tr>
              </thead>
              <tbody id="users-tbody" class="divide-y divide-slate-50">
                <?php foreach ($users as $i => $u): ?>
                  <?php $isLocked = !empty($u['locked_until']) && strtotime($u['locked_until']) > time(); ?>
                  <tr class="row-enter row-hover row-clear hover:bg-slate-50" data-user-id="<?= (int) $u['id'] ?>" style="animation-delay: <?= $i * 30 ?>ms">
                    <td class="px-4 py-3">
                      <div class="flex items-center gap-2.5">
                        <?php if (!empty($u['photo_path'])): ?>
                          <img src="<?= htmlspecialchars($u['photo_path']) ?>" alt="" width="32" height="32" class="w-8 h-8 rounded-full object-cover bg-slate-100 shrink-0">
                        <?php else: ?>
                          <span class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center shrink-0 text-xs font-bold text-slate-500"><?= htmlspecialchars(mb_strtoupper(mb_substr($u['name'], 0, 1))) ?></span>
                        <?php endif; ?>
                        <span class="min-w-0">
                          <span class="flex items-center gap-1.5">
                            <span class="block font-medium text-slate-900 truncate"><?= htmlspecialchars($u['name']) ?></span>
                            <?php if ($isLocked): ?>
                              <span class="badge-pop text-[10px] font-bold bg-red-50 text-red-500 rounded-full px-2 py-0.5 shrink-0" title="Temporarily locked after failed logins">Locked</span>
                            <?php endif; ?>
                          </span>
                          <span class="block text-xs text-slate-400 truncate"><?= htmlspecialchars($u['phone'] ?: ($u['email'] ?: '—')) ?></span>
                        </span>
                      </div>
                    </td>
                    <td class="px-4 py-3">
                      <span class="text-[11px] font-semibold rounded-full px-2.5 py-1 <?= $roleBadgeStyles[$u['role']] ?? 'bg-slate-100 text-slate-500' ?>">
                        <?= htmlspecialchars(ucfirst($u['role'])) ?>
                      </span>
                      <?php if ($u['role'] === 'mechanic' && !empty($u['shop_name'])): ?>
                        <span class="block text-xs text-slate-400 mt-1 truncate max-w-[160px]"><?= htmlspecialchars($u['shop_name']) ?></span>
                      <?php endif; ?>
                    </td>

                    <td class="px-4 py-3 text-slate-600"><?= $u['city'] ? htmlspecialchars($u['city']) : '<span class="text-slate-300">—</span>' ?></td>
                    <td class="px-4 py-3 text-center text-slate-600">
                      <?= (int) $u['bookings_made'] ?> placed
                      <?php if ((int) $u['bookings_served'] > 0): ?>
                        <span class="block text-xs text-slate-400"><?= (int) $u['bookings_served'] ?> served</span>
                      <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-400"><?= htmlspecialchars(date('M j, Y', strtotime($u['created_at']))) ?></td>
                    <td class="px-4 py-3">
                      <div class="flex items-center justify-end">
                        <?php if ($u['role'] === 'admin'): ?>
                          <span class="text-xs text-slate-300">Protected</span>
                        <?php else: ?>
                          <button type="button" class="delete-user-btn btn-tap rounded-lg border border-red-100 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-semibold px-3 py-1.5"
                                  data-user-id="<?= (int) $u['id'] ?>" data-user-name="<?= htmlspecialchars($u['name']) ?>">
                            <i class="fa-regular fa-trash-can" aria-hidden="true"></i> Delete
                          </button>
                        <?php endif; ?>
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
                <a href="users.php?role=<?= $roleFilter ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>&page=<?= $page - 1 ?>" class="btn-tap rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                  <i class="fa-solid fa-chevron-left" aria-hidden="true"></i> Previous
                </a>
              <?php else: ?>
                <span class="rounded-lg border border-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-300">Previous</span>
              <?php endif; ?>
              <span class="text-xs text-slate-400">Page <?= (int) $page ?> of <?= (int) $totalPages ?></span>
              <?php if ($page < $totalPages): ?>
                <a href="users.php?role=<?= $roleFilter ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>&page=<?= $page + 1 ?>" class="btn-tap rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">
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
    <h2 class="text-base font-bold text-slate-900">Delete this account?</h2>
    <p class="text-sm text-slate-500 mt-1.5">
      This permanently removes <span id="delete-user-name" class="font-semibold text-slate-700"></span>
      along with their bookings, messages, reviews and — for mechanics — their shop profile, services and photos.
    </p>
    <p class="text-xs text-red-500 mt-2"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> This action cannot be undone.</p>
    <div class="flex gap-3 mt-4">
      <button type="button" id="delete-modal-cancel" class="btn-tap flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
      <button type="button" id="delete-modal-confirm" class="btn-tap flex-1 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2.5">
        <span id="delete-confirm-text">Delete Account</span>
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
  const tbody            = document.getElementById('users-tbody');
  const deleteModal      = document.getElementById('delete-modal');
  const deleteUserName   = document.getElementById('delete-user-name');
  const deleteModalCancel  = document.getElementById('delete-modal-cancel');
  const deleteModalConfirm = document.getElementById('delete-modal-confirm');
  const deleteConfirmText  = document.getElementById('delete-confirm-text');
  const actionToast      = document.getElementById('action-toast');
  const toastIcon        = document.getElementById('toast-icon');
  const toastText        = document.getElementById('toast-text');

  let pendingDeleteId = null;

  if (!tbody) return; // empty state — nothing else to wire up

  function showToast(message, isError) {
    toastText.textContent = message;
    toastIcon.className = 'fa-solid ' + (isError ? 'fa-circle-xmark text-red-400' : 'fa-circle-check text-green-400');
    actionToast.classList.remove('hidden');
    actionToast.classList.add('toast-in');
    setTimeout(function () { actionToast.classList.add('hidden'); }, 2500);
  }

  // ---- Row removal (same animation pattern as mechanics.php) ----
  function removeRow(id) {
    const row = tbody.querySelector('[data-user-id="' + id + '"]');
    if (!row) return;
    row.style.opacity = '0';
    row.style.maxHeight = row.offsetHeight + 'px';
    requestAnimationFrame(function () {
      row.style.maxHeight = '0px';
      row.style.overflow = 'hidden';
    });
    setTimeout(function () { row.remove(); }, 350);
  }

  function callDeleteApi(id) {
    return fetch('../api/admin/delete-user.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ user_id: Number(id) })
    }).then(function (res) {
      return res.json().then(function (data) { return { ok: res.ok, data: data }; });
    });
  }

  document.querySelectorAll('.delete-user-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      pendingDeleteId = btn.dataset.userId;
      deleteUserName.textContent = btn.dataset.userName; // untrusted — textContent only
      deleteModal.classList.remove('hidden');
    });
  });

  deleteModalCancel.addEventListener('click', function () {
    deleteModal.classList.add('hidden');
    pendingDeleteId = null;
  });
  deleteModal.addEventListener('click', function (e) { if (e.target === deleteModal) { deleteModal.classList.add('hidden'); pendingDeleteId = null; } });

  deleteModalConfirm.addEventListener('click', function () {
    if (!pendingDeleteId) return;
    deleteModalConfirm.disabled = true;
    deleteConfirmText.textContent = 'Deleting...';

    callDeleteApi(pendingDeleteId)
      .then(function (result) {
        if (!result.ok) throw new Error(result.data && result.data.message ? result.data.message : 'Could not delete the account.');
        removeRow(pendingDeleteId);
        deleteModal.classList.add('hidden');
        showToast('Account deleted', false);
      })
      .catch(function (err) {
        deleteModal.classList.add('hidden');
        showToast(err.message, true);
      })
      .finally(function () {
        deleteModalConfirm.disabled = false;
        deleteConfirmText.textContent = 'Delete Account';
        pendingDeleteId = null;
      });
  });

})();
</script>

</body>
</html>

