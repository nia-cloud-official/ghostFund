<?php
// index.php
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>GhostFund</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: { bg:'#0a0a0a', panel:'#121212', text:'#e6e6e6', muted:'#9a9a9a', accent:'#8ecdf0', border:'#1f1f1f' }
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
      <h1 class="text-2xl mb-2">Capital flows unseen</h1>
      <p class="text-muted mb-6">You give. They grow. The cycle returns.</p>

      <form method="post" action="contribute.php" class="space-y-4">
        <div>
          <label class="text-sm text-muted">Name</label>
          <input name="name" required minlength="2" class="mt-1 w-full bg-bg border border-border rounded-lg px-3 py-2 outline-none focus:border-gray-600">
        </div>
        <div>
          <label class="text-sm text-muted">Email</label>
          <input name="email" type="email" required class="mt-1 w-full bg-bg border border-border rounded-lg px-3 py-2 outline-none focus:border-gray-600">
        </div>
        <div>
          <label class="text-sm text-muted">Phone (optional)</label>
          <input name="phone" class="mt-1 w-full bg-bg border border-border rounded-lg px-3 py-2 outline-none focus:border-gray-600">
        </div>
        <div>
          <label class="text-sm text-muted">Amount</label>
          <input name="amount" type="number" min="1" step="0.01" required class="mt-1 w-full bg-bg border border-border rounded-lg px-3 py-2 outline-none focus:border-gray-600">
        </div>

        <button class="bg-accent text-bg font-semibold px-4 py-2 rounded-lg">Proceed to payment</button>
      </form>
    </section>
  </main>

  <footer class="px-6 py-6 text-center border-t border-border text-muted">GhostFund — Zimbabwe</footer>
</body>
</html>
