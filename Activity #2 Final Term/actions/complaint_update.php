<?php
require_once __DIR__ . '/../core/bootstrap.php';
$u = Auth::require();
require_post();

$id = (int)($_POST['complaint_id'] ?? 0);
$c = Complaint::findFor($id, $u);
if (!$c) { http_response_code(404); exit('Complaint not found.'); }
$action = (string)($_POST['action'] ?? '');
$back = match ($u['role']) {
    ROLE_USER => 'user/complaint_details.php?id=' . $id,
    ROLE_STAFF => 'staff/complaint_details.php?id=' . $id,
    default => 'admin/complaint_details.php?id=' . $id,
};
$fail = function (string $msg) use ($back): never { flash('error', $msg); redirect($back); };
$text = fn(string $k, int $min, int $max, string $label) => (function () use ($k, $min, $max, $label, $fail) {
    $t = Validator::clean($_POST[$k] ?? '');
    $len = str_len($t);
    if ($len < $min || $len > $max) { $fail("$label must be between $min and $max characters."); }
    return $t;
})();

switch ($action) {
    case 'reopen':                                   // complainant (or admin) reopens a closed complaint
        $reason = $text('reason', 1, 500, 'The reason');
        [$ok, $msg] = Complaint::changeStatus($id, STATUS_REOPENED, $u, 'Reopened: ' . $reason);
        if ($ok) { Notification::toAdmins("{$c['reference']} was reopened by {$u['name']}.", $id); }
        flash($ok ? 'success' : 'error', $ok ? 'Complaint reopened. An administrator will review it.' : $msg);
        break;

    case 'note':                                     // staff/admin investigation note
        if ($u['role'] === ROLE_USER) { http_response_code(403); exit('403 Forbidden'); }
        if (!is_open($c) && $c['status'] !== STATUS_RESOLVED) { $fail('Notes can only be added to active complaints.'); }
        $body = $text('body', 3, 2000, 'The note');
        $public = ($_POST['visibility'] ?? '') === 'public';
        Storage::insert('notes', [
            'complaint_id' => $id, 'author_id' => (int)$u['id'], 'body' => $body,
            'visibility' => $public ? 'public' : 'internal', 'created_at' => now(),
        ]);
        log_activity("Added " . ($public ? 'public' : 'internal') . " note to {$c['reference']}", $id);
        if ($public) { Notification::send((int)$c['user_id'], "There is a new update on {$c['reference']}.", 'user/complaint_details.php?id=' . $id, $id); }
        flash('success', $public ? 'Update posted and visible to the complainant.' : 'Internal note saved.');
        break;

    case 'progress':                                 // start / resume investigation
    case 'request_info':
    case 'resolve':
        if ($u['role'] === ROLE_USER) { http_response_code(403); exit('403 Forbidden'); }
        if ($action === 'progress') {
            [$ok, $msg] = Complaint::changeStatus($id, STATUS_PROGRESS, $u, Validator::clean($_POST['remarks'] ?? '') ?: 'Investigation started');
        } elseif ($action === 'request_info') {
            $q = $text('message', 5, 2000, 'The request');
            [$ok, $msg] = Complaint::changeStatus($id, STATUS_AWAITING, $u, 'Additional information requested');
            if ($ok) {   // the question is delivered in the message thread so the complainant can answer in place
                Storage::insert('messages', ['complaint_id' => $id, 'sender_id' => (int)$u['id'], 'sender_role' => $u['role'],
                    'body' => $q, 'read_by' => [(int)$u['id']], 'created_at' => now()]);
            }
        } else {
            $res = $text('resolution', 10, 3000, 'The resolution details');
            [$ok, $msg] = Complaint::changeStatus($id, STATUS_RESOLVED, $u, 'Resolution submitted', ['resolution' => $res, 'resolved_at' => now()]);
        }
        flash($ok ? 'success' : 'error', $msg);
        break;

    default:
        http_response_code(400); exit('Unknown action.');
}
redirect($back);
