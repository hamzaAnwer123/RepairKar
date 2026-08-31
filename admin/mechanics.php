<?php
/**
 * Admin — Mechanic Verification Queue. Requires role='admin'.
 * All dynamic values from the database are escaped with
 * htmlspecialchars() before being echoed — mechanic-supplied shop
 * names, bios, and document filenames are untrusted content.
 */

$requiredRole = 'admin';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->prepare(
    "SELECT m.id, m.shop_name, m.category, m.bio, m.cnic_doc_path, m.shop_photo_path,
            m.created_at, u.name AS owner_name, u.phone AS owner_phone
     FROM mechanics m
     JOIN users u ON u.id = m.user_id
     WHERE m.verified = 0 AND m.rejected = 0
     ORDER BY m.created_at ASC"
);
$stmt->execute();
$pendingMechanics = $stmt->fetchAll();
?>
<?php
$adminPageTitle  = 'Mechanic Verification — RepairKar Admin';
$adminActivePage = 'mechanics';
require_once __DIR__ . '/../includes/admin-head.php'; ?>

<!--
  SECURITY NOTE: shop name, bio, owner name, and document paths below
  are untrusted mechanic-supplied content — escaped with
  htmlspecialchars() before being echoed. Approve/Reject actions POST
  to api/admin/verify-mechanic.php, which independently re-checks
  role='admin' server-side — this page's own auth-check is not
  sufficient on its own, since the API endpoint could otherwise be
  called directly.
-->

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
        <h1 class="flex-1 text-lg md:text-2xl font-extrabold text-slate-900">Mechanic Verification</h1>
      </div>
    </header>

    <main class="px-4 md:px-8 py-5 md:py-6 pb-10">

      <?php if (empty($pendingMechanics)): ?>

        <div class="fade-in-up rounded-2xl bg-white border border-slate-100 p-12 text-center">
          <i class="fa-solid fa-circle-check text-3xl text-green-400" aria-hidden="true"></i>
          <p class="mt-3 text-sm font-medium text-slate-600">No pending verifications.</p>
          <p class="text-xs text-slate-400 mt-1">New mechanic applications will appear here.</p>
        </div>

      <?php else: ?>

        <div class="fade-in-up rounded-2xl bg-white border border-slate-100 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left text-[11px] text-slate-400 uppercase tracking-wide border-b border-slate-100">
                  <th class="px-4 py-3 font-medium">Mechanic</th>
                  <th class="px-4 py-3 font-medium">Category</th>
                  <th class="px-4 py-3 font-medium">Documents</th>
                  <th class="px-4 py-3 font-medium">Submitted</th>
                  <th class="px-4 py-3 font-medium text-right">Actions</th>
                </tr>
              </thead>
              <tbody id="mechanics-tbody" class="divide-y divide-slate-50">
                <?php foreach ($pendingMechanics as $i => $m): ?>
                  <tr class="row-enter row-hover row-clear hover:bg-slate-50" data-mechanic-id="<?= (int) $m['id'] ?>" style="animation-delay: <?= $i * 50 ?>ms">
                    <td class="px-4 py-3">
                      <button type="button" class="view-detail-btn text-left flex items-center gap-2.5 hover:underline"
                              data-mechanic-id="<?= (int) $m['id'] ?>">
                        <?php if ($m['shop_photo_path']): ?>
                          <img src="<?= htmlspecialchars($m['shop_photo_path']) ?>" alt="" width="32" height="32" class="w-8 h-8 rounded-full object-cover bg-slate-100 shrink-0">
                        <?php else: ?>
                          <span class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-wrench text-slate-400 text-xs" aria-hidden="true"></i>
                          </span>
                        <?php endif; ?>
                        <span>
                          <span class="block font-medium text-slate-900"><?= htmlspecialchars($m['shop_name']) ?></span>
                          <span class="block text-xs text-slate-400"><?= htmlspecialchars($m['owner_name']) ?></span>
                        </span>
                      </button>
                    </td>
                    <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars(ucfirst(str_replace('-', ' ', $m['category']))) ?></td>
                    <td class="px-4 py-3">
                      <?php if ($m['cnic_doc_path']): ?>
                        <a href="<?= htmlspecialchars($m['cnic_doc_path']) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-xs text-brand hover:underline">
                          <i class="fa-regular fa-file-lines" aria-hidden="true"></i> CNIC
                        </a>
                      <?php else: ?>
                        <span class="text-xs text-slate-300">Not uploaded</span>
                      <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-400">
                      <?= htmlspecialchars(date('M j, Y', strtotime($m['created_at']))) ?>
                    </td>
                    <td class="px-4 py-3">
                      <div class="flex items-center justify-end gap-2">
                        <button type="button" class="approve-btn btn-tap rounded-lg bg-brand hover:bg-brand-dark text-white text-xs font-semibold px-3 py-1.5"
                                data-mechanic-id="<?= (int) $m['id'] ?>">
                          <i class="fa-solid fa-check" aria-hidden="true"></i> Approve
                        </button>
                        <button type="button" class="reject-btn btn-tap rounded-lg border border-slate-200 text-slate-600 text-xs font-semibold px-3 py-1.5"
                                data-mechanic-id="<?= (int) $m['id'] ?>">
                          <i class="fa-solid fa-xmark" aria-hidden="true"></i> Reject
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      <?php endif; ?>

    </main>
  </div>
