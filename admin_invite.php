<?php
// admin_invite.php
require_once __DIR__ . "/core.php";

// Require admin key in header
gf_require_admin();

$name  = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');

if (!$name || !gf_is_email($email)) {
  http_response_code(400);
  exit("Invalid input");
}

// Invite company (creates/updates record, sends consent email)
gf_invite_company($name, $email, $phone);

echo "OK";
