<?php
require_once __DIR__ . "/core.php";
session_start();

// Simple login check
if (empty($_SESSION['admin'])) {
  header("Location: admin_login.php");
  exit;
}

// Direct DB connection (adjust credentials)
$mysqli = mysqli_connect("db.fr-pari1.bengt.wasmernet.com", "a7a889bd7a068000a0f9016a5f63", "0690a7a8-89bd-7bf4-8000-47cf16b84cd9", "ghostFund", 10272);
if (!$mysqli) {
  die("DB connection failed: " . mysqli_connect_error());
}

// Handle actions
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['invite'])) {
    $name  = mysqli_real_escape_string($mysqli, $_POST['name']);
    $email = mysqli_real_escape_string($mysqli, $_POST['email']);
    $phone = mysqli_real_escape_string($mysqli, $_POST['phone']);
    mysqli_query($mysqli, "INSERT INTO companies (name,email,phone,status,created_at) VALUES ('$name','$email','$phone','PENDING',NOW())");
    $msg = "Invited $name";
  }
  if (isset($_POST['repay'])) {
    $fundingId = (int)$_POST['funding_id'];
    $amount    = (float)$_POST['repaid_amount'];
    mysqli_query($mysqli, "UPDATE funding SET repaid_amount=repaid_amount+$amount, repayment_status='PAID' WHERE id=$fundingId");
    $msg = "Repayment recorded";
  }
  if (isset($_POST['remind'])) {
    // Example: mark reminders sent
    mysqli_query($mysqli, "UPDATE funding SET reminder_sent=1 WHERE repayment_status='PENDING'");
    $msg = "Reminders flagged";
  }
  if (isset($_POST['blacklist'])) {
    mysqli_query($mysqli, "UPDATE companies SET status='BLACKLISTED' WHERE id IN (SELECT company_id FROM funding WHERE repayment_status='PENDING' AND repayment_due_date<NOW())");
    $msg = "Blacklist enforced";
  }
}

// Fetch data
$companies = mysqli_query($mysqli, "SELECT * FROM companies ORDER BY created_at DESC");
$funding   = mysqli_query($mysqli, "SELECT f.*, c.name AS company_name FROM funding f JOIN companies c ON c.id=f.company_id ORDER BY f.created_at DESC LIMIT 20");

// Stats
list($totalContributions) = mysqli_fetch_row(mysqli_query($mysqli, "SELECT SUM(amount) FROM contributions"));
list($totalFunding)       = mysqli_fetch_row(mysqli_query($mysqli, "SELECT SUM(amount_received) FROM funding"));
list($totalRepaid)        = mysqli_fetch_row(mysqli_query($mysqli, "SELECT SUM(repaid_amount) FROM funding"));
list($activeCompanies)    = mysqli_fetch_row(mysqli_query($mysqli, "SELECT COUNT(*) FROM companies WHERE status='ACTIVE'"));


$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['invite'])) {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    if ($name && gf_is_email($email)) {
      gf_invite_company($name, $email, $phone);
      $msg = "Invited $name";
    }
  }
  if (isset($_POST['repay'])) {
    $fundingId = (int)$_POST['funding_id'];
    $amount    = (float)$_POST['repaid_amount'];
    if ($fundingId > 0 && $amount > 0) {
      gf_mark_repayment($fundingId, $amount);
      $msg = "Repayment recorded";
    }
  }
  if (isset($_POST['remind'])) {
    $count = gf_reminders_send();
    $msg = "Reminders sent: $count";
  }
  if (isset($_POST['blacklist'])) {
    $count = gf_enforce_blacklist();
    $msg = "Blacklisted: $count";
  }
}

// Summary stats
list($totalContributions) = mysqli_fetch_row(mysqli_query($mysqli, "SELECT SUM(amount) FROM contributions"));
$totalContributions = $totalContributions ?: 0;