</div>

<!-- ================= DETAIL MODAL ================= -->
<div id="detail-modal" class="hidden modal-backdrop-in fixed inset-0 z-50 bg-black/40 flex items-end sm:items-center justify-center p-4">
  <div class="modal-in bg-white rounded-2xl w-full max-w-lg max-h-[85vh] overflow-y-auto p-5">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-base font-bold text-slate-900">Application Details</h2>
      <button type="button" id="detail-modal-close" aria-label="Close" class="w-8 h-8 rounded-full hover:bg-slate-100 flex items-center justify-center">
        <i class="fa-solid fa-xmark text-slate-500" aria-hidden="true"></i>
      </button>
    </div>
    <div id="detail-skeleton" class="space-y-3">
      <div class="skeleton h-4 w-1/2 rounded bg-slate-200 animate-pulse"></div>
      <div class="skeleton h-20 rounded bg-slate-200 animate-pulse"></div>
    </div>
    <div id="detail-content" class="hidden space-y-4">
      <div class="flex items-center gap-3">
        <img id="detail-shop-photo" src="" alt="" class="hidden w-14 h-14 rounded-xl object-cover bg-slate-100">
        <div>
          <p id="detail-shop-name" class="font-bold text-slate-900"></p>
          <p id="detail-owner" class="text-xs text-slate-500"></p>
        </div>
      </div>
      <div>
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Category</p>
        <p id="detail-category" class="text-sm text-slate-700"></p>
      </div>
      <div>
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Bio</p>
        <p id="detail-bio" class="text-sm text-slate-700"></p>
      </div>
      <div>
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">CNIC Document</p>
        <a id="detail-cnic-link" href="#" target="_blank" rel="noopener noreferrer" class="hidden text-sm text-brand hover:underline inline-flex items-center gap-1.5">
          <i class="fa-regular fa-file-lines" aria-hidden="true"></i> View document
        </a>
        <p id="detail-cnic-missing" class="hidden text-sm text-slate-400">Not uploaded</p>
      </div>
    </div>
  </div>
</div>

