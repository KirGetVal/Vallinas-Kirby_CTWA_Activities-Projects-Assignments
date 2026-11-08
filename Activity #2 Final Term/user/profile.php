<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
$u = Auth::requireRole(ROLE_USER);
require __DIR__ . '/../includes/profile_page.php';
