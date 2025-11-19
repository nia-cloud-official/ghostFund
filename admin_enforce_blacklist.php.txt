<?php
// admin_enforce_blacklist.php
require_once __DIR__ . "/core.php";

// Require admin key in header
gf_require_admin();

$affected = gf_enforce_blacklist();

echo "Blacklisted companies: $affected";
