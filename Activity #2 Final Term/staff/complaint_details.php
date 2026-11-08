<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
$u = Auth::requireRole(ROLE_STAFF);
$c = Complaint::findFor((int)($_GET['id'] ?? 0), $u);          // only complaints assigned to this staff member
if (!$c) { http_response_code(404); exit('Complaint not found.'); }
$id = (int)$c['id'];
mark_messages_read($id, (int)$u['id']);
$owner = Storage::find('users', (int)$c['user_id']);
$history = Storage::where('status_history', fn($h) => (int)$h['complaint_id'] === $id);
$files = Storage::where('attachments', fn($a) => (int)$a['complaint_id'] === $id);
$msgs = Storage::where('messages', fn($m) => (int)$m['complaint_id'] === $id);
$notes = array_reverse(Storage::where('notes', fn($n) => (int)$n['complaint_id'] === $id));
$next = Complaint::allowedNext($c, $u);
$action = base_url('actions/complaint_update.php');
layout_start($c['reference'], $u); ?>
<a href="<?= e(base_url('staff/assigned_complaints.php')) ?>" class="small"><i class="bi bi-arrow-left"></i> Back to assigned complaints</a>
<div class="d-flex flex-wrap justify-content-between align-items-start my-3 gap-2">
  <div><h1 class="h4 mb-1"><?= e($c['title']) ?></h1><span class="text-muted"><?= e($c['reference']) ?></span></div>
  <div><?= status_badge($c['status']) ?> <?= priority_badge($c['priority']) ?>
    <?php if (is_overdue($c)): ?><span class="badge text-bg-danger">Overdue</span><?php endif; ?></div>
