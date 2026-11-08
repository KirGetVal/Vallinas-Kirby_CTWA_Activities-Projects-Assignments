<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
$u = Auth::requireRole(ROLE_ADMIN);
$c = Complaint::findFor((int)($_GET['id'] ?? 0), $u);
if (!$c) { http_response_code(404); exit('Complaint not found.'); }
$id = (int)$c['id'];
mark_messages_read($id, (int)$u['id']);
$owner = Storage::find('users', (int)$c['user_id']);
$history = Storage::where('status_history', fn($h) => (int)$h['complaint_id'] === $id);
$files = Storage::where('attachments', fn($a) => (int)$a['complaint_id'] === $id);
$msgs = Storage::where('messages', fn($m) => (int)$m['complaint_id'] === $id);
$notes = array_reverse(Storage::where('notes', fn($n) => (int)$n['complaint_id'] === $id));
$fb = Storage::where('feedback', fn($f) => (int)$f['complaint_id'] === $id)[0] ?? null;
$next = Complaint::allowedNext($c, $u);
$staff = Storage::where('users', fn($x) => $x['role'] === ROLE_STAFF && $x['active']);
$work = Complaint::staffWorkload();
$act = base_url('actions/admin_complaint.php');
$canAssign = in_array($c['status'], [STATUS_SUBMITTED, STATUS_REVIEW, STATUS_REOPENED, STATUS_ASSIGNED, STATUS_PROGRESS, STATUS_AWAITING], true);
$isReassign = in_array($c['status'], [STATUS_ASSIGNED, STATUS_PROGRESS, STATUS_AWAITING], true);
$hidden = fn(string $a) => csrf_field() . '<input type="hidden" name="complaint_id" value="' . $id . '"><input type="hidden" name="action" value="' . $a . '">';
layout_start($c['reference'], $u); ?>
<div class="d-flex justify-content-between no-print">
  <a href="<?= e(base_url('admin/complaints.php')) ?>" class="small"><i class="bi bi-arrow-left"></i> Back to all complaints</a>
  <button class="btn btn-sm btn-outline-secondary" type="button" onclick="window.print()" data-no-loading="1"><i class="bi bi-printer me-1"></i>Print</button></div>
<div class="d-flex flex-wrap justify-content-between align-items-start my-3 gap-2">
  <div><h1 class="h4 mb-1"><?= e($c['title']) ?></h1><span class="text-muted"><?= e($c['reference']) ?></span></div>
  <div><?= status_badge($c['status']) ?> <?= priority_badge($c['priority']) ?><?php if (is_overdue($c)): ?> <span class="badge text-bg-danger">Overdue</span><?php endif; ?></div>
