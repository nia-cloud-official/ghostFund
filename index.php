<?php // ENTRY POINT // ?> 
<!doctype html>
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
          colors: {
            bg:'#0a0a0a', panel:'#121212', text:'#e6e6e6',
            muted:'#9a9a9a', accent:'#8ecdf0', border:'#1f1f1f'
          }
        }
      }
    }
  </script>
  <style>
    /* Ghost pulse animation */
    .pulse {
      position: relative;
      width: 120px;
      height: 120px;
      border-radius: 50%;
      background: rgba(142,205,240,0.15);
      animation: sonar 2s infinite;
    }
    @keyframes sonar {
      0% { transform: scale(1); opacity: 0.8; }
      100% { transform: scale(2.5); opacity: 0; }
    }
    .ghost {
      position: absolute;
      top: 50%; left: 50%;
      transform: translate(-50%,-50%);
      font-size: 3rem;
    }
  </style>
</head>
<body class="bg-bg text-text font-sans">

  <!-- Header -->
  <header class="px-6 py-6 text-center">
    <h1 class="text-2xl font-bold text-accent">GhostFund</h1>
  </header>

  <!-- Hero -->
  <section class="text-center py-20">
    <h2 class="text-5xl md:text-6xl font-extrabold mb-6">Funding the Unseen</h2>
    <p class="text-muted mb-10 max-w-xl mx-auto">GhostFund empowers hidden innovators with transparent funding and trust‑driven growth.</p>
    <div class="space-x-4">
      <a href="/admin.php" class="bg-accent text-bg px-6 py-3 rounded font-semibold hover:opacity-90">Enter App</a>
      <a href="/contact.html" class="bg-panel border border-border px-6 py-3 rounded font-semibold hover:bg-border">Contact Us</a>
    </div>
  </section>

  <!-- Ghost Pulse Section -->
  <section class="flex justify-center py-24">
    <a href="/admin.php" class="relative flex items-center justify-center">
      <div class="pulse"></div>
      <div class="ghost">👻</div>
    </a>
  </section>

  <!-- Newsletter -->
  <section class="bg-panel border-t border-border py-16 px-6 text-center">
    <h3 class="text-2xl font-semibold mb-4">Stay in the Loop</h3>
    <p class="text-muted mb-6">Get updates on new features and funding opportunities.</p>
    <form method="post" class="max-w-md mx-auto flex">
      <input type="email" name="email" placeholder="Your email" class="flex-grow bg-bg border border-border rounded-l px-4 py-3 focus:outline-none">
      <button class="bg-accent text-bg px-6 rounded-r hover:opacity-90">Subscribe</button>
    </form>
  </section>

  <!-- Footer -->
  <footer class="py-6 text-center text-muted text-sm border-t border-border">
    © <?= date('Y') ?> GhostFund. All rights reserved.
  </footer>

</body>
</html>
