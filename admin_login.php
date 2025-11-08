<?php
session_start();
require_once __DIR__ . "/core.php";

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    // Store your admin credentials in core.php
    if ($user === $GF_ADMIN_USER && $pass === $GF_ADMIN_PASS) {
        $_SESSION['admin'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $error = "Invalid login";
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-100 flex items-center justify-center h-screen">
  <form method="post" class="bg-gray-800 p-8 rounded shadow max-w-sm w-full space-y-4">
    <h1 class="text-xl font-bold">GhostFund Admin Login</h1>
    <?php if ($error): ?><div class="text-red-400"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <input type="text" name="username" placeholder="Username" class="w-full p-2 rounded bg-gray-700 border border-gray-600">
    <input type="password" name="password" placeholder="Password" class="w-full p-2 rounded bg-gray-700 border border-gray-600">
    <button class="w-full bg-blue-500 hover:bg-blue-600 text-white py-2 rounded">Login</button>
  </form>
</body>
</html>
