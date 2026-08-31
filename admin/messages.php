<?php
/**
 * Admin — Contact/Support Inbox. Requires role='admin'.
 * Submissions from the public contact form (contact_messages table)
 * with a status workflow: new → in_progress → resolved.
 * Actions POST to api/admin/update-contact-status.php and
 * api/admin/delete-contact-message.php, which independently re-check
 * role='admin' server-side. All database values are escaped with
 * htmlspecialchars() before being echoed.
 */

$requiredRole = 'admin';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
ensureContactMessagesTable($pdo);

// ---- Filters (GET) ----
$allowedStatuses = ['new', 'in_progress', 'resolved'];
$statusFilter = (string) ($_GET['status'] ?? 'all');
if (!in_array($statusFilter, array_merge(['all'], $allowedStatuses), true)) {
    $statusFilter = 'all';
}
$perPage = 15;
$page = max(1, (int) ($_GET['page'] ?? 1));

// ---- Counts for the status filter chips ----
$statusCounts = array_fill_keys($allowedStatuses, 0);
foreach ($pdo->query("SELECT status, COUNT(*) AS c FROM contact_messages GROUP BY status") as $row) {
    if (isset($statusCounts[$row['status']])) $statusCounts[$row['status']] = (int) $row['c'];
}
$totalMessages = array_sum($statusCounts);

// ---- Pagination ----
$countStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM contact_messages" . ($statusFilter !== 'all' ? " WHERE status = :status" : "")
);
$countStmt->execute($statusFilter !== 'all' ? ['status' => $statusFilter] : []);
$totalRows  = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

$listStmt = $pdo->prepare(
    "SELECT c.id, c.name, c.email, c.subject, c.message, c.status, c.created_at,
            u.name AS account_name
     FROM contact_messages c
     LEFT JOIN users u ON u.id = c.user_id
     " . ($statusFilter !== 'all' ? "WHERE c.status = :status" : "") . "
     ORDER BY c.created_at DESC
     LIMIT :limit OFFSET :offset"
);
if ($statusFilter !== 'all') {
    $listStmt->bindValue(':status', $statusFilter);
}
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$messages = $listStmt->fetchAll();

$statusStyles = [
    'new'         => 'bg-amber-50 text-amber-600',
    'in_progress' => 'bg-blue-50 text-blue-600',
    'resolved'    => 'bg-green-50 text-green-600',
];

