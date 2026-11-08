<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
$u = Auth::requireRole(ROLE_USER);
$c = Complaint::findFor((int)($_GET['id'] ?? 0), $u);
if (!$c) { http_response_code(404); exit('Complaint not found.'); }   // same answer for "missing" and "not yours"
$id = (int)$c['id'];
mark_messages_read($id, (int)$u['id']);
$history = Storage::where('status_history', fn($h) => (int)$h['complaint_id'] === $id);
$files = Storage::where('attachments', fn($a) => (int)$a['complaint_id'] === $id);
$msgs = Storage::where('messages', fn($m) => (int)$m['complaint_id'] === $id);
$updates = Storage::where('notes', fn($n) => (int)$n['complaint_id'] === $id && $n['visibility'] === 'public');
$feedback = Storage::where('feedback', fn($f) => (int)$f['complaint_id'] === $id)[0] ?? null;
$closed = $c['status'] === STATUS_CLOSED;
layout_start($c['reference'], $u); ?>
<a href="<?= e(base_url('user/my_complaints.php')) ?>" class="small"><i class="bi bi-arrow-left"></i> Back to my complaints</a>
<div class="d-flex flex-wrap justify-content-between align-items-start my-3 gap-2">
  <div><h1 class="h4 mb-1"><?= e($c['title']) ?></h1><span class="text-muted"><?= e($c['reference']) ?></span></div>
  <div><?= status_badge($c['status']) ?> <?= priority_badge($c['priority']) ?></div>
</div>
<?php if ($c['status'] === STATUS_AWAITING): ?>
  <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i><strong>Action needed:</strong> the handler requested more information. Reply in the Messages section below.</div>
<?php endif; ?>
<?php if ($c['status'] === STATUS_REJECTED): ?>
  <div class="alert alert-danger"><strong>Rejected:</strong> <?= e($c['rejection_reason'] ?: 'No reason provided.') ?></div>
