<?php
// admin_remind.php
require_once __DIR__ . "/core.php";

// Require admin key in header
gf_require_admin();

$count = gf_reminders_send();

echo "Reminders sent: $count";