list($totalFunding) = mysqli_fetch_row(mysqli_query($mysqli, "SELECT SUM(amount_received) FROM funding"));
$totalFunding = $totalFunding ?: 0;

list($totalRepaid) = mysqli_fetch_row(mysqli_query($mysqli, "SELECT SUM(repaid_amount) FROM funding"));
$totalRepaid = $totalRepaid ?: 0;

list($activeCompanies) = mysqli_fetch_row(mysqli_query($mysqli, "SELECT COUNT(*) FROM companies WHERE status='ACTIVE'"));
$activeCompanies = $activeCompanies ?: 0;

// Top companies
$res = mysqli_query($mysqli, "SELECT c.name, SUM(f.amount_received) AS total 
                              FROM funding f 
                              JOIN companies c ON c.id=f.company_id 
                              GROUP BY c.id 
                              ORDER BY total DESC 
                              LIMIT 10");
$companyNames = [];
$companyTotals = [];
while ($row = mysqli_fetch_assoc($res)) {
    $companyNames[]  = $row['name'];
    $companyTotals[] = (float)$row['total'];
}

// Repayment distribution
$res = mysqli_query($mysqli, "SELECT repayment_status, COUNT(*) AS cnt FROM funding GROUP BY repayment_status");
$repayLabels = [];
$repayCounts = [];
while ($row = mysqli_fetch_assoc($res)) {
    $repayLabels[] = $row['repayment_status'];
    $repayCounts[] = (int)$row['cnt'];
}

// Contributions over time
$res = mysqli_query($mysqli, "SELECT DATE_FORMAT(created_at, '%Y-%m') AS m, SUM(amount) AS total 
                              FROM contributions 
                              GROUP BY m 
                              ORDER BY m");
$months = [];
$monthTotals = [];
while ($row = mysqli_fetch_assoc($res)) {
    $months[]      = $row['m'];
    $monthTotals[] = (float)$row['total'];
}

// Companies list
$companies = mysqli_query($mysqli, "SELECT * FROM companies ORDER BY created_at DESC");

// Funding list
$funding = mysqli_query($mysqli, "SELECT f.*, c.name AS company_name 
                                  FROM funding f 
                                  JOIN companies c ON c.id=f.company_id 
                                  ORDER BY f.created_at DESC 
                                  LIMIT 20");
?>
<!doctype html>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>GhostFund Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            bg:'#0a0a0a', panel:'#121212', text:'#e6e6e6',
            muted:'#9a9a9a', accent:'#8ecdf0', border:'#1f1f1f'
          }
        }
      }
    }
  </script>
