<?php
/**
 * Admin — Bookings Management. Requires role='admin'.
 * Every booking on the platform with a status filter, search and
 * pagination; supports a detail modal and cancelling active bookings.
 * All database values are escaped with htmlspecialchars() before being
 * echoed. Actions POST to api/admin/* endpoints, which independently
 * re-check role='admin' server-side.
 */

$requiredRole = 'admin';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/db.php';

// ---- Filters (GET) ----
$allowedStatuses = ['pending', 'accepted', 'en_route', 'completed', 'cancelled'];
$statusFilter = (string) ($_GET['status'] ?? 'all');
if (!in_array($statusFilter, array_merge(['all'], $allowedStatuses), true)) {
    $statusFilter = 'all';
}
$q = trim((string) ($_GET['q'] ?? ''));
$perPage = 15;
$page = max(1, (int) ($_GET['page'] ?? 1));

// ---- Counts for the status filter chips ----
$statusCounts = array_fill_keys($allowedStatuses, 0);
foreach ($pdo->query("SELECT status, COUNT(*) AS c FROM bookings GROUP BY status") as $row) {
    if (isset($statusCounts[$row['status']])) $statusCounts[$row['status']] = (int) $row['c'];
}
$totalBookings = array_sum($statusCounts);

// ---- WHERE clauses (shared by the count and the list query) ----
$where  = [];
$params = [];
if ($statusFilter !== 'all') {
    $where[] = "b.status = :status";
    $params['status'] = $statusFilter;
}
if ($q !== '') {
    // Distinct placeholder per column — PDO with native prepared
    // statements (EMULATE_PREPARES=false) rejects a named parameter
    // used more than once in the same statement (HY093).
    $like = '%' . $q . '%';
    $where[] = "(u.name LIKE :q_user OR mu.name LIKE :q_mechanic OR m.shop_name LIKE :q_shop OR b.address LIKE :q_address)";
    $params['q_user']     = $like;
    $params['q_mechanic'] = $like;
    $params['q_shop']     = $like;
    $params['q_address']  = $like;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ---- Pagination ----
$countStmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM bookings b
     JOIN users u ON u.id = b.user_id
     JOIN mechanics m ON m.id = b.mechanic_id
     JOIN users mu ON mu.id = m.user_id
     {$whereSql}"
);
$countStmt->execute($params);
$totalRows  = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

$listStmt = $pdo->prepare(
    "SELECT b.id, b.status, b.address, b.scheduled_time, b.created_at,
            u.name AS user_name, u.phone AS user_phone,
            m.shop_name, m.category,
            mu.name AS mechanic_name,
            g.title AS gig_title
     FROM bookings b
     JOIN users u ON u.id = b.user_id
     JOIN mechanics m ON m.id = b.mechanic_id
     JOIN users mu ON mu.id = m.user_id
     LEFT JOIN gigs g ON g.id = b.gig_id
     {$whereSql}
     ORDER BY b.created_at DESC
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $key => $value) {
    $listStmt->bindValue(':' . $key, $value);
}
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$bookings = $listStmt->fetchAll();

$statusStyles = [
    'pending'   => 'bg-amber-50 text-amber-600',
    'accepted'  => 'bg-blue-50 text-blue-600',
    'en_route'  => 'bg-blue-50 text-blue-600',
    'completed' => 'bg-green-50 text-green-600',
    'cancelled' => 'bg-slate-100 text-slate-500',
];
$cancellableStatuses = ['pending', 'accepted', 'en_route'];

