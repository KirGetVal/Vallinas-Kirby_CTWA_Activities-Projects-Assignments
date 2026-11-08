<?php
require_once __DIR__ . '/../core/bootstrap.php';
$u = Auth::require();
require_post();
$c = Complaint::findFor((int)($_POST['complaint_id'] ?? 0), $u);
if (!$c) { http_response_code(404); exit('Complaint not found.'); }

$v = new Validator($_POST);
$v->required('body', 'Message')->length('body', 'Message', 1, 2000);
$back = match ($u['role']) {
    ROLE_USER => 'user/complaint_details.php?id=' . $c['id'] . '#messages',
    ROLE_STAFF => 'staff/complaint_details.php?id=' . $c['id'] . '#messages',
    default => 'admin/complaint_details.php?id=' . $c['id'] . '#messages',
};
if ($c['status'] === STATUS_REJECTED && $u['role'] !== ROLE_ADMIN) { $v->fail('body', 'Messaging is closed for rejected complaints.'); }
if (!$v->passes()) { flash('error', implode(' ', $v->errors())); redirect($back); }

Storage::insert('messages', [
    'complaint_id' => (int)$c['id'], 'sender_id' => (int)$u['id'], 'sender_role' => $u['role'],
    'body' => $v->get('body'), 'read_by' => [(int)$u['id']], 'created_at' => now(),
]);

// Notify the other participants
$targets = [];
if ($u['role'] === ROLE_USER) {
    if ($c['assigned_to']) { $targets[] = (int)$c['assigned_to']; }
    else { foreach (Storage::where('users', fn($x) => $x['role'] === ROLE_ADMIN && $x['active']) as $a) { $targets[] = (int)$a['id']; } }
} else {
    $targets[] = (int)$c['user_id'];
    if ($u['role'] === ROLE_ADMIN && $c['assigned_to']) { $targets[] = (int)$c['assigned_to']; }
}
foreach (array_unique($targets) as $t) {
    if ($t !== (int)$u['id']) {
        Notification::send($t, "New message on {$c['reference']} from {$u['name']}.", $t === (int)$c['user_id'] ? 'user/complaint_details.php?id=' . $c['id'] . '#messages' : (user_role_of($t) === ROLE_ADMIN ? 'admin/complaint_details.php?id=' : 'staff/complaint_details.php?id=') . $c['id'], (int)$c['id']);
    }
}

// A complainant reply to an information request resumes the investigation.
if ($u['role'] === ROLE_USER && $c['status'] === STATUS_AWAITING) {
    Complaint::changeStatus((int)$c['id'], STATUS_PROGRESS, $u, 'Complainant provided the requested information');
}
flash('success', 'Message sent.');
redirect($back);