</div>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="card mb-4"><div class="card-body"><dl class="row mb-0">
      <dt class="col-sm-3">Complainant</dt><dd class="col-sm-9"><?= e($owner['name'] ?? '-') ?> <span class="text-muted small"><?= e($owner['email'] ?? '') ?></span></dd>
      <dt class="col-sm-3">Category</dt><dd class="col-sm-9"><?= e(category_name((int)$c['category_id'])) ?></dd>
      <dt class="col-sm-3">Assigned to</dt><dd class="col-sm-9"><?= $c['assigned_to'] ? e(user_name((int)$c['assigned_to'])) : '<span class="text-muted">Unassigned</span>' ?></dd>
      <dt class="col-sm-3">Submitted</dt><dd class="col-sm-9"><?= e(fmt_date($c['created_at'])) ?> <span class="text-muted small">(due <?= e(date('M j, Y', due_date($c))) ?>)</span></dd>
      <dt class="col-sm-3">Incident</dt><dd class="col-sm-9"><?= e($c['location']) ?> on <?= e(fmt_date($c['incident_date'], false)) ?></dd>
      <dt class="col-sm-3">Description</dt><dd class="col-sm-9"><?= nl2br(e($c['description'])) ?></dd>
      <?php if ($c['additional_info']): ?><dt class="col-sm-3">Additional info</dt><dd class="col-sm-9"><?= nl2br(e($c['additional_info'])) ?></dd><?php endif; ?>
      <dt class="col-sm-3">Evidence</dt><dd class="col-sm-9">
        <?php foreach ($files as $f): ?><div><i class="bi bi-paperclip"></i> <a href="<?= e(base_url('download.php?id=' . (int)$f['id'])) ?>" target="_blank"><?= e($f['original_name']) ?></a>
          <span class="text-muted small">(<?= e(number_format($f['size'] / 1024, 1)) ?> KB)</span></div><?php endforeach;
        if (!$files) echo '<span class="text-muted">None</span>'; ?></dd>
      <?php if ($c['rejection_reason']): ?><dt class="col-sm-3">Rejection reason</dt><dd class="col-sm-9 text-danger"><?= nl2br(e($c['rejection_reason'])) ?></dd><?php endif; ?>
      <?php if ($c['resolution']): ?><dt class="col-sm-3">Resolution</dt><dd class="col-sm-9"><?= nl2br(e($c['resolution'])) ?></dd><?php endif; ?>
      <?php if ($fb): ?><dt class="col-sm-3">Feedback</dt><dd class="col-sm-9"><span class="text-warning"><?= str_repeat('★', (int)$fb['rating']) . str_repeat('☆', 5 - (int)$fb['rating']) ?></span>
        <?= $fb['comment'] ? '— ' . e($fb['comment']) : '' ?></dd><?php endif; ?>
    </dl></div></div>

    <div class="card mb-4" id="messages"><div class="card-header bg-white fw-semibold">Conversation with complainant</div><div class="card-body">
      <?php foreach ($msgs as $m): $mine = (int)$m['sender_id'] === (int)$u['id']; ?>
        <div class="mb-3 d-flex"><div class="msg <?= $mine ? 'mine' : '' ?>">
          <div class="small fw-semibold"><?= e(user_name((int)$m['sender_id'])) ?> <span class="text-muted fw-normal text-capitalize">(<?= e($m['sender_role']) ?>)</span></div>
          <?= nl2br(e($m['body'])) ?><div class="text-muted" style="font-size:.72rem"><?= e(fmt_date($m['created_at'])) ?></div></div></div>
      <?php endforeach; if (!$msgs) echo '<p class="text-muted text-center mb-3">No messages yet.</p>'; ?>
      <form method="post" action="<?= e(base_url('actions/message_process.php')) ?>" class="no-print"><?= csrf_field() ?>
        <input type="hidden" name="complaint_id" value="<?= $id ?>">
        <div class="input-group"><textarea class="form-control" name="body" rows="2" maxlength="2000" required placeholder="Write a message to the complainant..."></textarea>
          <button class="btn btn-primary" type="submit" aria-label="Send"><i class="bi bi-send"></i></button></div></form>
    </div></div>

    <div class="card mb-4"><div class="card-header bg-white fw-semibold">Investigation notes <span class="text-muted fw-normal small">(you can see internal notes; complainants cannot)</span></div><div class="card-body">
      <form method="post" action="<?= e(base_url('actions/complaint_update.php')) ?>" class="mb-3 no-print"><?= $hidden('note') ?>
        <textarea class="form-control mb-2" name="body" rows="2" required minlength="3" maxlength="2000" placeholder="Add an administrative note..."></textarea>
        <div class="d-flex justify-content-between align-items-center">
          <div class="form-check"><input class="form-check-input" type="checkbox" name="visibility" value="public" id="pub"><label class="form-check-label small" for="pub">Visible to complainant</label></div>
          <button class="btn btn-primary btn-sm" type="submit">Add note</button></div></form>
      <?php foreach ($notes as $n): ?>
        <div class="border-top pt-2 mt-2"><?= nl2br(e($n['body'])) ?>
          <div class="small text-muted"><?= e(user_name((int)$n['author_id'])) ?> · <?= e(fmt_date($n['created_at'])) ?>
            · <span class="badge text-bg-<?= $n['visibility'] === 'public' ? 'info' : 'secondary' ?>"><?= $n['visibility'] === 'public' ? 'Visible to complainant' : 'Internal' ?></span></div></div>
      <?php endforeach; if (!$notes) echo '<p class="text-muted text-center mb-0">No notes yet.</p>'; ?>
    </div></div>
  </div>

  <div class="col-lg-4">
    <div class="card mb-4 border-primary no-print"><div class="card-header bg-white fw-semibold">Manage complaint</div><div class="card-body">
      <form method="post" action="<?= e($act) ?>" class="mb-3"><?= $hidden('priority') ?>
        <label class="form-label small fw-semibold" for="prio">Priority</label>
        <div class="input-group input-group-sm"><select class="form-select" id="prio" name="priority">
          <?php foreach (PRIORITIES as $k => $l): ?><option value="<?= e($k) ?>" <?= $c['priority'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
          <button class="btn btn-outline-primary">Set</button></div></form>

      <?php if (in_array(STATUS_REVIEW, $next, true)): ?>
      <form method="post" action="<?= e($act) ?>" class="mb-3"><?= $hidden('review') ?>
        <button class="btn btn-sm btn-info w-100"><i class="bi bi-search me-1"></i>Start review</button></form>
      <?php endif; ?>

      <?php if ($canAssign): ?>
      <form method="post" action="<?= e(base_url('actions/assignment_process.php')) ?>" class="mb-3"><?= $hidden('assign') ?>
        <label class="form-label small fw-semibold" for="staff_id"><?= $isReassign ? 'Reassign to' : 'Assign to staff' ?></label>
        <select class="form-select form-select-sm mb-2" id="staff_id" name="staff_id" required><option value="">Select staff…</option>
          <?php foreach ($staff as $s): ?><option value="<?= (int)$s['id'] ?>" <?= (int)$c['assigned_to'] === (int)$s['id'] ? 'disabled' : '' ?>><?= e($s['name']) ?> (<?= (int)($work[(int)$s['id']] ?? 0) ?> open)</option><?php endforeach; ?></select>
        <input class="form-control form-control-sm mb-2" name="remarks" maxlength="300" placeholder="Remarks (optional)">
        <button class="btn btn-sm btn-primary w-100" <?= $staff ? '' : 'disabled' ?>><i class="bi bi-person-plus me-1"></i><?= $isReassign ? 'Reassign' : 'Assign' ?></button></form>
      <?php endif; ?>

      <?php if ($c['status'] === STATUS_RESOLVED): ?>
      <form method="post" action="<?= e($act) ?>" class="mb-3" data-confirm="Approve this resolution and close the complaint?"><?= $hidden('approve') ?>
        <input class="form-control form-control-sm mb-2" name="reason" maxlength="300" placeholder="Approval remarks (optional)">
        <button class="btn btn-sm btn-success w-100"><i class="bi bi-check2-circle me-1"></i>Approve &amp; close</button></form>
      <form method="post" action="<?= e($act) ?>" class="mb-3"><?= $hidden('send_back') ?>
        <textarea class="form-control form-control-sm mb-2" name="reason" rows="2" required minlength="5" maxlength="1000" placeholder="What needs more work?"></textarea>
        <button class="btn btn-sm btn-outline-warning w-100"><i class="bi bi-arrow-counterclockwise me-1"></i>Send back to staff</button></form>
      <?php endif; ?>

      <?php if (in_array(STATUS_REJECTED, $next, true)): ?>
      <form method="post" action="<?= e($act) ?>" data-confirm="Reject this complaint? The complainant will see your reason."><?= $hidden('reject') ?>
        <label class="form-label small fw-semibold text-danger" for="why">Reject complaint</label>
        <textarea class="form-control form-control-sm mb-2" id="why" name="reason" rows="2" required minlength="5" maxlength="1000" placeholder="Reason shown to the complainant"></textarea>
        <button class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-x-circle me-1"></i>Reject</button></form>
      <?php endif; ?>

      <?php if (in_array(STATUS_REOPENED, $next, true)): ?>
      <form method="post" action="<?= e(base_url('actions/complaint_update.php')) ?>" data-confirm="Reopen this complaint?"><?= $hidden('reopen') ?>
        <label class="form-label small fw-semibold" for="rr">Reopen complaint</label>
        <input class="form-control form-control-sm mb-2" id="rr" name="reason" required maxlength="500" placeholder="Reason for reopening">
        <button class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-arrow-repeat me-1"></i>Reopen</button></form>
      <?php endif; ?>
    </div></div>

    <div class="card"><div class="card-header bg-white fw-semibold">Status timeline</div><div class="card-body">
      <ul class="timeline"><?php foreach (array_reverse($history) as $h): ?>
        <li><div><?= status_badge($h['new_status']) ?></div>
          <div class="small text-muted"><?= e(fmt_date($h['created_at'])) ?><?= $h['updated_by'] ? ' · ' . e(user_name((int)$h['updated_by'])) : '' ?></div>
          <?php if ($h['remarks']): ?><div class="small"><?= e($h['remarks']) ?></div><?php endif; ?></li>
      <?php endforeach; ?></ul></div></div>
  </div>
</div>
<?php layout_end();
