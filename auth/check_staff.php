<?php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/roles.php';

require_roles($conn, STAFF_ROLES, "Access denied. Staff only");

?>
