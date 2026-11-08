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

// Check + insert inside one lock so two simultaneous registrations can't share an email.
$created = null;
if ($v->passes()) {
    Storage::mutate('users', function (array $rows) use ($email, $v, $phone, &$created) {
        foreach ($rows as $r) {
            if (strtolower($r['email']) === $email) {
                return $rows;
            }
        }
        $max = 0;
        foreach ($rows as $r) { $max = max($max, (int)$r['id']); }
        $created = [
            'id' => $max + 1, 'name' => $v->get('name'), 'email' => $email,
            'password' => password_hash((string)$_POST['password'], PASSWORD_DEFAULT),
            'role' => ROLE_USER, // self-registration can only ever create regular users
            'phone' => $phone, 'active' => true, 'created_at' => now(),
        ];
        $rows[] = $created;
        return $rows;
    });
    if (!$created) {
        $v->fail('email', 'That email is already registered.');
    }
}
if (!$v->passes()) {
    $_SESSION['errors'] = $v->errors();
    $_SESSION['old'] = ['name' => $v->get('name'), 'email' => $v->get('email'), 'phone' => $phone];
    redirect('register.php');
}
$_SESSION['user_id'] = (int)$created['id'];
session_regenerate_id(true);
log_activity('Registered a new account', (int)$created['id']);
flash('success', 'Welcome! Your account has been created.');
redirect(Auth::homeFor(ROLE_USER));