<?php endif; ?>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="card mb-4"><div class="card-body">
      <dl class="row mb-0">
        <dt class="col-sm-3">Category</dt><dd class="col-sm-9"><?= e(category_name((int)$c['category_id'])) ?></dd>
        <dt class="col-sm-3">Submitted</dt><dd class="col-sm-9"><?= e(fmt_date($c['created_at'])) ?></dd>
        <dt class="col-sm-3">Incident</dt><dd class="col-sm-9"><?= e($c['location']) ?> on <?= e(fmt_date($c['incident_date'], false)) ?></dd>
        <dt class="col-sm-3">Assigned to</dt><dd class="col-sm-9"><?= $c['assigned_to'] ? e(user_name((int)$c['assigned_to'])) : '<span class="text-muted">Not yet assigned</span>' ?></dd>
        <dt class="col-sm-3">Description</dt><dd class="col-sm-9"><?= nl2br(e($c['description'])) ?></dd>
        <?php if ($c['additional_info']): ?><dt class="col-sm-3">Additional info</dt><dd class="col-sm-9"><?= nl2br(e($c['additional_info'])) ?></dd><?php endif; ?>
        <dt class="col-sm-3">Attachments</dt><dd class="col-sm-9">
          <?php foreach ($files as $f): ?><div><i class="bi bi-paperclip"></i>
            <a href="<?= e(base_url('download.php?id=' . (int)$f['id'])) ?>"><?= e($f['original_name']) ?></a>
            <span class="text-muted small">(<?= e(number_format($f['size'] / 1024, 1)) ?> KB)</span></div><?php endforeach;
          if (!$files) echo '<span class="text-muted">None</span>'; ?></dd>
      </dl></div></div>

    <?php if ($c['resolution'] && in_array($c['status'], [STATUS_RESOLVED, STATUS_CLOSED], true)): ?>
    <div class="card mb-4 border-success border-start border-4"><div class="card-body"><h2 class="h6 text-success">Resolution</h2><?= nl2br(e($c['resolution'])) ?></div></div>
    <?php endif; ?>

    <?php if ($updates): ?><div class="card mb-4"><div class="card-header bg-white fw-semibold">Updates from the handler</div><ul class="list-group list-group-flush">
      <?php foreach (array_reverse($updates) as $n): ?><li class="list-group-item"><?= nl2br(e($n['body'])) ?><div class="text-muted small"><?= e(user_name((int)$n['author_id'])) ?> · <?= e(fmt_date($n['created_at'])) ?></div></li><?php endforeach; ?></ul></div><?php endif; ?>
    <div class="card mb-4" id="messages"><div class="card-header bg-white fw-semibold">Messages</div><div class="card-body">
      <?php foreach ($msgs as $m): $mine = (int)$m['sender_id'] === (int)$u['id']; ?>
        <div class="mb-3 d-flex"><div class="msg <?= $mine ? 'mine' : '' ?>">
          <div class="small fw-semibold"><?= e(user_name((int)$m['sender_id'])) ?> <span class="text-muted fw-normal text-capitalize">(<?= e($m['sender_role']) ?>)</span></div>
          <?= nl2br(e($m['body'])) ?><div class="text-muted" style="font-size:.72rem"><?= e(fmt_date($m['created_at'])) ?></div></div></div>
      <?php endforeach; if (!$msgs) echo '<p class="text-muted text-center mb-3">No messages yet.</p>'; ?>
      <?php if (!in_array($c['status'], [STATUS_REJECTED], true)): ?>
      <form method="post" action="<?= e(base_url('actions/message_process.php')) ?>"><?= csrf_field() ?>
        <input type="hidden" name="complaint_id" value="<?= $id ?>">
        <div class="input-group"><textarea class="form-control" name="body" rows="2" maxlength="2000" required placeholder="Write a message..."></textarea>
          <button class="btn btn-primary" type="submit"><i class="bi bi-send"></i></button></div></form>
      <?php endif; ?></div></div>

    <?php if ($closed && !$feedback): ?>
    <div class="card mb-4"><div class="card-header bg-white fw-semibold">How did we do?</div><div class="card-body">
      <form method="post" action="<?= e(base_url('actions/feedback_process.php')) ?>"><?= csrf_field() ?>
        <input type="hidden" name="complaint_id" value="<?= $id ?>">
        <div class="mb-3"><label class="form-label">Satisfaction rating</label>
          <select class="form-select w-auto" name="rating" required><option value="">Select</option>
            <?php foreach ([5 => '5 - Very satisfied', 4 => '4 - Satisfied', 3 => '3 - Neutral', 2 => '2 - Unsatisfied', 1 => '1 - Very unsatisfied'] as $k => $l): ?>
            <option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><textarea class="form-control" name="comment" rows="3" maxlength="1000" placeholder="Comments (optional)"></textarea></div>
        <button class="btn btn-accent" type="submit">Submit feedback</button></form></div></div>
    <?php elseif ($feedback): ?>
    <div class="alert alert-success">Thanks for your feedback: <?= str_repeat('★', (int)$feedback['rating']) . str_repeat('☆', 5 - (int)$feedback['rating']) ?>
      <?= $feedback['comment'] ? '— ' . e($feedback['comment']) : '' ?></div>
    <?php endif; ?>

    <?php if ($closed): ?>
    <form method="post" action="<?= e(base_url('actions/complaint_update.php')) ?>" data-confirm="Reopen this complaint?"><?= csrf_field() ?>
      <input type="hidden" name="complaint_id" value="<?= $id ?>"><input type="hidden" name="action" value="reopen">
      <div class="input-group"><input class="form-control" name="reason" required maxlength="500" placeholder="Why should this be reopened?">
        <button class="btn btn-outline-danger" type="submit">Reopen complaint</button></div></form>
    <?php endif; ?>
  </div>
  <div class="col-lg-4"><div class="card"><div class="card-header bg-white fw-semibold">Status timeline</div><div class="card-body">
    <ul class="timeline"><?php foreach (array_reverse($history) as $h): ?>
      <li><div><?= status_badge($h['new_status']) ?></div><div class="small text-muted"><?= e(fmt_date($h['created_at'])) ?></div>
        <?php if ($h['remarks']): ?><div class="small"><?= e($h['remarks']) ?></div><?php endif; ?></li>
    <?php endforeach; ?></ul></div></div></div>
</div>
<?php layout_end();
