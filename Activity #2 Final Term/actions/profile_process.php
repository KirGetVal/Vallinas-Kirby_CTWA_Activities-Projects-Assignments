<?php
require_once __DIR__ . '/../core/bootstrap.php';
$u = Auth::require();
require_post();
$back = $u['role'] . '/profile.php';
$v = new Validator($_POST);

if (($_POST['action'] ?? '') === 'profile') {
    $v->required('name', 'Name')->length('name', 'Name', 2, 80);
    $phone = $v->get('phone');
    if ($phone !== '' && !preg_match('/^[0-9+()\-\s]{7,20}$/', $phone)) { $v->fail('phone', 'Enter a valid phone number.'); }
    if ($v->passes()) {
        Storage::update('users', (int)$u['id'], ['name' => $v->get('name'), 'phone' => $phone]);
        log_activity('Updated profile');
        flash('success', 'Profile updated.');
    }
} elseif (($_POST['action'] ?? '') === 'password') {
    if (!password_verify((string)($_POST['current'] ?? ''), $u['password'])) { $v->fail('current', 'Current password is incorrect.'); }
    $v->password('password');
    if ((string)($_POST['password'] ?? '') !== (string)($_POST['confirm'] ?? '')) { $v->fail('confirm', 'Passwords do not match.'); }
    if ($v->passes()) {
        Storage::update('users', (int)$u['id'], ['password' => password_hash((string)$_POST['password'], PASSWORD_DEFAULT)]);
        session_regenerate_id(true);
        log_activity('Changed password');
        flash('success', 'Password updated.');
    }
} else { http_response_code(400); exit('Unknown action.'); }

if (!$v->passes()) { $_SESSION['errors'] = $v->errors(); }
redirect($back);
