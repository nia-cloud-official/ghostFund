<?php
// consent.php
require_once __DIR__ . "/core.php";

$email = trim($_GET['email'] ?? '');
if (!$email) {
  http_response_code(400);
  exit("Missing email");
}

gf_mark_consent_active($email);

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>GhostFund Consent</title>
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
  <main class="max-w-xl mx-auto px-6 py-12">
    <section class="bg-panel border border-border rounded-xl p-6">
      <h1 class="text-2xl mb-2">GhostFund</h1>
      <p class="text-muted"><?= htmlspecialchars($email) ?> is now an active GhostFund partner.</p>
      <a href="index.php" class="inline-block mt-4 bg-accent text-bg font-semibold px-4 py-2 rounded-lg">Back to Home</a>
    </section>
  </main>
</body>
</html>