$adminPageTitle  = 'Messages — RepairKar Admin';
$adminActivePage = 'messages';
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
        <h1 class="flex-1 text-lg md:text-2xl font-extrabold text-slate-900">Messages</h1>
        <span class="text-xs text-slate-400 hidden sm:inline">Logged in as <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></span>
      </div>
    </header>

    <main class="px-4 md:px-8 py-5 md:py-6 space-y-5 pb-10">

      <!-- ---- Status filter chips ---- -->
      <div class="fade-in-up flex flex-wrap gap-2">
        <?php
          $chips = array_merge(
              [['all', 'All', $totalMessages]],
              array_map(
                  fn($s) => [$s, ucfirst(str_replace('_', ' ', $s)), $statusCounts[$s]],
                  $allowedStatuses
              )
          );
        ?>
        <?php foreach ($chips as [$chipStatus, $chipLabel, $chipCount]): ?>
          <?php $chipActive = ($statusFilter === $chipStatus); ?>
          <a href="messages.php?status=<?= $chipStatus ?>"
             class="rounded-full px-3.5 py-1.5 text-xs font-semibold border transition <?= $chipActive ? 'bg-brand text-white border-brand' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300' ?>">
            <?= $chipLabel ?> <span class="<?= $chipActive ? 'text-white/80' : 'text-slate-400' ?>"><?= number_format($chipCount) ?></span>
          </a>
        <?php endforeach; ?>
      </div>

      <?php if (empty($messages)): ?>

        <div class="fade-in-up rounded-2xl bg-white border border-slate-100 p-12 text-center">
          <i class="fa-regular fa-envelope-open text-3xl text-slate-300" aria-hidden="true"></i>
          <p class="mt-3 text-sm font-medium text-slate-600">No messages here.</p>
          <p class="text-xs text-slate-400 mt-1">Contact form submissions will appear in this inbox.</p>
        </div>

      <?php else: ?>

        <div class="fade-in-up-d1 rounded-2xl bg-white border border-slate-100 overflow-hidden">
          <div class="px-4 py-3 border-b border-slate-100">
            <p class="text-xs text-slate-400"><?= number_format($totalRows) ?> message<?= $totalRows === 1 ? '' : 's' ?> · page <?= (int) $page ?> of <?= (int) $totalPages ?></p>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left text-[11px] text-slate-400 uppercase tracking-wide border-b border-slate-100">
                  <th class="px-4 py-3 font-medium">From</th>
                  <th class="px-4 py-3 font-medium">Subject</th>
                  <th class="px-4 py-3 font-medium">Status</th>
                  <th class="px-4 py-3 font-medium">Received</th>
                  <th class="px-4 py-3 font-medium text-right">Actions</th>
                </tr>
              </thead>
              <tbody id="messages-tbody" class="divide-y divide-slate-50">
                <?php foreach ($messages as $i => $m): ?>
                  <tr class="row-enter row-hover row-clear hover:bg-slate-50" data-message-id="<?= (int) $m['id'] ?>" data-message-status="<?= htmlspecialchars($m['status']) ?>" style="animation-delay: <?= $i * 30 ?>ms">
                    <td class="px-4 py-3">
                      <span class="block font-medium text-slate-900"><?= htmlspecialchars($m['name']) ?></span>
                      <span class="block text-xs text-slate-400 truncate max-w-[180px]"><?= htmlspecialchars($m['email']) ?></span>
                      <?php if (!empty($m['account_name'])): ?>
                        <span class="block text-[11px] text-slate-400 truncate max-w-[180px]" title="Signed-in account"><i class="fa-regular fa-user" aria-hidden="true"></i> <?= htmlspecialchars($m['account_name']) ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="px-4 py-3">
                      <button type="button" class="view-message-btn text-left hover:underline" data-message-id="<?= (int) $m['id'] ?>">
                        <span class="block font-medium text-slate-800 truncate max-w-[240px]"><?= htmlspecialchars($m['subject']) ?></span>
                        <span class="block text-xs text-slate-400 truncate max-w-[240px]"><?= htmlspecialchars(mb_substr($m['message'], 0, 80)) ?><?= mb_strlen($m['message']) > 80 ? '…' : '' ?></span>
                      </button>
                    </td>
                    <td class="px-4 py-3">
                      <span class="message-status-badge text-[11px] font-semibold rounded-full px-2.5 py-1 <?= $statusStyles[$m['status']] ?? 'bg-slate-100 text-slate-500' ?>">
                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $m['status']))) ?>
                      </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-400 whitespace-nowrap"><?= htmlspecialchars(date('M j, g:i A', strtotime($m['created_at']))) ?></td>
                    <td class="px-4 py-3">
                      <div class="flex items-center justify-end gap-2">
                        <?php if ($m['status'] === 'new'): ?>
                          <button type="button" class="status-btn btn-tap rounded-lg border border-blue-100 bg-blue-50 hover:bg-blue-100 text-blue-600 text-xs font-semibold px-3 py-1.5"
                                  data-message-id="<?= (int) $m['id'] ?>" data-new-status="in_progress">
                            <i class="fa-solid fa-play" aria-hidden="true"></i> Start
                          </button>
                        <?php elseif ($m['status'] === 'in_progress'): ?>
                          <button type="button" class="status-btn btn-tap rounded-lg border border-green-100 bg-green-50 hover:bg-green-100 text-green-600 text-xs font-semibold px-3 py-1.5"
                                  data-message-id="<?= (int) $m['id'] ?>" data-new-status="resolved">
                            <i class="fa-solid fa-check" aria-hidden="true"></i> Resolve
                          </button>
                        <?php endif; ?>
                        <button type="button" class="delete-message-btn btn-tap rounded-lg border border-red-100 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-semibold px-3 py-1.5"
                                data-message-id="<?= (int) $m['id'] ?>">
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
                <a href="messages.php?status=<?= $statusFilter ?>&page=<?= $page - 1 ?>" class="btn-tap rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                  <i class="fa-solid fa-chevron-left" aria-hidden="true"></i> Previous
                </a>
              <?php else: ?>
                <span class="rounded-lg border border-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-300">Previous</span>
              <?php endif; ?>
              <span class="text-xs text-slate-400">Page <?= (int) $page ?> of <?= (int) $totalPages ?></span>
              <?php if ($page < $totalPages): ?>
                <a href="messages.php?status=<?= $statusFilter ?>&page=<?= $page + 1 ?>" class="btn-tap rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">
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

