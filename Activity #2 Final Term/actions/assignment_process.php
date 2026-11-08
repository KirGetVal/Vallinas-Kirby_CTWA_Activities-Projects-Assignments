<?php
require_once __DIR__ . '/../core/bootstrap.php';
$u = Auth::requireRole(ROLE_ADMIN);
require_post();

$id = (int)($_POST['complaint_id'] ?? 0);
$c = Storage::find('complaints', $id);
if (!$c) { http_response_code(404); exit('Complaint not found.'); }
$back = ($_POST['back'] ?? '') === 'queue' ? 'admin/assign_complaint.php' : 'admin/complaint_details.php?id=' . $id;

$priority = (string)($_POST['priority'] ?? '');
if ($priority !== '' && !isset(PRIORITIES[$priority])) { flash('error', 'Select a valid priority.'); redirect($back); }
$remarks = Validator::clean($_POST['remarks'] ?? '');
if (str_len($remarks) > 300) { flash('error', 'Remarks must be 300 characters or fewer.'); redirect($back); }

[$ok, $msg] = Complaint::assign($id, (int)($_POST['staff_id'] ?? 0), $u, $remarks);
if ($ok && $priority !== '' && $priority !== $c['priority']) {
    Storage::update('complaints', $id, ['priority' => $priority]);
    log_activity("Priority of {$c['reference']} set to " . PRIORITIES[$priority], $id);
}
flash($ok ? 'success' : 'error', $ok ? "{$c['reference']}: $msg" : $msg);
redirect($back);
