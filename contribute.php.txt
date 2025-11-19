<?php
// contribute.php
require_once __DIR__ . "/core.php";

$name   = trim($_POST['name'] ?? '');
$email  = trim($_POST['email'] ?? '');
$phone  = trim($_POST['phone'] ?? '');
$amount = (float)($_POST['amount'] ?? 0);

if (!$name || !gf_is_email($email) || $amount <= 0) {
  http_response_code(400);
  exit("Invalid input");
}

$receipt = gf_receipt();

// Initiate Paynow payment
global $GF_APP_URL;
$returnUrl = $GF_APP_URL . "/paynow_return.php?ref=" . urlencode($receipt);
$resultUrl = $GF_APP_URL . "/paynow_result.php";
$response  = gf_paynow_create($receipt, $email, $amount, $returnUrl, $resultUrl);

if (isset($response['status']) && $response['status'] === 'Ok') {
  header("Location: " . $response['browserurl']);
  exit;
} else {
  echo "<!doctype html><html><head><meta charset='utf-8'><script src='https://cdn.tailwindcss.com'></script>
  <script>tailwind.config={theme:{extend:{colors:{bg:'#0a0a0a',panel:'#121212',text:'#e6e6e6',muted:'#9a9a9a',accent:'#8ecdf0',border:'#1f1f1f'}}}}</script></head>
  <body class='min-h-screen bg-bg text-text'><main class='max-w-xl mx-auto px-6 py-12'>
  <section class='bg-panel border border-border rounded-xl p-6'>
  <h1 class='text-2xl mb-2'>Payment Error</h1>
  <p class='text-muted'>Failed to initiate payment.</p>
  <pre class='text-sm text-red-400 bg-bg border border-border rounded p-2 overflow-x-auto'>".htmlspecialchars(print_r($response,true))."</pre>
  <a href='index.php' class='inline-block mt-4 bg-accent text-bg font-semibold px-4 py-2 rounded-lg'>Back</a>
  </section></main></body></html>";
}