</div>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="card mb-4"><div class="card-body"><dl class="row mb-0">
      <dt class="col-sm-3">Complainant</dt><dd class="col-sm-9"><?= e($owner['name'] ?? '-') ?> <span class="text-muted small"><?= e($owner['email'] ?? '') ?></span></dd>
      <dt class="col-sm-3">Category</dt><dd class="col-sm-9"><?= e(category_name((int)$c['category_id'])) ?></dd>
      <dt class="col-sm-3">Submitted</dt><dd class="col-sm-9"><?= e(fmt_date($c['created_at'])) ?> <span class="text-muted small">(due <?= e(date('M j, Y', due_date($c))) ?>)</span></dd>
      <dt class="col-sm-3">Incident</dt><dd class="col-sm-9"><?= e($c['location']) ?> on <?= e(fmt_date($c['incident_date'], false)) ?></dd>
      <dt class="col-sm-3">Description</dt><dd class="col-sm-9"><?= nl2br(e($c['description'])) ?></dd>
      <?php if ($c['additional_info']): ?><dt class="col-sm-3">Additional info</dt><dd class="col-sm-9"><?= nl2br(e($c['additional_info'])) ?></dd><?php endif; ?>
      <dt class="col-sm-3">Evidence</dt><dd class="col-sm-9">
        <?php foreach ($files as $f): ?><div><i class="bi bi-paperclip"></i> <a href="<?= e(base_url('download.php?id=' . (int)$f['id'])) ?>" target="_blank"><?= e($f['original_name']) ?></a>
          <span class="text-muted small">(<?= e(number_format($f['size'] / 1024, 1)) ?> KB)</span></div><?php endforeach;
        if (!$files) echo '<span class="text-muted">None</span>'; ?></dd>
      <?php if ($c['resolution']): ?><dt class="col-sm-3">Resolution</dt><dd class="col-sm-9"><?= nl2br(e($c['resolution'])) ?></dd><?php endif; ?>
    </dl></div></div>

    <?php if ($next): ?>
    <div class="card mb-4 border-primary"><div class="card-header bg-white fw-semibold">Update this complaint</div><div class="card-body">
      <?php if (in_array(STATUS_PROGRESS, $next, true)): ?>
      <form method="post" action="<?= e($action) ?>" class="mb-3"><?= csrf_field() ?>
        <input type="hidden" name="complaint_id" value="<?= $id ?>"><input type="hidden" name="action" value="progress">
        <div class="input-group"><input class="form-control" name="remarks" maxlength="300" placeholder="Remarks (optional)">
          <button class="btn btn-primary" type="submit"><i class="bi bi-play-circle me-1"></i><?= $c['status'] === STATUS_AWAITING ? 'Resume investigation' : 'Start investigation' ?></button></div></form>
      <?php endif; ?>
      <?php if (in_array(STATUS_AWAITING, $next, true)): ?>
      <form method="post" action="<?= e($action) ?>" class="mb-3"><?= csrf_field() ?>
        <input type="hidden" name="complaint_id" value="<?= $id ?>"><input type="hidden" name="action" value="request_info">
        <label class="form-label fw-semibold">Request more information</label>
        <textarea class="form-control mb-2" name="message" rows="2" required minlength="5" maxlength="2000" placeholder="What do you need from the complainant?"></textarea>
        <button class="btn btn-outline-warning" type="submit"><i class="bi bi-question-circle me-1"></i>Send request</button></form>
      <?php endif; ?>
      <?php if (in_array(STATUS_RESOLVED, $next, true)): ?>
      <form method="post" action="<?= e($action) ?>" data-confirm="Submit this resolution for administrator approval?"><?= csrf_field() ?>
        <input type="hidden" name="complaint_id" value="<?= $id ?>"><input type="hidden" name="action" value="resolve">
        <label class="form-label fw-semibold">Submit resolution</label>
        <textarea class="form-control mb-2" name="resolution" rows="3" required minlength="10" maxlength="3000" placeholder="Describe how the complaint was resolved"></textarea>
        <button class="btn btn-success" type="submit"><i class="bi bi-check-circle me-1"></i>Mark as resolved</button></form>
      <?php endif; ?>
    </div></div>
    <?php elseif ($c['status'] === STATUS_RESOLVED): ?>
    <div class="alert alert-success">Resolution submitted. Waiting for administrator approval.</div>
    <?php endif; ?>

    <div class="card mb-4"><div class="card-header bg-white fw-semibold">Investigation notes</div><div class="card-body">
      <form method="post" action="<?= e($action) ?>" class="mb-3"><?= csrf_field() ?>
        <input type="hidden" name="complaint_id" value="<?= $id ?>"><input type="hidden" name="action" value="note">
        <textarea class="form-control mb-2" name="body" rows="2" required minlength="3" maxlength="2000" placeholder="Record findings or progress..."></textarea>
        <div class="d-flex justify-content-between align-items-center">
          <div class="form-check"><input class="form-check-input" type="checkbox" name="visibility" value="public" id="pub">
            <label class="form-check-label small" for="pub">Visible to complainant (otherwise internal only)</label></div>
          <button class="btn btn-primary btn-sm" type="submit">Add note</button></div></form>
      <?php foreach ($notes as $n): ?>
        <div class="border-top pt-2 mt-2"><?= nl2br(e($n['body'])) ?>
          <div class="small text-muted"><?= e(user_name((int)$n['author_id'])) ?> · <?= e(fmt_date($n['created_at'])) ?>
            · <span class="badge text-bg-<?= $n['visibility'] === 'public' ? 'info' : 'secondary' ?>"><?= $n['visibility'] === 'public' ? 'Visible to complainant' : 'Internal' ?></span></div></div>
      <?php endforeach; if (!$notes) echo '<p class="text-muted text-center mb-0">No notes yet.</p>'; ?>
    </div></div>

    <div class="card mb-4" id="messages"><div class="card-header bg-white fw-semibold">Messages with complainant</div><div class="card-body">
      <?php foreach ($msgs as $m): $mine = (int)$m['sender_id'] === (int)$u['id']; ?>
        <div class="mb-3 d-flex"><div class="msg <?= $mine ? 'mine' : '' ?>">
          <div class="small fw-semibold"><?= e(user_name((int)$m['sender_id'])) ?> <span class="text-muted fw-normal text-capitalize">(<?= e($m['sender_role']) ?>)</span></div>
          <?= nl2br(e($m['body'])) ?><div class="text-muted" style="font-size:.72rem"><?= e(fmt_date($m['created_at'])) ?></div></div></div>
      <?php endforeach; if (!$msgs) echo '<p class="text-muted text-center mb-3">No messages yet.</p>'; ?>
      <form method="post" action="<?= e(base_url('actions/message_process.php')) ?>"><?= csrf_field() ?>
        <input type="hidden" name="complaint_id" value="<?= $id ?>">
        <div class="input-group"><textarea class="form-control" name="body" rows="2" maxlength="2000" required placeholder="Write a message..."></textarea>
          <button class="btn btn-primary" type="submit"><i class="bi bi-send"></i></button></div></form>
    </div></div>
  </div>
  <div class="col-lg-4"><div class="card"><div class="card-header bg-white fw-semibold">Status timeline</div><div class="card-body">
    <ul class="timeline"><?php foreach (array_reverse($history) as $h): ?>
      <li><div><?= status_badge($h['new_status']) ?></div>
        <div class="small text-muted"><?= e(fmt_date($h['created_at'])) ?><?= $h['updated_by'] ? ' · ' . e(user_name((int)$h['updated_by'])) : '' ?></div>
        <?php if ($h['remarks']): ?><div class="small"><?= e($h['remarks']) ?></div><?php endif; ?></li>
    <?php endforeach; ?></ul></div></div></div>
</div>
<?php layout_end();
