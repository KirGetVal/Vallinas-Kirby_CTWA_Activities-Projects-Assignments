<?php
require_once __DIR__ . '/core/bootstrap.php';
$u = Auth::user();
redirect($u ? Auth::homeFor($u['role']) : 'login.php');