<!-- ================= REJECT REASON MODAL ================= -->
<div id="reject-modal" class="hidden modal-backdrop-in fixed inset-0 z-50 bg-black/40 flex items-end sm:items-center justify-center p-4">
  <div class="modal-in bg-white rounded-2xl w-full max-w-sm p-5">
    <h2 class="text-base font-bold text-slate-900">Reject this application?</h2>
    <p class="text-sm text-slate-500 mt-1.5">Provide a reason — this will be shared with the applicant.</p>
    <label for="reject-reason" class="sr-only">Reason for rejection</label>
    <textarea id="reject-reason" rows="3" placeholder="e.g. CNIC document is unreadable, please re-upload."
              class="w-full mt-3 rounded-xl border border-slate-200 px-4 py-2.5 text-sm resize-none focus:ring-2 focus:ring-brand focus:outline-none"></textarea>
    <p id="reject-reason-error" class="error-msg hidden text-xs text-red-600 mt-1.5"></p>
    <div class="flex gap-3 mt-4">
      <button type="button" id="reject-modal-cancel" class="btn-tap flex-1 rounded-xl border border-slate-200 text-slate-700 text-sm font-medium px-4 py-2.5">
        Cancel
      </button>
      <button type="button" id="reject-modal-confirm" class="btn-tap flex-1 rounded-xl bg-red-500 hover:bg-red-600 text-white text-sm font-semibold px-4 py-2.5">
        <span id="reject-confirm-text">Confirm Reject</span>
      </button>
    </div>
  </div>
</div>

<!-- ================= TOAST ================= -->
<div id="action-toast" class="hidden toast-in fixed bottom-5 left-1/2 -translate-x-1/2 z-50 bg-slate-900 text-white text-sm font-medium rounded-xl px-4 py-2.5 flex items-center gap-2">
  <i id="toast-icon" class="fa-solid fa-circle-check text-green-400" aria-hidden="true"></i>
  <span id="toast-text">Mechanic approved</span>
</div>

