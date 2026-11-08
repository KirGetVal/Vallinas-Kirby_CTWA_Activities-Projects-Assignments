<?php
require_once __DIR__ . '/core/bootstrap.php';
// Logout is a state change: POST + CSRF only (prevents forced-logout via <img src=logout.php>).
require_post();
Auth::logout();
session_start();
flash('success', 'You have been signed out.');
redirect('login.php');
