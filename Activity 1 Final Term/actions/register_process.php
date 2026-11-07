<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_post();

$v = new Validator($_POST);
$v->required('name', 'Full name')->length('name', 'Full name', 2, 80)
  ->required('email', 'Email')->email('email')->length('email', 'Email', 5, 120);
$v->password('password');
if ((string)($_POST['password'] ?? '') !== (string)($_POST['password_confirm'] ?? '')) {
    $v->fail('password_confirm', 'Passwords do not match.');
}
$phone = $v->get('phone');
if ($phone !== '' && !preg_match('/^[0-9+()\-\s]{7,20}$/', $phone)) {
    $v->fail('phone', 'Enter a valid phone number.');
}
$email = strtolower($v->get('email'));

$newId = 0;
if ($v->passes()) {
    try {
        // The UNIQUE index on users.email makes this safe even if two people register at once.
        Database::run(
            'INSERT INTO users (name, email, password, role, phone, active, created_at) VALUES (?, ?, ?, ?, ?, 1, ?)',
            [$v->get('name'), $email, password_hash((string)$_POST['password'], PASSWORD_DEFAULT),
             ROLE_USER, // self-registration can only ever create regular users
             $phone, date('Y-m-d H:i:s')]
        );
        $newId = (int)Database::pdo()->lastInsertId();
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $v->fail('email', 'That email is already registered.');
        } else {
            throw $e;
        }
    }
}
if (!$v->passes()) {
    $_SESSION['errors'] = $v->errors();
    $_SESSION['old'] = ['name' => $v->get('name'), 'email' => $v->get('email'), 'phone' => $phone];
    redirect('register.php');
}
session_regenerate_id(true);
$_SESSION['user_id'] = $newId;
log_activity('Registered a new account', $newId);
flash('success', 'Welcome! Your account has been created.');
redirect(Auth::homeFor(ROLE_USER));
