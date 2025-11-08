<?php
// paynow_result.php
require_once __DIR__ . "/core.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit("Method Not Allowed");
}

$data = $_POST;
if (!$data) {
  http_response_code(400);
  exit("No data");
}

// Build hash string for verification
$hashString = "";
foreach ($data as $key => $value) {
  if (strtolower($key) === "hash") continue;
  $hashString .= $value;
}

global $GF_PAYNOW_KEY;
// $expectedHash = strtoupper(hash("sha512", $hashString . $GF_PAYNOW_KEY));

//if (!isset($data['hash']) || $expectedHash !== $data['hash']) {
  //http_response_code(400);
  //exit("Invalid hash");
//}

$status = strtolower($data['status'] ?? '');
if ($status === 'paid') {
  $reference = $data['reference'] ?? gf_receipt();
  $email     = $data['authemail'] ?? '';
  $amount    = (float)($data['amount'] ?? 0);

  if ($email && $amount > 0) {
    gf_finalize_paid_contribution($reference, $email, $amount);
  }
}

// Always respond with OK so Paynow knows we received the callback
echo "OK";