$adminPageTitle  = 'Bookings — RepairKar Admin';
$adminActivePage = 'bookings';
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
        <h1 class="flex-1 text-lg md:text-2xl font-extrabold text-slate-900">Bookings</h1>
        <span class="text-xs text-slate-400 hidden sm:inline">Logged in as <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></span>
      </div>
    </header>

    <main class="px-4 md:px-8 py-5 md:py-6 space-y-5 pb-10">

      <!-- ---- Status filter chips + search ---- -->
      <div class="fade-in-up flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex flex-wrap gap-2">
          <?php
            $chips = array_merge(
                [['all', 'All', $totalBookings]],
                array_map(
                    fn($s) => [$s, ucfirst(str_replace('_', ' ', $s)), $statusCounts[$s]],
                    $allowedStatuses
                )
            );
          ?>
          <?php foreach ($chips as [$chipStatus, $chipLabel, $chipCount]): ?>
            <?php $chipActive = ($statusFilter === $chipStatus); ?>
            <a href="bookings.php?status=<?= $chipStatus ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>"
               class="rounded-full px-3.5 py-1.5 text-xs font-semibold border transition <?= $chipActive ? 'bg-brand text-white border-brand' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300' ?>">
              <?= $chipLabel ?> <span class="<?= $chipActive ? 'text-white/80' : 'text-slate-400' ?>"><?= number_format($chipCount) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
        <form method="get" action="bookings.php" class="sm:ml-auto flex gap-2">
          <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
          <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search customer, mechanic, address…"
                 class="w-full sm:w-64 rounded-xl border border-slate-200 px-4 py-2 text-sm focus:ring-2 focus:ring-brand focus:outline-none">
          <button type="submit" class="btn-tap rounded-xl bg-brand hover:bg-brand-dark text-white text-sm font-semibold px-4" aria-label="Search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
          </button>
        </form>
      </div>

      <?php if (empty($bookings)): ?>

        <div class="fade-in-up rounded-2xl bg-white border border-slate-100 p-12 text-center">
          <i class="fa-regular fa-calendar text-3xl text-slate-300" aria-hidden="true"></i>
          <p class="mt-3 text-sm font-medium text-slate-600">No bookings found.</p>
          <p class="text-xs text-slate-400 mt-1">Try a different search or status filter.</p>
        </div>

      <?php else: ?>

        <div class="fade-in-up-d1 rounded-2xl bg-white border border-slate-100 overflow-hidden">
          <div class="px-4 py-3 border-b border-slate-100">
            <p class="text-xs text-slate-400"><?= number_format($totalRows) ?> booking<?= $totalRows === 1 ? '' : 's' ?> · page <?= (int) $page ?> of <?= (int) $totalPages ?></p>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left text-[11px] text-slate-400 uppercase tracking-wide border-b border-slate-100">
                  <th class="px-4 py-3 font-medium">#</th>
                  <th class="px-4 py-3 font-medium">Customer</th>
                  <th class="px-4 py-3 font-medium">Mechanic</th>
                  <th class="px-4 py-3 font-medium">Service</th>
                  <th class="px-4 py-3 font-medium">Status</th>
                  <th class="px-4 py-3 font-medium">Created</th>
                  <th class="px-4 py-3 font-medium text-right">Actions</th>
                </tr>
              </thead>
              <tbody id="bookings-tbody" class="divide-y divide-slate-50">
                <?php foreach ($bookings as $i => $b): ?>
                  <tr class="row-enter row-hover hover:bg-slate-50" data-booking-id="<?= (int) $b['id'] ?>" data-booking-status="<?= htmlspecialchars($b['status']) ?>" style="animation-delay: <?= $i * 30 ?>ms">
                    <td class="px-4 py-3 text-xs font-semibold text-slate-400">#<?= (int) $b['id'] ?></td>
                    <td class="px-4 py-3">
                      <span class="block font-medium text-slate-900 truncate"><?= htmlspecialchars($b['user_name']) ?></span>
                      <span class="block text-xs text-slate-400"><?= htmlspecialchars($b['user_phone'] ?: '—') ?></span>
                    </td>
                    <td class="px-4 py-3">
                      <span class="block font-medium text-slate-900 truncate"><?= htmlspecialchars($b['shop_name'] ?: ($b['mechanic_name'] ?: '—')) ?></span>
                      <span class="block text-xs text-slate-400"><?= htmlspecialchars($b['mechanic_name'] ?: '') ?></span>
                    </td>
                    <td class="px-4 py-3 text-slate-600 max-w-[180px] truncate"><?= htmlspecialchars($b['gig_title'] ?: ucfirst(str_replace('-', ' ', (string) $b['category']))) ?></td>
                    <td class="px-4 py-3">
                      <span class="booking-status-badge text-[11px] font-semibold rounded-full px-2.5 py-1 <?= $statusStyles[$b['status']] ?? 'bg-slate-100 text-slate-500' ?>">
                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $b['status']))) ?>
                      </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-400"><?= htmlspecialchars(date('M j, g:i A', strtotime($b['created_at']))) ?></td>
                    <td class="px-4 py-3">
                      <div class="flex items-center justify-end gap-2">
                        <button type="button" class="view-booking-btn btn-tap rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-xs font-semibold px-3 py-1.5"
                                data-booking-id="<?= (int) $b['id'] ?>">
                          <i class="fa-regular fa-eye" aria-hidden="true"></i> View
                        </button>
                        <?php if (in_array($b['status'], $cancellableStatuses, true)): ?>
                          <button type="button" class="cancel-booking-btn btn-tap rounded-lg border border-red-100 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-semibold px-3 py-1.5"
                                  data-booking-id="<?= (int) $b['id'] ?>">
                            <i class="fa-solid fa-ban" aria-hidden="true"></i> Cancel
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
                <a href="bookings.php?status=<?= $statusFilter ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>&page=<?= $page - 1 ?>" class="btn-tap rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                  <i class="fa-solid fa-chevron-left" aria-hidden="true"></i> Previous
                </a>
              <?php else: ?>
                <span class="rounded-lg border border-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-300">Previous</span>
              <?php endif; ?>
              <span class="text-xs text-slate-400">Page <?= (int) $page ?> of <?= (int) $totalPages ?></span>
              <?php if ($page < $totalPages): ?>
                <a href="bookings.php?status=<?= $statusFilter ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>&page=<?= $page + 1 ?>" class="btn-tap rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">
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

