<?php
// core.php — GhostFund full core

/////////////////////
// CONFIG (hardcoded)
/////////////////////
$GF_DB_HOST = "127.0.0.1";
$GF_DB_NAME = "ghostfund";
$GF_DB_USER = "root";
$GF_DB_PASS = "password";
$GF_ADMIN_KEY = "supersecretadminkey";
$GF_APP_URL   = "http://localhost";
$GF_SMTP_HOST = "smtp.example.com";
$GF_SMTP_PORT = 587;
$GF_SMTP_USER = "smtp_user";
$GF_SMTP_PASS = "smtp_pass";
$GF_MAIL_FROM = "GhostFund <no-reply@ghostfund.co.zw>";
$GF_MARGIN_PERCENT = 10;
$GF_DEFAULT_REPAYMENT_DAYS = 7;
$GF_PAYNOW_ID  = "YOUR_PAYNOW_INTEGRATION_ID";
$GF_PAYNOW_KEY = "YOUR_PAYNOW_INTEGRATION_KEY";
$GF_PAYNOW_URL = "https://www.paynow.co.zw/interface/initiatetransaction";

/////////////////////
// CORE (PDO)
/////////////////////
function gf_db(): PDO {
  static $pdo = null;
  global $GF_DB_HOST,$GF_DB_NAME,$GF_DB_USER,$GF_DB_PASS;
  if ($pdo) return $pdo;
  $pdo = new PDO("mysql:host={$GF_DB_HOST};dbname={$GF_DB_NAME};charset=utf8mb4", $GF_DB_USER, $GF_DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  return $pdo;
}

/////////////////////
// MAIL (PHPMailer)
/////////////////////
function gf_parse_from($from): array {
  if (preg_match('/(.*)<(.*)>/', $from, $m)) return ['name'=>trim($m[1]), 'email'=>trim($m[2])];
  return ['name'=>'GhostFund','email'=>$from];
}
function gf_send_mail(string $to, string $subject, string $html): bool {
  global $GF_SMTP_HOST,$GF_SMTP_PORT,$GF_SMTP_USER,$GF_SMTP_PASS,$GF_MAIL_FROM;
  require_once __DIR__ . "/PHPMailer.php";
  require_once __DIR__ . "/SMTP.php";
  require_once __DIR__ . "/Exception.php";
  $from = gf_parse_from($GF_MAIL_FROM);
  $mail = new PHPMailer\PHPMailer\PHPMailer(true);
  try {
    $mail->isSMTP();
    $mail->Host = $GF_SMTP_HOST;
    $mail->Port = $GF_SMTP_PORT;
    $mail->SMTPAuth = true;
    $mail->Username = $GF_SMTP_USER;
    $mail->Password = $GF_SMTP_PASS;
    $mail->setFrom($from['email'], $from['name']);
    $mail->addAddress($to);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $html;
    return $mail->send();
  } catch (\Throwable $e) { return false; }
}

/////////////////////
// HELPERS
/////////////////////
function gf_is_email($e): bool { return filter_var($e, FILTER_VALIDATE_EMAIL) !== false; }
function gf_receipt(): string { return "GF-" . time() . "-" . random_int(1000, 9999); }
function gf_due_in_days(int $days): string { return date("Y-m-d 00:00:00", strtotime("+$days days")); }
function gf_require_admin(): void {
  global $GF_ADMIN_KEY;
  $key = $_SERVER['HTTP_X_ADMIN_KEY'] ?? '';
  if ($key !== $GF_ADMIN_KEY) { http_response_code(401); exit("Unauthorized"); }
}

/////////////////////
// EMAIL HTML
/////////////////////
function gf_email_invite(string $companyName, string $consentLink): string {
  return "<div style='background:#0b0b0b;color:#e6e6e6;padding:24px;font-family:system-ui'><h1>GhostFund</h1><p>Hello ".htmlspecialchars($companyName).",</p><p>You may be randomly funded by GhostFund. You agree to return principal + margin within the agreed period. Failure to return results in removal from GhostFund.</p><p><a href='".htmlspecialchars($consentLink)."' style='color:#8ecdf0'>Accept & Join GhostFund</a></p></div>";
}
function gf_email_contributor_receipt(float $amount, string $receipt): string {
  return "<div style='background:#0b0b0b;color:#e6e6e6;padding:24px;font-family:system-ui'><h1>GhostFund</h1><p>Your contribution has been released.</p><p>Amount: ".number_format($amount,2)."</p><p>Receipt: ".htmlspecialchars($receipt)."</p></div>";
}
function gf_email_company_funding(string $companyName, float $amount, string $dueDate): string {
  return "<div style='background:#0b0b0b;color:#e6e6e6;padding:24px;font-family:system-ui'><h1>GhostFund</h1><p>".htmlspecialchars($companyName)." has been funded with ".number_format($amount,2).".</p><p>Return by ".htmlspecialchars($dueDate)."</p></div>";
}
function gf_email_reminder(string $companyName, float $amount, string $dueDate): string {
  return "<div style='background:#0b0b0b;color:#e6e6e6;padding:24px;font-family:system-ui'><h1>GhostFund</h1><p>Reminder: Return by ".htmlspecialchars($dueDate).".</p><p>Amount received: ".number_format($amount,2)."</p></div>";
}
function gf_email_return_notice(float $returned): string {
  return "<div style='background:#0b0b0b;color:#e6e6e6;padding:24px;font-family:system-ui'><h1>GhostFund</h1><p>Your GhostFund cycle has returned.</p><p>Returned: ".number_format($returned,2)."</p></div>";
}

/////////////////////
// DATA ACCESS
/////////////////////
function gf_upsert_contributor(string $name, string $email, ?string $phone): int {
  $pdo = gf_db();
  $s = $pdo->prepare("SELECT id FROM contributors WHERE email=?");
  $s->execute([$email]);
  if ($r = $s->fetch()) {
    $pdo->prepare("UPDATE contributors SET name=?, phone=? WHERE id=?")->execute([$name, $phone, (int)$r['id']]);
    return (int)$r['id'];
  } else {
    $pdo->prepare("INSERT INTO contributors (name,email,phone) VALUES (?,?,?)")->execute([$name,$email,$phone]);
    return (int)$pdo->lastInsertId();
  }
}
function gf_random_active_company(): array {
  $pdo = gf_db();
  $companies = $pdo->query("SELECT id,name,email FROM companies WHERE status='ACTIVE'")->fetchAll();
  if (!$companies) { http_response_code(503); exit("No active companies"); }
  return $companies[array_rand($companies)];
}
function gf_open_cycle_id(): int {
  $pdo = gf_db();
  $r = $pdo->query("SELECT id FROM cycles WHERE status='OPEN' ORDER BY id DESC LIMIT 1")->fetch();
  if ($r) return (int)$r['id'];
  $pdo->exec("INSERT INTO cycles (status) VALUES ('OPEN')");
  return (int)$pdo->lastInsertId();
}
function gf_create_contribution(int $contributorId, float $amount, int $companyId, int $cycleId, string $receipt): int {
  $pdo = gf_db();
  $stmt = $pdo->prepare("INSERT INTO contributions (contributor_id,amount,assigned_company_id,cycle_id,receipt_number) VALUES (?,?,?,?,?)");
  $stmt->execute([$contributorId,$amount,$companyId,$cycleId,$receipt]);
  return (int)$pdo->lastInsertId();
}
function gf_create_funding(int $companyId, int $contributionId, float $amount, string $dueDate): int {
  $pdo = gf_db();
  $stmt = $pdo->prepare("INSERT INTO funding (company_id,contribution_id,amount_received,repayment_due_date) VALUES (?,?,?,?)");
  $stmt->execute([$companyId,$contributionId,$amount,$dueDate]);
  return (int)$pdo->lastInsertId();
}
function gf_log_notification(string $email, string $type, ?int $relatedId = null): void {
  $pdo = gf_db();
  $stmt = $pdo->prepare("INSERT INTO notifications_log (recipient_email,type,related_id) VALUES (?,?,?)");
  $stmt->execute([$email,$type,$relatedId]);
}
function gf_invite_company(string $name, string $email, ?string $phone): void {
  global $GF_APP_URL;
  $pdo = gf_db();
  $stmt = $pdo->prepare("INSERT INTO companies (name,email,phone,status) 
                         VALUES (?,?,?,'PENDING')
                         ON DUPLICATE KEY UPDATE 
                           name=VALUES(name),
                           phone=VALUES(phone),
                           status='PENDING'");
  $stmt->execute([$name,$email,$phone]);

  $link = $GF_APP_URL . "/consent.php?email=" . urlencode($email);
  $html = gf_email_invite($name, $link);

  gf_send_mail($email, "Invitation to join GhostFund", $html);
  gf_log_notification($email, "INVITE", null);
}

function gf_mark_consent_active(string $email): void {
  $pdo = gf_db();
  $stmt = $pdo->prepare("UPDATE companies SET status='ACTIVE', consent_date=NOW() WHERE email=?");
  $stmt->execute([$email]);
  gf_log_notification($email, "CONSENT", null);
}

function gf_reminders_send(): int {
  $pdo = gf_db();
  $soon = (new DateTimeImmutable())->modify('+3 days')->format('Y-m-d 00:00:00');
  $stmt = $pdo->prepare("SELECT f.id, f.amount_received, f.repayment_due_date, comp.email, comp.name
                         FROM funding f JOIN companies comp ON comp.id=f.company_id
                         WHERE f.repayment_status='PENDING' AND f.repayment_due_date <= ?");
  $stmt->execute([$soon]);
  $count = 0;
  while ($f = $stmt->fetch()) {
    gf_send_mail($f['email'], "GhostFund Repayment Reminder", gf_email_reminder($f['name'], (float)$f['amount_received'], substr($f['repayment_due_date'],0,10)));
    gf_log_notification($f['email'], "REMINDER", (int)$f['id']);
    $count++;
  }
  return $count;
}

function gf_enforce_blacklist(): int {
  $pdo = gf_db();
  $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
  $overdue = $pdo->query("SELECT DISTINCT f.company_id FROM funding f
                          JOIN companies c ON c.id=f.company_id
                          WHERE f.repayment_status='PENDING' AND f.repayment_due_date < '$now' AND c.status!='BLACKLISTED'")->fetchAll();
  $affected = 0;
  foreach ($overdue as $o) {
    $cid = (int)$o['company_id'];
    $pdo->prepare("UPDATE companies SET status='BLACKLISTED' WHERE id=?")->execute([$cid]);
    $pdo->prepare("INSERT INTO blacklist_log (company_id,reason) VALUES (?,?)")->execute([$cid,'Repayment overdue']);
    $affected++;
  }
  return $affected;
}

function gf_mark_repayment(int $fundingId, float $repaidAmount): void {
  global $GF_MARGIN_PERCENT;
  $pdo = gf_db();
  $pdo->prepare("UPDATE funding SET repaid_amount=?, repaid_at=NOW(), repayment_status='PAID' WHERE id=?")
      ->execute([$repaidAmount,$fundingId]);
  $q = $pdo->prepare("SELECT c.amount AS contrib_amount, ctr.email AS contrib_email
                      FROM funding f
                      JOIN contributions c ON c.id=f.contribution_id
                      JOIN contributors ctr ON ctr.id=c.contributor_id
                      WHERE f.id=?");
  $q->execute([$fundingId]);
  $row = $q->fetch();
  if ($row) {
    $returnAmount = (float)$row['contrib_amount'] * (1 + $GF_MARGIN_PERCENT/100);
    gf_send_mail($row['contrib_email'], "GhostFund Cycle Return", gf_email_return_notice($returnAmount));
    gf_log_notification($row['contrib_email'], "RETURN_NOTICE", $fundingId);
  }
}

function gf_paynow_create(string $ref, string $email, float $amount, string $returnUrl, string $resultUrl): array {
  global $GF_PAYNOW_ID, $GF_PAYNOW_KEY, $GF_PAYNOW_URL;
  $values = [
    'id'            => $GF_PAYNOW_ID,
    'reference'     => $ref,
    'amount'        => number_format($amount, 2, '.', ''),
    'additionalinfo'=> 'GhostFund Contribution',
    'returnurl'     => $returnUrl,
    'resulturl'     => $resultUrl,
    'authemail'     => $email
  ];
  $fields_string = http_build_query($values);
  $hash = strtoupper(hash("sha512", $fields_string . "&" . $GF_PAYNOW_KEY));
  $fields_string .= "&hash=" . $hash;

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $GF_PAYNOW_URL);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $result = curl_exec($ch);
  curl_close($ch);

  parse_str($result, $response);
  return $response;
}

function gf_finalize_paid_contribution(string $reference, string $email, float $amount): void {
  global $GF_DEFAULT_REPAYMENT_DAYS;
  $pdo = gf_db();
  $stmt = $pdo->prepare("SELECT id,name,phone FROM contributors WHERE email=?");
  $stmt->execute([$email]);
  $contributor = $stmt->fetch();
  if (!$contributor) {
    // auto-create contributor if Paynow returned an email not yet in DB
    $pdo->prepare("INSERT INTO contributors (name,email) VALUES (?,?)")->execute([$email,$email]);
    $contributorId = (int)$pdo->lastInsertId();
  } else {
    $contributorId = (int)$contributor['id'];
  }
  $company  = gf_random_active_company();
  $cycleId  = gf_open_cycle_id();
  $receipt  = $reference ?: gf_receipt();
  $contribId= gf_create_contribution($contributorId, $amount, (int)$company['id'], $cycleId, $receipt);
  $due      = gf_due_in_days($GF_DEFAULT_REPAYMENT_DAYS);
  gf_create_funding((int)$company['id'], $contribId, $amount, $due);
  gf_send_mail($email, "GhostFund Receipt", gf_email_contributor_receipt($amount, $receipt));
  gf_log_notification($email, "FUNDING_RECEIPT", $contribId);
  gf_send_mail($company['email'], "GhostFund Funding Notice", gf_email_company_funding($company['name'], $amount, substr($due,0,10)));
  gf_log_notification($company['email'], "FUNDING_RECEIPT", $contribId);
}
