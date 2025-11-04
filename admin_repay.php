<?php
// admin_repay.php
require_once __DIR__ . "/core.php";

// Require admin key in header
gf_require_admin();

$fundingId = (int)($_POST['funding_id'] ?? 0);
$repaid    = (float)($_POST['repaid_amount'] ?? 0);

if ($fundingId <= 0 || $repaid <= 0) {
  http_response_code(400);
  exit("Invalid input");
}

gf_mark_repayment($fundingId, $repaid);

echo "OK";