<!-- ================= MESSAGE VIEW MODAL ================= -->
<div id="view-modal" class="hidden modal-backdrop-in fixed inset-0 z-50 bg-black/40 flex items-end sm:items-center justify-center p-4">
  <div class="modal-in bg-white rounded-2xl w-full max-w-lg max-h-[85vh] overflow-y-auto p-5">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-base font-bold text-slate-900">Message</h2>
      <button type="button" id="view-modal-close" aria-label="Close" class="w-8 h-8 rounded-full hover:bg-slate-100 flex items-center justify-center">
        <i class="fa-solid fa-xmark text-slate-500" aria-hidden="true"></i>
      </button>
    </div>
    <div class="space-y-3">
      <div class="flex items-start justify-between gap-3">
        <div>
          <p id="view-name" class="font-bold text-slate-900"></p>
          <p id="view-email" class="text-xs text-slate-500"></p>
          <p id="view-account" class="hidden text-xs text-slate-400 mt-0.5"></p>
        </div>
        <span id="view-status" class="text-[11px] font-semibold rounded-full px-2.5 py-1 bg-slate-100 text-slate-500 shrink-0"></span>
      </div>
      <div>
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Subject</p>
        <p id="view-subject" class="text-sm font-semibold text-slate-800"></p>
      </div>
      <div>
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Message</p>
        <p id="view-message" class="text-sm text-slate-700 whitespace-pre-wrap"></p>
      </div>
      <p id="view-date" class="text-xs text-slate-400"></p>
      <div class="flex flex-wrap gap-2 pt-2 border-t border-slate-100">
        <a id="view-reply-link" href="#" class="btn-tap rounded-xl bg-brand hover:bg-brand-dark text-white text-xs font-semibold px-4 py-2.5">
          <i class="fa-regular fa-envelope" aria-hidden="true"></i> Reply via Email
        </a>
        <button type="button" id="view-start-btn" class="btn-tap rounded-xl border border-blue-100 bg-blue-50 hover:bg-blue-100 text-blue-600 text-xs font-semibold px-4 py-2.5">
          <i class="fa-solid fa-play" aria-hidden="true"></i> Mark In Progress
        </button>
        <button type="button" id="view-resolve-btn" class="btn-tap rounded-xl border border-green-100 bg-green-50 hover:bg-green-100 text-green-600 text-xs font-semibold px-4 py-2.5">
          <i class="fa-solid fa-check" aria-hidden="true"></i> Mark Resolved
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ================= DELETE CONFIRM MODAL ================= -->
<div id="delete-modal" class="hidden modal-backdrop-in fixed inset-0 z-50 bg-black/40 flex items-end sm:items-center justify-center p-4">
  <div class="modal-in bg-white rounded-2xl w-full max-w-sm p-5">
    <h2 class="text-base font-bold text-slate-900">Delete this message?</h2>
    <p class="text-sm text-slate-500 mt-1.5">The submission is removed permanently. If the sender expects a reply, consider resolving it instead.</p>
    <div class="flex gap-3 mt-4">
      <button type="button" id="delete-modal-cancel" class="btn-tap flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
      <button type="button" id="delete-modal-confirm" class="btn-tap flex-1 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2.5">
        <span id="delete-confirm-text">Delete Message</span>
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
  const tbody          = document.getElementById('messages-tbody');
  const viewModal      = document.getElementById('view-modal');
  const viewModalClose = document.getElementById('view-modal-close');
  const deleteModal        = document.getElementById('delete-modal');
  const deleteModalCancel  = document.getElementById('delete-modal-cancel');
  const deleteModalConfirm = document.getElementById('delete-modal-confirm');
  const deleteConfirmText  = document.getElementById('delete-confirm-text');
  const actionToast    = document.getElementById('action-toast');
  const toastIcon      = document.getElementById('toast-icon');
  const toastText      = document.getElementById('toast-text');

  const statusStylesMap = {
    new:         'bg-amber-50 text-amber-600',
    in_progress: 'bg-blue-50 text-blue-600',
    resolved:    'bg-green-50 text-green-600'
  };

  // Message payloads for the view modal — encoded server-side with
  // JSON_HEX_* flags so embedded user content can never break out of
  // the script context. Rendered with textContent only.
  const MESSAGES_DATA = <?php
    $messagesData = [];
    foreach ($messages as $m) {
        $messagesData[(int) $m['id']] = [
            'name'      => $m['name'],
            'email'     => $m['email'],
            'subject'   => $m['subject'],
            'message'   => $m['message'],
            'status'    => $m['status'],
            'createdAt' => $m['created_at'],
        ];
    }
    echo json_encode($messagesData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
  ?>;

  let currentViewId = null;
  let pendingDeleteId = null;

  if (!tbody) return; // empty state — nothing else to wire up

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

  function removeRow(id) {
    const row = tbody.querySelector('[data-message-id="' + id + '"]');
    if (!row) return;
    row.style.opacity = '0';
    row.style.maxHeight = row.offsetHeight + 'px';
    requestAnimationFrame(function () {
      row.style.maxHeight = '0px';
      row.style.overflow = 'hidden';
    });
    setTimeout(function () { row.remove(); }, 350);
  }

  function updateRowStatus(id, newStatus) {
    const row = tbody.querySelector('[data-message-id="' + id + '"]');
    if (row) {
      row.dataset.messageStatus = newStatus;
      const badge = row.querySelector('.message-status-badge');
      if (badge) {
        badge.textContent = newStatus.replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
        badge.className = 'message-status-badge text-[11px] font-semibold rounded-full px-2.5 py-1 ' + (statusStylesMap[newStatus] || 'bg-slate-100 text-slate-500');
      }
      const statusBtn = row.querySelector('.status-btn');
      if (statusBtn) {
        if (newStatus === 'new') {
          statusBtn.dataset.newStatus = 'in_progress';
          statusBtn.innerHTML = '<i class="fa-solid fa-play" aria-hidden="true"></i> Start';
          statusBtn.className = 'status-btn btn-tap rounded-lg border border-blue-100 bg-blue-50 hover:bg-blue-100 text-blue-600 text-xs font-semibold px-3 py-1.5';
        } else if (newStatus === 'in_progress') {
          statusBtn.dataset.newStatus = 'resolved';
          statusBtn.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> Resolve';
          statusBtn.className = 'status-btn btn-tap rounded-lg border border-green-100 bg-green-50 hover:bg-green-100 text-green-600 text-xs font-semibold px-3 py-1.5';
        } else {
          statusBtn.remove();
        }
      }
    }
  }

  function openViewModal(id) {
    const data = MESSAGES_DATA[id];
    if (!data) return;
    currentViewId = id;

    setText('view-name', data.name || '—');
    setText('view-email', data.email || '');
    setText('view-subject', data.subject || '');
    setText('view-message', data.message || '');
    setText('view-date', data.createdAt ? new Date(String(data.createdAt).replace(' ', 'T')).toLocaleString() : '');

    const accountEl = document.getElementById('view-account');
    if (accountEl) {
      if (data.account_name) {
        accountEl.textContent = 'Signed-in account: ' + data.account_name;
        accountEl.classList.remove('hidden');
      } else {
        accountEl.classList.add('hidden');
      }
    }

    const statusEl = document.getElementById('view-status');
    if (statusEl) {
      statusEl.textContent = String(data.status || '').replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
      statusEl.className = 'text-[11px] font-semibold rounded-full px-2.5 py-1 shrink-0 ' + (statusStylesMap[data.status] || 'bg-slate-100 text-slate-500');
    }

    const replyLink = document.getElementById('view-reply-link');
    if (replyLink) {
      replyLink.href = 'mailto:' + encodeURIComponent(data.email || '') +
        '?subject=' + encodeURIComponent('Re: ' + (data.subject || 'Your RepairKar message'));
    }

    const startBtn   = document.getElementById('view-start-btn');
    const resolveBtn = document.getElementById('view-resolve-btn');
    if (startBtn)   startBtn.classList.toggle('hidden', data.status !== 'new');
    if (resolveBtn) resolveBtn.classList.toggle('hidden', data.status === 'resolved');

    viewModal.classList.remove('hidden');
  }

  function callStatusApi(id, newStatus) {
    return fetch('../api/admin/update-contact-status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ message_id: Number(id), status: newStatus })
    }).then(function (res) {
      return res.json().then(function (data) { return { ok: res.ok, data: data }; });
    });
  }

  // ---- Status buttons (rows) ----
  document.querySelectorAll('.status-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const id = btn.dataset.messageId;
      const newStatus = btn.dataset.newStatus;
      btn.disabled = true;
      callStatusApi(id, newStatus)
        .then(function (result) {
          if (!result.ok) throw new Error(result.data && result.data.message ? result.data.message : 'Could not update the message.');
          updateRowStatus(id, newStatus);
          if (MESSAGES_DATA[id]) MESSAGES_DATA[id].status = newStatus;
          showToast(newStatus === 'resolved' ? 'Message resolved' : 'Marked in progress', false);
        })
        .catch(function (err) {
          showToast(err.message, true);
        })
        .finally(function () {
          btn.disabled = false;
        });
    });
  });

  // ---- View modal ----
  document.querySelectorAll('.view-message-btn').forEach(function (btn) {
    btn.addEventListener('click', function () { openViewModal(Number(btn.dataset.messageId)); });
  });
  if (viewModalClose) viewModalClose.addEventListener('click', function () { viewModal.classList.add('hidden'); });
  if (viewModal) viewModal.addEventListener('click', function (e) { if (e.target === viewModal) viewModal.classList.add('hidden'); });

  // ---- Status buttons (view modal) ----
  [['view-start-btn', 'in_progress'], ['view-resolve-btn', 'resolved']].forEach(function (pair) {
    const btn = document.getElementById(pair[0]);
    if (!btn) return;
    btn.addEventListener('click', function () {
      if (!currentViewId) return;
      btn.disabled = true;
      callStatusApi(currentViewId, pair[1])
        .then(function (result) {
          if (!result.ok) throw new Error(result.data && result.data.message ? result.data.message : 'Could not update the message.');
          updateRowStatus(currentViewId, pair[1]);
          if (MESSAGES_DATA[currentViewId]) MESSAGES_DATA[currentViewId].status = pair[1];
          const statusEl = document.getElementById('view-status');
          if (statusEl) {
            statusEl.textContent = pair[1].replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
            statusEl.className = 'text-[11px] font-semibold rounded-full px-2.5 py-1 shrink-0 ' + (statusStylesMap[pair[1]] || 'bg-slate-100 text-slate-500');
          }
          btn.classList.add('hidden');
          showToast(pair[1] === 'resolved' ? 'Message resolved' : 'Marked in progress', false);
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
  document.querySelectorAll('.delete-message-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      pendingDeleteId = btn.dataset.messageId;
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

    fetch('../api/admin/delete-contact-message.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ message_id: Number(pendingDeleteId) })
    })
      .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
      .then(function (result) {
        if (!result.ok) throw new Error(result.data && result.data.message ? result.data.message : 'Could not delete the message.');
        if (currentViewId === Number(pendingDeleteId)) viewModal.classList.add('hidden');
        removeRow(pendingDeleteId);
        closeModal();
        showToast('Message deleted', false);
      })
      .catch(function (err) {
        closeModal();
        showToast(err.message, true);
      })
      .finally(function () {
        deleteModalConfirm.disabled = false;
        deleteConfirmText.textContent = 'Delete Message';
      });
  });

})();
</script>

</body>
</html>