<!-- ================= BOOKING DETAIL MODAL ================= -->
<div id="detail-modal" class="hidden modal-backdrop-in fixed inset-0 z-50 bg-black/40 flex items-end sm:items-center justify-center p-4">
  <div class="modal-in bg-white rounded-2xl w-full max-w-lg max-h-[85vh] overflow-y-auto p-5">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-base font-bold text-slate-900">Booking Details</h2>
      <button type="button" id="detail-modal-close" aria-label="Close" class="w-8 h-8 rounded-full hover:bg-slate-100 flex items-center justify-center">
        <i class="fa-solid fa-xmark text-slate-500" aria-hidden="true"></i>
      </button>
    </div>
    <div id="detail-skeleton" class="space-y-3">
      <div class="h-4 w-1/2 rounded bg-slate-200 animate-pulse"></div>
      <div class="h-20 rounded bg-slate-200 animate-pulse"></div>
    </div>
    <div id="detail-content" class="hidden space-y-4">
      <div>
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Service</p>
        <p id="detail-service" class="text-sm font-semibold text-slate-800"></p>
        <p id="detail-description" class="text-sm text-slate-500 mt-0.5"></p>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Customer</p>
          <p id="detail-user" class="text-sm text-slate-700"></p>
          <p id="detail-user-phone" class="text-xs text-slate-400"></p>
        </div>
        <div>
          <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Mechanic</p>
          <p id="detail-mechanic" class="text-sm text-slate-700"></p>
          <p id="detail-mechanic-phone" class="text-xs text-slate-400"></p>
        </div>
      </div>
      <div>
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Service Address</p>
        <p id="detail-address" class="text-sm text-slate-700"></p>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Scheduled</p>
          <p id="detail-scheduled" class="text-sm text-slate-700"></p>
        </div>
        <div>
          <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Requested</p>
          <p id="detail-created" class="text-sm text-slate-700"></p>
        </div>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Status</p>
          <p><span id="detail-status" class="text-[11px] font-semibold rounded-full px-2.5 py-1 bg-slate-100 text-slate-500"></span></p>
        </div>
        <div>
          <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Price Range</p>
          <p id="detail-price" class="text-sm text-slate-700"></p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ================= CANCEL CONFIRM MODAL ================= -->
