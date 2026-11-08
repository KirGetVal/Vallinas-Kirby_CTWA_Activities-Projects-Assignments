<?php
// Administrator-only workflow actions: start review, reject, approve/send back a resolution, set priority.
require_once __DIR__ . '/../core/bootstrap.php';
$u = Auth::requireRole(ROLE_ADMIN);
require_post();

$id = (int)($_POST['complaint_id'] ?? 0);
$c = Storage::find('complaints', $id);
if (!$c) { http_response_code(404); exit('Complaint not found.'); }
$back = 'admin/complaint_details.php?id=' . $id;
$reason = function (int $min, int $max, string $label) use ($back): string {
    $t = Validator::clean($_POST['reason'] ?? '');
    $n = str_len($t);
    if ($n < $min || $n > $max) { flash('error', "$label must be between $min and $max characters."); redirect($back); }
    return $t;
};

switch ((string)($_POST['action'] ?? '')) {
    case 'review':
        [$ok, $msg] = Complaint::changeStatus($id, STATUS_REVIEW, $u, 'Review started');
        break;

    case 'reject':
        $why = $reason(5, 1000, 'The rejection reason');
        [$ok, $msg] = Complaint::changeStatus($id, STATUS_REJECTED, $u, 'Rejected: ' . $why, ['rejection_reason' => $why]);
        if ($ok) {
            log_activity("Rejected {$c['reference']}: " . str_cut($why, 80), $id);
        }
        break;

    case 'approve':      // resolved -> closed
        $note = Validator::clean($_POST['reason'] ?? '');
        [$ok, $msg] = Complaint::changeStatus($id, STATUS_CLOSED, $u, $note !== '' ? 'Resolution approved: ' . $note : 'Resolution approved', ['closed_at' => now()]);
        if ($ok) {
            log_activity("Approved resolution of {$c['reference']}", $id);
            if ($c['assigned_to']) { Notification::send((int)$c['assigned_to'], "Your resolution of {$c['reference']} was approved and the complaint is closed.", 'staff/complaint_details.php?id=' . $id, $id); }
            $msg = 'Resolution approved. The complaint is closed and the complainant can leave feedback.';
        }
        break;

    case 'send_back':    // resolved -> in progress
        $why = $reason(5, 1000, 'The feedback for staff');
        [$ok, $msg] = Complaint::changeStatus($id, STATUS_PROGRESS, $u, 'Resolution sent back: ' . $why, ['resolution' => null]);
        if ($ok) {
            log_activity("Sent back resolution of {$c['reference']}", $id);
            if ($c['assigned_to']) { Notification::send((int)$c['assigned_to'], "Resolution of {$c['reference']} was sent back: " . str_cut($why, 120), 'staff/complaint_details.php?id=' . $id, $id); }
            $msg = 'Resolution sent back to staff for more work.';
        }
        break;

    case 'priority':
        $p = (string)($_POST['priority'] ?? '');
        if (!isset(PRIORITIES[$p])) { flash('error', 'Select a valid priority.'); redirect($back); }
        if ($p === $c['priority']) { flash('info', 'Priority is already ' . PRIORITIES[$p] . '.'); redirect($back); }
        Storage::update('complaints', $id, ['priority' => $p, 'updated_at' => now()]);
        log_activity("Priority of {$c['reference']}: " . PRIORITIES[$c['priority']] . ' -> ' . PRIORITIES[$p], $id);
        [$ok, $msg] = [true, 'Priority set to ' . PRIORITIES[$p] . '.'];
        break;

    default:
        http_response_code(400); exit('Unknown action.');
}
flash($ok ? 'success' : 'error', $msg);
redirect($back);
