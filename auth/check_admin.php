<?php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/roles.php';

require_roles($conn, ['Admin'], "Access denied. Admin only");

?>
