<?php
// Shared messages inbox. Expects $u.
$unread = unread_messages_by_complaint($u);
$allMsgs = Storage::read('messages');
$rows = [];
foreach (Complaint::visibleTo($u) as $c) {
    $thread = array_values(array_filter($allMsgs, fn($m) => (int)$m['complaint_id'] === (int)$c['id']));
    if ($thread || $c['status'] === STATUS_AWAITING) { $c['_last'] = end($thread) ?: null; $rows[] = $c; }
}
layout_start('Messages', $u); ?>
<h1 class="h4 mb-3">Messages</h1>
<div class="card"><ul class="list-group list-group-flush">
<?php foreach ($rows as $c): $n = $unread[$c['id']] ?? 0; ?>
  <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="<?= e(complaint_link($c, $u)) ?>#messages">
    <div><div class="fw-semibold"><?= e($c['reference']) ?> — <?= e($c['title']) ?></div>
      <div class="small text-muted"><?= $c['_last'] ? e(str_cut($c['_last']['body'], 80)) : 'Reply requested' ?></div></div>
    <?= $n ? '<span class="badge bg-danger rounded-pill">' . $n . ' new</span>' : '' ?></a>
<?php endforeach; if (!$rows): ?><li class="list-group-item text-center text-muted py-5"><i class="bi bi-chat-dots fs-1 d-block mb-2"></i>No conversations yet. Open a complaint to send a message.</li><?php endif; ?>
</ul></div>
<?php layout_end();