<div id="cancel-modal" class="hidden modal-backdrop-in fixed inset-0 z-50 bg-black/40 flex items-end sm:items-center justify-center p-4">
  <div class="modal-in bg-white rounded-2xl w-full max-w-sm p-5">
    <h2 class="text-base font-bold text-slate-900">Cancel this booking?</h2>
    <p class="text-sm text-slate-500 mt-1.5">The customer and the mechanic will see the booking as cancelled. This cannot be undone.</p>
    <div class="flex gap-3 mt-4">
      <button type="button" id="cancel-modal-cancel" class="btn-tap flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Keep It</button>
      <button type="button" id="cancel-modal-confirm" class="btn-tap flex-1 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2.5">
        <span id="cancel-confirm-text">Cancel Booking</span>
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
  const tbody           = document.getElementById('bookings-tbody');
  const statusStylesMap = {
    pending:   'bg-amber-50 text-amber-600',
    accepted:  'bg-blue-50 text-blue-600',
    en_route:  'bg-blue-50 text-blue-600',
    completed: 'bg-green-50 text-green-600',
    cancelled: 'bg-slate-100 text-slate-500'
  };
  const cancellable = ['pending', 'accepted', 'en_route'];

  const detailModal    = document.getElementById('detail-modal');
  const detailClose    = document.getElementById('detail-modal-close');
  const detailSkeleton = document.getElementById('detail-skeleton');
  const detailContent  = document.getElementById('detail-content');

  const cancelModal        = document.getElementById('cancel-modal');
  const cancelModalCancel  = document.getElementById('cancel-modal-cancel');
  const cancelModalConfirm = document.getElementById('cancel-modal-confirm');
  const cancelConfirmText  = document.getElementById('cancel-confirm-text');

  const actionToast = document.getElementById('action-toast');
  const toastIcon   = document.getElementById('toast-icon');
  const toastText   = document.getElementById('toast-text');

  let pendingCancelId = null;

  function showToast(message, isError) {
    toastText.textContent = message;
    toastIcon.className = 'fa-solid ' + (isError ? 'fa-circle-xmark text-red-400' : 'fa-circle-check text-green-400');
    actionToast.classList.remove('hidden');
    actionToast.classList.add('toast-in');
    setTimeout(function () { actionToast.classList.add('hidden'); }, 2500);
  }

  function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value; // untrusted data — textContent only
  }

  function openDetailModal(id) {
    detailModal.classList.remove('hidden');
    detailSkeleton.classList.remove('hidden');
    detailContent.classList.add('hidden');

    fetch('../api/admin/get-booking-detail.php?booking_id=' + encodeURIComponent(id), { credentials: 'same-origin' })
      .then(function (res) { if (!res.ok) throw new Error('failed'); return res.json(); })
      .then(function (data) {
        const b = data.booking || {};
        setText('detail-service', b.serviceTitle || '—');
        setText('detail-description', b.description || '');
        setText('detail-user', b.userName || '—');
        setText('detail-user-phone', b.userPhone || '');
        setText('detail-mechanic', b.shopName || '—');
        setText('detail-mechanic-phone', (b.mechanicName ? b.mechanicName + ' · ' : '') + (b.mechanicPhone || ''));
        setText('detail-address', b.address || '—');
        setText('detail-scheduled', b.scheduledTime ? new Date(b.scheduledTime.replace(' ', 'T')).toLocaleString() : 'Not scheduled');
        setText('detail-created', b.createdAt ? new Date(b.createdAt.replace(' ', 'T')).toLocaleString() : '—');
        const statusEl = document.getElementById('detail-status');
        if (statusEl) {
          statusEl.textContent = (b.status || '').replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
          statusEl.className = 'text-[11px] font-semibold rounded-full px-2.5 py-1 ' + (statusStylesMap[b.status] || 'bg-slate-100 text-slate-500');
        }
        setText('detail-price', (b.priceMin !== null && b.priceMin !== undefined && b.priceMax !== null && b.priceMax !== undefined)
          ? 'Rs ' + Number(b.priceMin).toLocaleString() + ' – Rs ' + Number(b.priceMax).toLocaleString()
          : 'Not specified');
        detailSkeleton.classList.add('hidden');
        detailContent.classList.remove('hidden');
      })
      .catch(function () {
        detailSkeleton.classList.add('hidden');
        detailContent.classList.add('hidden');
        setText('detail-service', 'Could not load details.');
        detailContent.classList.remove('hidden');
      });
  }

  if (detailClose) detailClose.addEventListener('click', function () { detailModal.classList.add('hidden'); });
  if (detailModal) detailModal.addEventListener('click', function (e) { if (e.target === detailModal) detailModal.classList.add('hidden'); });

  document.querySelectorAll('.view-booking-btn').forEach(function (btn) {
    btn.addEventListener('click', function () { openDetailModal(btn.dataset.bookingId); });
  });

  document.querySelectorAll('.cancel-booking-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      pendingCancelId = btn.dataset.bookingId;
      cancelModal.classList.remove('hidden');
    });
  });

  function closeCancelModal() { cancelModal.classList.add('hidden'); pendingCancelId = null; }
  if (cancelModalCancel) cancelModalCancel.addEventListener('click', closeCancelModal);
  if (cancelModal) cancelModal.addEventListener('click', function (e) { if (e.target === cancelModal) closeCancelModal(); });

  if (cancelModalConfirm) {
    cancelModalConfirm.addEventListener('click', function () {
      if (!pendingCancelId) return;
      cancelModalConfirm.disabled = true;
      cancelConfirmText.textContent = 'Cancelling...';

      fetch('../api/admin/cancel-booking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ booking_id: Number(pendingCancelId) })
      })
        .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
        .then(function (result) {
          if (!result.ok) throw new Error(result.data && result.data.message ? result.data.message : 'Could not cancel the booking.');
          const row = tbody.querySelector('[data-booking-id="' + pendingCancelId + '"]');
          if (row) {
            row.dataset.bookingStatus = 'cancelled';
            const badge = row.querySelector('.booking-status-badge');
            if (badge) {
              badge.textContent = 'Cancelled';
              badge.className = 'booking-status-badge text-[11px] font-semibold rounded-full px-2.5 py-1 ' + statusStylesMap.cancelled;
            }
            const cancelBtn = row.querySelector('.cancel-booking-btn');
            if (cancelBtn) cancelBtn.remove();
          }
          closeCancelModal();
          showToast('Booking cancelled', false);
        })
        .catch(function (err) {
          closeCancelModal();
          showToast(err.message, true);
        })
        .finally(function () {
          cancelModalConfirm.disabled = false;
          cancelConfirmText.textContent = 'Cancel Booking';
        });
    });
  }

})();
</script>

</body>
</html>

