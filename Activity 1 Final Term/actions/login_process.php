<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_post();

$email = Validator::clean($_POST['email'] ?? '');
$password = (string)($_POST['password'] ?? '');
if ($email === '' || $password === '') {
    flash('error', 'Email and password are required.');
    $_SESSION['old'] = ['email' => $email];
    redirect('login.php');
}
[$ok, $result] = Auth::attempt($email, $password);
if (!$ok) {
    flash('error', $result);
    $_SESSION['old'] = ['email' => $email];
    redirect('login.php');
}
redirect(Auth::homeFor($result['role']));
