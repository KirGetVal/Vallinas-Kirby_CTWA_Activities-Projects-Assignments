<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
$u = Auth::requireRole(ROLE_ADMIN);
$role = ROLE_USER;
require __DIR__ . '/../includes/accounts_page.php';
