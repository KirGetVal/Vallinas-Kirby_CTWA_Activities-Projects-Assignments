<?php
// Administrator-only account management for complainants and staff.
require_once __DIR__ . '/../core/bootstrap.php';
$u = Auth::requireRole(ROLE_ADMIN);
require_post();

$back = ($_POST['back'] ?? '') === 'staff' ? 'admin/staff.php' : 'admin/users.php';
$fail = function (string $m) use ($back): never { flash('error', $m); redirect($back); };
$action = (string)($_POST['action'] ?? '');

function email_taken(string $email, int $exceptId = 0): bool
{
    foreach (Storage::read('users') as $x) {
        if (strtolower($x['email']) === strtolower($email) && (int)$x['id'] !== $exceptId) { return true; }
    }
    return false;
}
function validate_profile(array $post, int $exceptId = 0): array
{
    $v = new Validator($post);
    $v->required('name', 'Full name')->length('name', 'Full name', 2, 100)
      ->required('email', 'Email')->email('email')->length('email', 'Email', 5, 150)
      ->length('phone', 'Phone', 0, 30);
    if ($v->passes() && email_taken($v->get('email'), $exceptId)) { $v->fail('email', 'That email address is already registered.'); }
    return [$v->passes(), $v];
}
/** Load a manageable (non-admin) account or stop. */
$target = function () use ($fail): array {
    $t = Storage::find('users', (int)($_POST['user_id'] ?? 0));
    if (!$t) { $fail('Account not found.'); }
    if ($t['role'] === ROLE_ADMIN) { $fail('Administrator accounts cannot be changed here.'); }
    return $t;
};

switch ($action) {
    case 'create_staff':
        [$ok, $v] = validate_profile($_POST);
        if ($ok) { $v->password('password'); $ok = $v->passes(); }
        if (!$ok) { $_SESSION['old'] = ['name' => $v->get('name'), 'email' => $v->get('email'), 'phone' => $v->get('phone')]; $fail(implode(' ', $v->errors())); }
        $new = Storage::insert('users', [
            'name' => $v->get('name'), 'email' => strtolower($v->get('email')),
            'password' => password_hash((string)$_POST['password'], PASSWORD_DEFAULT),
            'role' => ROLE_STAFF, 'phone' => $v->get('phone'), 'active' => true, 'created_at' => now(),
        ]);
        log_activity("Created staff account {$new['email']}", (int)$new['id']);
        flash('success', "Staff account created for {$new['name']}.");
        break;

    case 'update':
        $t = $target();
        [$ok, $v] = validate_profile($_POST, (int)$t['id']);
        if (!$ok) { $fail(implode(' ', $v->errors())); }
        Storage::update('users', (int)$t['id'], ['name' => $v->get('name'), 'email' => strtolower($v->get('email')), 'phone' => $v->get('phone')]);
        log_activity("Updated account {$t['email']}", (int)$t['id']);
        flash('success', 'Account updated.');
        break;

    case 'toggle':
        $t = $target();
        if ((int)$t['id'] === (int)$u['id']) { $fail('You cannot change your own account status.'); }
        if ($t['active'] && $t['role'] === ROLE_STAFF) {
            $open = Complaint::staffWorkload()[(int)$t['id']] ?? 0;
            if ($open > 0) { $fail("{$t['name']} still has $open open complaint(s). Reassign them before deactivating this account."); }
        }
        Storage::update('users', (int)$t['id'], ['active' => !$t['active']]);
        log_activity(($t['active'] ? 'Deactivated' : 'Activated') . " account {$t['email']}", (int)$t['id']);
        flash('success', $t['name'] . ($t['active'] ? ' was deactivated and can no longer sign in.' : ' was activated.'));
        break;

    case 'reset_password':
        $t = $target();
        $v = new Validator($_POST);
        $v->password('password');
        if (!$v->passes()) { $fail(implode(' ', $v->errors())); }
        Storage::update('users', (int)$t['id'], ['password' => password_hash((string)$_POST['password'], PASSWORD_DEFAULT)]);
        log_activity("Reset password for {$t['email']}", (int)$t['id']);
        flash('success', "Password reset for {$t['name']}. Share the new password with them securely.");
        break;

    default:
        http_response_code(400); exit('Unknown action.');
}
unset($_SESSION['old']);
redirect($back);
