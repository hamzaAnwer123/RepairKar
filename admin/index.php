<?php
/**
 * Admin Dashboard — requires an authenticated session with role='admin'.
 * All dynamic values pulled from the database are escaped with
 * htmlspecialchars() before being echoed, since they are untrusted
 * content (user names, mechanic shop names, etc.).
 */

$requiredRole = 'admin';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
ensureContactMessagesTable($pdo);

// ---- KPI counts ----
$totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalMechanics = (int) $pdo->query("SELECT COUNT(*) FROM mechanics")->fetchColumn();
$activeBookingsToday = (int) $pdo->query(
    "SELECT COUNT(*) FROM bookings
     WHERE status IN ('pending', 'accepted', 'en_route')
       AND DATE(created_at) = CURDATE()"
)->fetchColumn();
$pendingVerifications = (int) $pdo->query("SELECT COUNT(*) FROM mechanics WHERE verified = 0 AND rejected = 0")->fetchColumn();

// ---- Contact/support inbox stats ----
$newMessagesCount  = (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn();
$totalMessagesCount = (int) $pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();

// ---- Latest contact submissions (dashboard preview) ----
$recentMessages = $pdo->query(
    "SELECT c.id, c.name, c.email, c.subject, c.message, c.status, c.created_at,
            u.name AS account_name
     FROM contact_messages c
     LEFT JOIN users u ON u.id = c.user_id
     ORDER BY c.created_at DESC
     LIMIT 6"
)->fetchAll();

// ---- Bookings per day, last 30 days (for the chart) ----
$chartStmt = $pdo->query(
    "SELECT DATE(created_at) AS day, COUNT(*) AS total
     FROM bookings
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY DATE(created_at)
     ORDER BY day ASC"
);
$chartRows = $chartStmt->fetchAll();
$chartLabels = array_map(fn($r) => $r['day'], $chartRows);
$chartValues = array_map(fn($r) => (int) $r['total'], $chartRows);

// ---- Recent activity (latest bookings + latest signups, merged) ----
$recentBookings = $pdo->query(
    "SELECT b.id, b.status, b.created_at, u.name AS user_name, m.shop_name AS mechanic_name
     FROM bookings b
     JOIN users u ON u.id = b.user_id
     JOIN mechanics m ON m.id = b.mechanic_id
     ORDER BY b.created_at DESC
     LIMIT 8"
)->fetchAll();
?>
<?php
$adminPageTitle  = 'Admin Dashboard — RepairKar';
$adminActivePage = 'dashboard';
require_once __DIR__ . '/../includes/admin-head.php'; ?>
<body class="bg-slate-50 text-slate-800 antialiased">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<div class="md:flex md:min-h-screen">

<?php require_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <!-- ================= MAIN ================= -->
  <div class="flex-1 min-w-0">

    <header class="sticky top-0 z-30 bg-white border-b border-slate-100">
      <div class="flex items-center gap-3 px-4 md:px-8 py-4">
        <button type="button" id="open-drawer-btn" aria-label="Open menu" class="md:hidden w-9 h-9 rounded-full hover:bg-slate-100 flex items-center justify-center shrink-0">
          <i class="fa-solid fa-bars text-slate-600" aria-hidden="true"></i>
        </button>
        <h1 class="flex-1 text-lg md:text-2xl font-extrabold text-slate-900">Dashboard</h1>
        <span class="text-xs text-slate-400 hidden sm:inline">Logged in as <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></span>
      </div>
    </header>

    <main class="px-4 md:px-8 py-5 md:py-6 space-y-6 pb-10">

      <!-- ---- KPI cards ---- -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 md:gap-4">

        <div class="kpi-card rounded-2xl bg-white border border-slate-100 p-4">
          <p class="text-[11px] text-slate-400 font-medium uppercase tracking-wide">Total Users</p>
          <p class="kpi-number text-2xl font-extrabold text-slate-900 mt-1"><?= number_format($totalUsers) ?></p>
        </div>

        <div class="kpi-card rounded-2xl bg-white border border-slate-100 p-4" style="animation-delay:60ms">
          <p class="text-[11px] text-slate-400 font-medium uppercase tracking-wide">Total Mechanics</p>
          <p class="kpi-number text-2xl font-extrabold text-slate-900 mt-1"><?= number_format($totalMechanics) ?></p>
        </div>

        <div class="kpi-card rounded-2xl bg-white border border-slate-100 p-4" style="animation-delay:120ms">
          <p class="text-[11px] text-slate-400 font-medium uppercase tracking-wide">Active Bookings Today</p>
          <p class="kpi-number text-2xl font-extrabold text-slate-900 mt-1"><?= number_format($activeBookingsToday) ?></p>
        </div>

        <div class="kpi-card rounded-2xl bg-white border border-slate-100 p-4" style="animation-delay:180ms">
          <p class="text-[11px] text-slate-400 font-medium uppercase tracking-wide">Pending Verifications</p>
          <p class="kpi-number text-2xl font-extrabold <?= $pendingVerifications > 0 ? 'text-accent' : 'text-slate-900' ?> mt-1">
            <?= number_format($pendingVerifications) ?>
          </p>
        </div>

        <a href="messages.php" class="kpi-card block rounded-2xl bg-white border border-slate-100 p-4 hover:border-brand hover:shadow-sm transition-all" style="animation-delay:240ms" aria-label="Open contact messages inbox">
          <p class="text-[11px] text-slate-400 font-medium uppercase tracking-wide">Contact Messages</p>
          <p class="kpi-number text-2xl font-extrabold mt-1 flex items-center gap-2 <?= $newMessagesCount > 0 ? 'text-accent' : 'text-slate-900' ?>">
            <?= number_format($totalMessagesCount) ?>
            <?php if ($newMessagesCount > 0): ?>
              <span class="badge-pop text-[10px] font-bold bg-amber-100 text-amber-700 rounded-full px-2 py-0.5"><?= (int) $newMessagesCount ?> new</span>
            <?php endif; ?>
          </p>
        </a>

      </div>

      <!-- ---- Chart ---- -->
      <section class="fade-in-up rounded-2xl bg-white border border-slate-100 p-5">
        <h2 class="text-sm font-semibold text-slate-900 mb-3">Bookings — Last 30 Days</h2>
        <?php if (empty($chartRows)): ?>
          <p class="text-sm text-slate-400 text-center py-10">No booking data yet.</p>
        <?php else: ?>
          <canvas id="bookings-chart" height="80" role="img" aria-label="Line chart of bookings over the last 30 days"></canvas>
        <?php endif; ?>
      </section>

      <!-- ---- Recent activity ---- -->
      <section class="fade-in-up-d1">
        <h2 class="text-sm font-semibold text-slate-900 mb-3 px-1">Recent Activity</h2>

        <?php if (empty($recentBookings)): ?>
          <div class="rounded-2xl bg-white border border-slate-100 p-8 text-center">
            <i class="fa-regular fa-clock text-2xl text-slate-300" aria-hidden="true"></i>
            <p class="mt-2 text-sm text-slate-500">No recent bookings.</p>
          </div>
        <?php else: ?>
          <div class="rounded-2xl bg-white border border-slate-100 overflow-hidden">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left text-[11px] text-slate-400 uppercase tracking-wide border-b border-slate-100">
                  <th class="px-4 py-3 font-medium">User</th>
                  <th class="px-4 py-3 font-medium">Mechanic</th>
                  <th class="px-4 py-3 font-medium">Status</th>
                  <th class="px-4 py-3 font-medium text-right">Date</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50">
                <?php foreach ($recentBookings as $i => $row): ?>
                  <?php
                    $statusStyles = [
                        'pending'   => 'bg-amber-50 text-amber-600',
                        'accepted'  => 'bg-blue-50 text-blue-600',
                        'en_route'  => 'bg-blue-50 text-blue-600',
                        'completed' => 'bg-green-50 text-green-600',
                        'cancelled' => 'bg-slate-100 text-slate-500',
                    ];
                    $statusClass = $statusStyles[$row['status']] ?? 'bg-slate-100 text-slate-500';
                  ?>
                  <tr class="row-enter row-hover hover:bg-slate-50" style="animation-delay: <?= $i * 40 ?>ms">
                    <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars($row['user_name']) ?></td>
                    <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($row['mechanic_name']) ?></td>
                    <td class="px-4 py-3">
                      <span class="text-[11px] font-semibold rounded-full px-2.5 py-1 <?= $statusClass ?>">
                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $row['status']))) ?>
                      </span>
                    </td>
                    <td class="px-4 py-3 text-right text-xs text-slate-400">
                      <?= htmlspecialchars(date('M j, g:i A', strtotime($row['created_at']))) ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>

      <!-- ---- Recent messages (contact form submissions) ---- -->
      <section class="fade-in-up-d2">
        <div class="flex items-center justify-between gap-3 mb-3 px-1">
          <h2 class="text-sm font-semibold text-slate-900">Recent Messages</h2>
          <a href="messages.php" class="text-xs font-semibold text-brand hover:underline inline-flex items-center gap-1">
            Open inbox <i class="fa-solid fa-arrow-right text-[10px]" aria-hidden="true"></i>
          </a>
        </div>

        <?php if (empty($recentMessages)): ?>
          <div class="rounded-2xl bg-white border border-slate-100 p-8 text-center">
            <i class="fa-regular fa-envelope-open text-2xl text-slate-300" aria-hidden="true"></i>
            <p class="mt-2 text-sm text-slate-500">No contact messages yet.</p>
            <p class="text-xs text-slate-400 mt-1">Submissions from the public contact form appear here.</p>
          </div>
        <?php else: ?>
          <div class="rounded-2xl bg-white border border-slate-100 overflow-hidden">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left text-[11px] text-slate-400 uppercase tracking-wide border-b border-slate-100">
                  <th class="px-4 py-3 font-medium">From</th>
                  <th class="px-4 py-3 font-medium">Subject</th>
                  <th class="px-4 py-3 font-medium">Status</th>
                  <th class="px-4 py-3 font-medium text-right">Received</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50">
                <?php
                  $dashStatusStyles = [
                      'new'         => 'bg-amber-50 text-amber-600',
                      'in_progress' => 'bg-blue-50 text-blue-600',
                      'resolved'    => 'bg-green-50 text-green-600',
                  ];
                ?>
                <?php foreach ($recentMessages as $i => $m): ?>
                  <tr class="row-enter row-hover hover:bg-slate-50" style="animation-delay: <?= $i * 40 ?>ms">
                    <td class="px-4 py-3">
                      <a href="messages.php" class="block font-medium text-slate-800 hover:text-brand hover:underline"><?= htmlspecialchars($m['name']) ?></a>
                      <span class="block text-xs text-slate-400 truncate max-w-[200px]"><?= htmlspecialchars($m['email']) ?></span>
                    </td>
                    <td class="px-4 py-3 text-slate-600 truncate max-w-[260px]"><?= htmlspecialchars($m['subject']) ?></td>
                    <td class="px-4 py-3">
                      <span class="text-[11px] font-semibold rounded-full px-2.5 py-1 <?= $dashStatusStyles[$m['status']] ?? 'bg-slate-100 text-slate-500' ?>">
                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $m['status']))) ?>
                      </span>
                    </td>
                    <td class="px-4 py-3 text-right text-xs text-slate-400 whitespace-nowrap">
                      <?= htmlspecialchars(date('M j, g:i A', strtotime($m['created_at']))) ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>

    </main>
  </div>
</div>

<script>
(function () {

  <?php if (!empty($chartRows)): ?>
  var ctx = document.getElementById('bookings-chart');
  if (ctx && window.Chart) {
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
          label: 'Bookings',
          data: <?= json_encode($chartValues) ?>,
          borderColor: '#0F766E',
          backgroundColor: 'rgba(15, 118, 110, 0.08)',
          tension: 0.3,
          fill: true,
          pointRadius: 2
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
      }
    });
  }
  <?php endif; ?>
})();
</script>

</body>
</html>