</head>
<body class="min-h-screen bg-bg text-text font-sans">

  <!-- Header -->
  <header class="bg-panel border-b border-border px-8 py-6 flex justify-between items-center shadow">
    <div>
      <h1 class="text-2xl font-bold text-accent">GhostFund Dashboard</h1>
      <p class="text-muted text-sm">Transparency • Trust • Growth</p>
    </div>
    <button class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">Logout</button>
  </header>

  <main class="max-w-7xl mx-auto px-6 py-10 space-y-12">

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-gradient-to-br from-accent to-blue-600 p-6 rounded-xl shadow-lg">
        <div class="text-3xl font-bold">$<?= number_format($totalContributions,2) ?></div>
        <div class="text-sm opacity-80">Total Contributions</div>
      </div>
      <div class="bg-gradient-to-br from-green-500 to-emerald-600 p-6 rounded-xl shadow-lg">
        <div class="text-3xl font-bold">$<?= number_format($totalFunding,2) ?></div>
        <div class="text-sm opacity-80">Total Funding</div>
      </div>
      <div class="bg-gradient-to-br from-purple-500 to-indigo-600 p-6 rounded-xl shadow-lg">
        <div class="text-3xl font-bold">$<?= number_format($totalRepaid,2) ?></div>
        <div class="text-sm opacity-80">Total Repaid</div>
      </div>
      <div class="bg-gradient-to-br from-pink-500 to-rose-600 p-6 rounded-xl shadow-lg">
        <div class="text-3xl font-bold"><?= $activeCompanies ?></div>
        <div class="text-sm opacity-80">Active Companies</div>
      </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <div class="bg-panel p-6 rounded-xl shadow-lg">
        <h2 class="text-lg font-semibold mb-4">Top Beneficiaries</h2>
        <canvas id="topCompanies"></canvas>
      </div>
      <div class="bg-panel p-6 rounded-xl shadow-lg">
        <h2 class="text-lg font-semibold mb-4">Repayment Status</h2>
        <canvas id="repayPie"></canvas>
      </div>
    </div>
    <div class="bg-panel p-6 rounded-xl shadow-lg">
      <h2 class="text-lg font-semibold mb-4">Contributions Over Time</h2>
      <canvas id="contribLine"></canvas>
    </div>

    <!-- Admin Actions -->
    <section class="bg-panel border border-border rounded-xl p-6 shadow-lg space-y-6">
      <h2 class="text-xl font-semibold mb-4">Admin Actions</h2>
      <form method="post" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input type="hidden" name="invite" value="1">
        <input type="text" name="name" placeholder="Company Name" class="bg-bg border border-border rounded p-2">
        <input type="email" name="email" placeholder="Email" class="bg-bg border border-border rounded p-2">
        <input type="text" name="phone" placeholder="Phone (optional)" class="bg-bg border border-border rounded p-2">
        <button class="col-span-3 bg-accent text-bg px-4 py-2 rounded hover:opacity-90">Invite Company</button>
      </form>
      <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="hidden" name="repay" value="1">
        <input type="number" name="funding_id" placeholder="Funding ID" class="bg-bg border border-border rounded p-2">
        <input type="number" step="0.01" name="repaid_amount" placeholder="Repaid Amount" class="bg-bg border border-border rounded p-2">
        <button class="col-span-2 bg-accent text-bg px-4 py-2 rounded hover:opacity-90">Record Repayment</button>
      </form>
      <div class="flex gap-4">
        <form method="post"><input type="hidden" name="remind" value="1"><button class="bg-accent text-bg px-4 py-2 rounded hover:opacity-90">Send Reminders</button></form>
        <form method="post"><input type="hidden" name="blacklist" value="1"><button class="bg-red-600 text-bg px-4 py-2 rounded hover:bg-red-700">Enforce Blacklist</button></form>
      </div>
    </section>

    <!-- Companies -->
    <section class="bg-panel border border-border rounded-xl p-6 shadow-lg">
      <h2 class="text-xl font-semibold mb-4">Companies</h2>
      <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
          <thead class="bg-border text-muted uppercase text-xs">
            <tr>
              <th class="px-4 py-2 text-left">ID</th>
              <th class="px-4 py-2 text-left">Name</th>
              <th class="px-4 py-2 text-left">Email</th>
              <th class="px-4 py-2 text-left">Status</th>
              <th class="px-4 py-2 text-left">Consent</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($companies as $c): ?>
            <tr class="hover:bg-gray-800">
              <td class="px-4 py-2"><?= $c['id'] ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($c['name']) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($c['email']) ?></td>
              <td class="px-4 py-2"><?= $c['status'] ?></td>
              <td class="px-4 py-2"><?= $c['consent_date'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Funding -->
    <section class="bg-panel border border-border rounded-xl p-6 shadow-lg">
      <h2 class="text-xl font-semibold mb-4">Recent Funding</h2>
      <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
          <thead class="bg-border text-muted uppercase text-xs">
            <tr>
              <th class="px-4 py-2 text-left">ID</th>
              <th class="px-4 py-2 text-left">Company</th>
              <th class="px-4 py-2 text-left">Amount</th>
              <th class="px-4 py-2 text-left">Status</th>
              <th class="px-4 py-2 text-left">Due</th>
              <th class="px-4 py-2 text-left">Repaid</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($funding as $f): ?>
            <tr class="hover:bg-gray-800">
              <td class="px-4 py-2"><?= $f['id'] ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($f['company_name']) ?></td>
              <td class="px-4 py-2">$<?= number_format($f['amount_received'],2) ?></td>
              <td class="px-4 py-2"><?= $f['repayment_status'] ?></td>
              <td class="px-4 py-2"><?= $f['repayment_due_date'] ?></td>
              <td class="px-4 py-2">$<?= number_format($f['repaid_amount'],2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Activity & Notes -->
    <section class="bg-panel border border-border rounded-xl p-6 shadow-lg">
      <h2 class="text-xl font-semibold mb-4">Activity</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-bg border border-border rounded-xl p-4">
          <h3 class="font-semibold mb-2">Latest Events</h3>
          <ul class="space-y-2 text-sm text-muted">
            <li>• Companies invited and consented will appear in the table above.</li>
            <li>• Repayments recorded update repayment health instantly.</li>
            <li>• Blacklist prevents new funding allocations for overdue accounts.</li>
          </ul>
        </div>
        <div class="bg-bg border border-border rounded-xl p-4">
          <h3 class="font-semibold mb-2">Admin Tips</h3>
          <ul class="space-y-2 text-sm text-muted">
            <li>• Screenshots of charts work great for outreach and updates.</li>
            <li>• Keep invites clean; only ACTIVE companies receive allocations.</li>
            <li>• Use reminders weekly to maintain repayment discipline.</li>
          </ul>
        </div>
      </div>
    </section>

  </main>

  <!-- Footer -->
  <footer class="px-8 py-8 text-center border-t border-border text-muted">
    GhostFund · Built for transparency and trust
  </footer>

  <!-- Charts -->
  <script>
    // Top Companies Bar Chart
    new Chart(document.getElementById('topCompanies'), {
      type: 'bar',
      data: {
        labels: <?= json_encode($companyNames) ?>,
        datasets: [{
          label: 'Total Funding',
          data: <?= json_encode($companyTotals) ?>,
          backgroundColor: '#8ecdf0',
          borderRadius: 6
        }]
      },
      options: {
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: (ctx) => '$' + Number(ctx.parsed.y).toLocaleString() } }
        },
        scales: {
          x: { ticks: { color: '#e6e6e6' }, grid: { color: '#1f1f1f' } },
          y: { ticks: { color: '#e6e6e6' }, grid: { color: '#1f1f1f' } }
        }
      }
    });

    // Repayment Pie Chart
    new Chart(document.getElementById('repayPie'), {
      type: 'pie',
      data: {
        labels: <?= json_encode($repayLabels) ?>,
        datasets: [{
          data: <?= json_encode($repayCounts) ?>,
          backgroundColor: ['#8ecdf0','#f87171','#34d399','#fbbf24','#a78bfa']
        }]
      },
      options: {
        plugins: {
          legend: { labels: { color: '#e6e6e6' } },
          tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${ctx.parsed}` } }
        }
      }
    });

    // Contributions Line Chart
    new Chart(document.getElementById('contribLine'), {
      type: 'line',
      data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
          label: 'Contributions',
          data: <?= json_encode($monthTotals) ?>,
          borderColor: '#8ecdf0',
          backgroundColor: 'rgba(142, 205, 240, 0.2)',
          fill: true,
          tension: 0.35,
          pointRadius: 3,
          pointBackgroundColor: '#8ecdf0'
        }]
      },
      options: {
        plugins: {
          legend: { labels: { color: '#e6e6e6' } },
          tooltip: { callbacks: { label: (ctx) => '$' + Number(ctx.parsed.y).toLocaleString() } }
        },
        scales: {
          x: { ticks: { color: '#e6e6e6' }, grid: { color: '#1f1f1f' } },
          y: { ticks: { color: '#e6e6e6' }, grid: { color: '#1f1f1f' } }
        }
      }
    });
  </script>

</body>
</html>

