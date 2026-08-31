<?php
/**
 * Admin — Review Moderation. Requires role='admin'.
 * Every review on the platform with a rating filter and search.
 * The Delete action POSTs to api/admin/delete-review.php, which
 * removes the review (and its photo) and recalculates the mechanic's
 * cached rating — and independently re-checks role='admin'.
 * All database values are escaped with htmlspecialchars() before being
 * echoed.
 */

$requiredRole = 'admin';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Self-migration (same pattern as api/submit-review.php) so the query
// below cannot fail on databases created before review photos existed.
ensureReviewPhotoColumn($pdo);

// ---- Filters (GET) ----
$ratingFilter = (string) ($_GET['rating'] ?? 'all');
if (!in_array($ratingFilter, array_merge(['all'], ['1', '2', '3', '4', '5']), true)) {
    $ratingFilter = 'all';
}
$q = trim((string) ($_GET['q'] ?? ''));
$perPage = 15;
$page = max(1, (int) ($_GET['page'] ?? 1));

// ---- Counts for the rating filter chips ----
$ratingCounts = ['1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0];
foreach ($pdo->query("SELECT rating, COUNT(*) AS c FROM reviews GROUP BY rating") as $row) {
    $key = (string) (int) $row['rating'];
    if (isset($ratingCounts[$key])) $ratingCounts[$key] = (int) $row['c'];
}
$totalReviews = array_sum($ratingCounts);

// ---- WHERE clauses (shared by the count and the list query) ----
$where  = [];
$params = [];
if ($ratingFilter !== 'all') {
    $where[] = "r.rating = :rating";
    $params['rating'] = (int) $ratingFilter;
}
if ($q !== '') {
    // Distinct placeholder per column — PDO with native prepared
    // statements (EMULATE_PREPARES=false) rejects a named parameter
    // used more than once in the same statement (HY093).
    $like = '%' . $q . '%';
    $where[] = "(r.comment LIKE :q_comment OR u.name LIKE :q_user OR mu.name LIKE :q_mechanic OR m.shop_name LIKE :q_shop)";
    $params['q_comment']  = $like;
    $params['q_user']     = $like;
    $params['q_mechanic'] = $like;
    $params['q_shop']     = $like;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ---- Pagination ----
$countStmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM reviews r
     JOIN bookings b ON b.id = r.booking_id
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
    "SELECT r.id, r.rating, r.comment, r.photo_path, r.created_at,
            b.id AS booking_id, u.name AS user_name,
            m.id AS mechanic_id, m.shop_name, mu.name AS mechanic_name
     FROM reviews r
     JOIN bookings b ON b.id = r.booking_id
     JOIN users u ON u.id = b.user_id
     JOIN mechanics m ON m.id = b.mechanic_id
     JOIN users mu ON mu.id = m.user_id
     {$whereSql}
     ORDER BY r.created_at DESC
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $key => $value) {
    $listStmt->bindValue(':' . $key, $value);
}
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$reviews = $listStmt->fetchAll();

$adminPageTitle  = 'Reviews — RepairKar Admin';
$adminActivePage = 'reviews';
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
        <h1 class="flex-1 text-lg md:text-2xl font-extrabold text-slate-900">Reviews</h1>
        <span class="text-xs text-slate-400 hidden sm:inline">Logged in as <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></span>
      </div>
    </header>

    <main class="px-4 md:px-8 py-5 md:py-6 space-y-5 pb-10">

      <!-- ---- Rating filter chips + search ---- -->
      <div class="fade-in-up flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex flex-wrap gap-2">
          <?php
            $chips = [['all', 'All', $totalReviews]];
            for ($r = 5; $r >= 1; $r--) {
                $chips[] = [(string) $r, str_repeat('★', $r) . ' ' . $r . '★', $ratingCounts[(string) $r]];
            }
          ?>
          <?php foreach ($chips as [$chipRating, $chipLabel, $chipCount]): ?>
            <?php $chipActive = ($ratingFilter === $chipRating); ?>
            <a href="reviews.php?rating=<?= $chipRating ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>"
               class="rounded-full px-3.5 py-1.5 text-xs font-semibold border transition <?= $chipActive ? 'bg-brand text-white border-brand' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300' ?>">
              <?= $chipLabel ?> <span class="<?= $chipActive ? 'text-white/80' : 'text-slate-400' ?>"><?= number_format($chipCount) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
        <form method="get" action="reviews.php" class="sm:ml-auto flex gap-2">
          <input type="hidden" name="rating" value="<?= htmlspecialchars($ratingFilter) ?>">
          <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search customer, mechanic, comment…"
                 class="w-full sm:w-64 rounded-xl border border-slate-200 px-4 py-2 text-sm focus:ring-2 focus:ring-brand focus:outline-none">
          <button type="submit" class="btn-tap rounded-xl bg-brand hover:bg-brand-dark text-white text-sm font-semibold px-4" aria-label="Search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
          </button>
        </form>
      </div>

      <?php if (empty($reviews)): ?>

        <div class="fade-in-up rounded-2xl bg-white border border-slate-100 p-12 text-center">
          <i class="fa-regular fa-star text-3xl text-slate-300" aria-hidden="true"></i>
          <p class="mt-3 text-sm font-medium text-slate-600">No reviews found.</p>
          <p class="text-xs text-slate-400 mt-1">Reviews appear here once customers rate completed bookings.</p>
        </div>

      <?php else: ?>

        <div class="fade-in-up-d1 rounded-2xl bg-white border border-slate-100 overflow-hidden">
          <div class="px-4 py-3 border-b border-slate-100">
            <p class="text-xs text-slate-400"><?= number_format($totalRows) ?> review<?= $totalRows === 1 ? '' : 's' ?> · page <?= (int) $page ?> of <?= (int) $totalPages ?></p>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left text-[11px] text-slate-400 uppercase tracking-wide border-b border-slate-100">
                  <th class="px-4 py-3 font-medium">Customer</th>
                  <th class="px-4 py-3 font-medium">Mechanic</th>
                  <th class="px-4 py-3 font-medium">Rating</th>
                  <th class="px-4 py-3 font-medium">Comment</th>
                  <th class="px-4 py-3 font-medium">Date</th>
                  <th class="px-4 py-3 font-medium text-right">Actions</th>
                </tr>
              </thead>
              <tbody id="reviews-tbody" class="divide-y divide-slate-50">
                <?php foreach ($reviews as $i => $r): ?>
                  <tr class="row-enter row-hover row-clear hover:bg-slate-50" data-review-id="<?= (int) $r['id'] ?>" style="animation-delay: <?= $i * 30 ?>ms">
                    <td class="px-4 py-3 font-medium text-slate-900"><?= htmlspecialchars($r['user_name']) ?></td>
                    <td class="px-4 py-3">
                      <span class="block text-slate-600 truncate"><?= htmlspecialchars($r['shop_name'] ?: ($r['mechanic_name'] ?: '—')) ?></span>
                      <span class="block text-xs text-slate-400">Booking #<?= (int) $r['booking_id'] ?></span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                      <span class="text-amber-400 text-xs" aria-label="<?= (int) $r['rating'] ?> out of 5 stars">
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                          <i class="fa-<?= $s <= (int) $r['rating'] ? 'solid text-amber-400' : 'regular text-slate-300' ?> fa-star" aria-hidden="true"></i>
                        <?php endfor; ?>
                      </span>
                      <span class="block text-[11px] text-slate-400 mt-0.5"><?= (int) $r['rating'] ?>/5</span>
                    </td>
                    <td class="px-4 py-3 text-slate-600 max-w-[260px]">
                      <span class="block truncate"><?= $r['comment'] !== null && $r['comment'] !== '' ? htmlspecialchars($r['comment']) : '<span class="text-slate-300">No comment</span>' ?></span>
                      <?php if (!empty($r['photo_path'])): ?>
                        <a href="<?= htmlspecialchars($r['photo_path']) ?>" target="_blank" rel="noopener noreferrer" class="text-xs text-brand hover:underline">
                          <i class="fa-regular fa-image" aria-hidden="true"></i> View photo
                        </a>
                      <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-400 whitespace-nowrap"><?= htmlspecialchars(date('M j, Y', strtotime($r['created_at']))) ?></td>
                    <td class="px-4 py-3">
                      <div class="flex items-center justify-end">
                        <button type="button" class="delete-review-btn btn-tap rounded-lg border border-red-100 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-semibold px-3 py-1.5"
                                data-review-id="<?= (int) $r['id'] ?>">
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
                <a href="reviews.php?rating=<?= $ratingFilter ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>&page=<?= $page - 1 ?>" class="btn-tap rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                  <i class="fa-solid fa-chevron-left" aria-hidden="true"></i> Previous
                </a>
              <?php else: ?>
                <span class="rounded-lg border border-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-300">Previous</span>
              <?php endif; ?>
              <span class="text-xs text-slate-400">Page <?= (int) $page ?> of <?= (int) $totalPages ?></span>
              <?php if ($page < $totalPages): ?>
                <a href="reviews.php?rating=<?= $ratingFilter ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>&page=<?= $page + 1 ?>" class="btn-tap rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">
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
    <h2 class="text-base font-bold text-slate-900">Delete this review?</h2>
    <p class="text-sm text-slate-500 mt-1.5">The review is removed permanently and the mechanic's average rating is recalculated. This cannot be undone.</p>
    <div class="flex gap-3 mt-4">
      <button type="button" id="delete-modal-cancel" class="btn-tap flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
      <button type="button" id="delete-modal-confirm" class="btn-tap flex-1 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2.5">
        <span id="delete-confirm-text">Delete Review</span>
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
  const tbody              = document.getElementById('reviews-tbody');
  const deleteModal        = document.getElementById('delete-modal');
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
    const row = tbody.querySelector('[data-review-id="' + id + '"]');
    if (!row) return;
    row.style.opacity = '0';
    row.style.maxHeight = row.offsetHeight + 'px';
    requestAnimationFrame(function () {
      row.style.maxHeight = '0px';
      row.style.overflow = 'hidden';
    });
    setTimeout(function () { row.remove(); }, 350);
  }

  document.querySelectorAll('.delete-review-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      pendingDeleteId = btn.dataset.reviewId;
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

    fetch('../api/admin/delete-review.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ review_id: Number(pendingDeleteId) })
    })
      .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
      .then(function (result) {
        if (!result.ok) throw new Error(result.data && result.data.message ? result.data.message : 'Could not delete the review.');
        removeRow(pendingDeleteId);
        closeModal();
        showToast('Review deleted', false);
      })
      .catch(function (err) {
        closeModal();
        showToast(err.message, true);
      })
      .finally(function () {
        deleteModalConfirm.disabled = false;
        deleteConfirmText.textContent = 'Delete Review';
      });
  });

})();
</script>

</body>
</html>