<script>
(function () {

  const tbody = document.getElementById('mechanics-tbody');

  const detailModal   = document.getElementById('detail-modal');
  const detailClose    = document.getElementById('detail-modal-close');
  const detailSkeleton = document.getElementById('detail-skeleton');
  const detailContent  = document.getElementById('detail-content');
  const detailShopPhoto = document.getElementById('detail-shop-photo');
  const detailShopName  = document.getElementById('detail-shop-name');
  const detailOwner     = document.getElementById('detail-owner');
  const detailCategory  = document.getElementById('detail-category');
  const detailBio       = document.getElementById('detail-bio');
  const detailCnicLink   = document.getElementById('detail-cnic-link');
  const detailCnicMissing = document.getElementById('detail-cnic-missing');

  const rejectModal        = document.getElementById('reject-modal');
  const rejectReason        = document.getElementById('reject-reason');
  const rejectReasonError   = document.getElementById('reject-reason-error');
  const rejectModalCancel   = document.getElementById('reject-modal-cancel');
  const rejectModalConfirm  = document.getElementById('reject-modal-confirm');
  const rejectConfirmText   = document.getElementById('reject-confirm-text');

  const actionToast = document.getElementById('action-toast');
  const toastIcon    = document.getElementById('toast-icon');
  const toastText    = document.getElementById('toast-text');

  let pendingRejectId = null;

  if (!tbody) return; // empty state — nothing else to wire up

  function showToast(message, isError) {
    toastText.textContent = message;
    toastIcon.className = 'fa-solid ' + (isError ? 'fa-circle-xmark text-red-400' : 'fa-circle-check text-green-400');
    actionToast.classList.remove('hidden');
    actionToast.classList.add('toast-in');
    setTimeout(function () { actionToast.classList.add('hidden'); }, 2500);
  }

  // ---- View detail modal ----
  document.querySelectorAll('.view-detail-btn').forEach(function (btn) {
    btn.addEventListener('click', function () { openDetailModal(btn.dataset.mechanicId); });
  });
  detailClose.addEventListener('click', function () { detailModal.classList.add('hidden'); });
  detailModal.addEventListener('click', function (e) { if (e.target === detailModal) detailModal.classList.add('hidden'); });

  function openDetailModal(id) {
    detailModal.classList.remove('hidden');
    detailSkeleton.classList.remove('hidden');
    detailContent.classList.add('hidden');

    fetch('../api/admin/get-mechanic-detail.php?id=' + encodeURIComponent(id), { credentials: 'same-origin' })
      .then(function (res) { if (!res.ok) throw new Error('failed'); return res.json(); })
      .then(function (data) {
        detailShopName.textContent = data.shopName;   // untrusted — textContent only
        detailOwner.textContent = data.ownerName + ' · ' + data.ownerPhone;   // untrusted — textContent only
        detailCategory.textContent = data.category;
        detailBio.textContent = data.bio || 'No bio provided.';   // untrusted — textContent only
        if (data.shopPhoto) {
          detailShopPhoto.src = data.shopPhoto;
          detailShopPhoto.classList.remove('hidden');
        } else {
          detailShopPhoto.classList.add('hidden');
        }
        if (data.cnicDoc) {
          detailCnicLink.href = data.cnicDoc;
          detailCnicLink.classList.remove('hidden');
          detailCnicMissing.classList.add('hidden');
        } else {
          detailCnicLink.classList.add('hidden');
          detailCnicMissing.classList.remove('hidden');
        }
        detailSkeleton.classList.add('hidden');
        detailContent.classList.remove('hidden');
      })
      .catch(function () {
        detailSkeleton.classList.add('hidden');
        detailContent.classList.remove('hidden');
        detailShopName.textContent = 'Could not load details.';
      });
  }

  // ---- Approve/Reject row removal (matches home.html's card-removal animation pattern) ----
  function removeRow(id) {
    const row = tbody.querySelector('[data-mechanic-id="' + id + '"]');
    if (!row) return;
    row.style.opacity = '0';
    row.style.maxHeight = row.offsetHeight + 'px';
    requestAnimationFrame(function () {
      row.style.maxHeight = '0px';
      row.style.overflow = 'hidden';
    });
    setTimeout(function () { row.remove(); }, 350);
  }

  function callVerifyApi(id, action, reason) {
    return fetch('../api/admin/verify-mechanic.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ mechanic_id: id, action: action, reason: reason || null })
    }).then(function (res) {
      return res.json().then(function (data) { return { ok: res.ok, data: data }; });
    });
  }

  // ---- Approve (no confirmation needed — reversible via re-review) ----
  document.querySelectorAll('.approve-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const id = btn.dataset.mechanicId;
      btn.disabled = true;
      callVerifyApi(id, 'approve', null)
        .then(function (result) {
          if (!result.ok) throw new Error(result.data && result.data.message ? result.data.message : 'Could not approve mechanic.');
          removeRow(id);
          showToast('Mechanic approved', false);
        })
        .catch(function (err) {
          showToast(err.message, true);
          btn.disabled = false;
        });
    });
  });

  // ---- Reject (requires a reason — confirmation modal) ----
  document.querySelectorAll('.reject-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      pendingRejectId = btn.dataset.mechanicId;
      rejectReason.value = '';
      rejectReasonError.classList.add('hidden');
      rejectModal.classList.remove('hidden');
      rejectReason.focus();
    });
  });
  rejectModalCancel.addEventListener('click', function () {
    rejectModal.classList.add('hidden');
    pendingRejectId = null;
  });
  rejectModalConfirm.addEventListener('click', function () {
    const reason = rejectReason.value.trim();
    if (reason.length < 10) {
      rejectReasonError.textContent = 'Please provide a reason (at least 10 characters) so the applicant knows what to fix.';
      rejectReasonError.classList.remove('hidden');
      return;
    }
    rejectModalConfirm.disabled = true;
    rejectConfirmText.textContent = 'Rejecting...';

    callVerifyApi(pendingRejectId, 'reject', reason)
      .then(function (result) {
        if (!result.ok) throw new Error(result.data && result.data.message ? result.data.message : 'Could not reject application.');
        removeRow(pendingRejectId);
        rejectModal.classList.add('hidden');
        showToast('Application rejected', false);
      })
      .catch(function (err) {
        rejectReasonError.textContent = err.message;
        rejectReasonError.classList.remove('hidden');
      })
      .finally(function () {
        rejectModalConfirm.disabled = false;
        rejectConfirmText.textContent = 'Confirm Reject';
        pendingRejectId = null;
      });
  });

})();
</script>

</body>
</html>