<?php
// paynow_return.php
require_once __DIR__ . "/core.php";

$ref = $_GET['ref'] ?? '';
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>GhostFund Payment Return</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            bg:'#0a0a0a',
            panel:'#121212',
            text:'#e6e6e6',
            muted:'#9a9a9a',
            accent:'#8ecdf0',
            border:'#1f1f1f'
          }
        }
      }
    }
  </script>
</head>
<body class="min-h-screen bg-bg text-text">
  <header class="flex items-center gap-3 px-6 py-4 border-b border-border">
    <div class="font-semibold tracking-wide">GhostFund</div>
  </header>

  <main class="max-w-xl mx-auto px-6 py-12">
    <section class="bg-panel border border-border rounded-xl p-6">
      <h1 class="text-2xl mb-2">Payment Received</h1>
      <?php if ($ref): ?>
        <p class="text-muted mb-4">Your payment reference is <span class="font-mono"><?= htmlspecialchars($ref) ?></span>.</p>
      <?php else: ?>
        <p class="text-muted mb-4">Your payment has been processed.</p>
      <?php endif; ?>
      <p class="text-muted">You will receive an email once your contribution is confirmed in the GhostFund cycle.</p>
      <a href="index.php" class="inline-block mt-6 bg-accent text-bg font-semibold px-4 py-2 rounded-lg">Back to Home</a>
    </section>
  </main>

  <footer class="px-6 py-6 text-center border-t border-border text-muted">GhostFund — Zimbabwe</footer>
</body>
</html>
