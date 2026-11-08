<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
$u = Auth::requireRole(ROLE_USER);
$mine = Complaint::visibleTo($u);
$n = count_by_status($mine);
layout_start('Dashboard', $u); ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div><h1 class="h4 mb-0">Welcome, <?= e($u['name']) ?></h1><p class="text-muted mb-0">Here is the status of your complaints.</p></div>
  <a href="<?= e(base_url('user/submit_complaint.php')) ?>" class="btn btn-accent btn-lg"><i class="bi bi-plus-circle me-1"></i>Submit New Complaint</a>
</div>
<div class="row g-3 mb-4">
  <?php stat_card('Total Complaints', count($mine), 'collection');
        stat_card('Submitted', $n[STATUS_SUBMITTED], 'send', 'secondary');
        stat_card('Under Review', $n[STATUS_REVIEW], 'search', 'info');
        stat_card('In Progress', $n[STATUS_PROGRESS] + $n[STATUS_ASSIGNED] + $n[STATUS_AWAITING], 'hourglass-split', 'warning');
        stat_card('Resolved', $n[STATUS_RESOLVED], 'check-circle', 'success');
        stat_card('Closed', $n[STATUS_CLOSED], 'lock', 'dark'); ?>
</div>
<div class="row g-4">
  <div class="col-xl-8"><div class="card"><div class="card-header bg-white fw-semibold">Recent Complaints</div>
    <div class="card-body p-0"><?php render_complaint_table(array_slice($mine, 0, 5), $u); ?></div></div></div>
  <div class="col-xl-4"><div class="card"><div class="card-header bg-white fw-semibold">Recent Notifications</div>
    <ul class="list-group list-group-flush">
    <?php $notes = array_slice(Notification::forUser((int)$u['id']), 0, 5);
    foreach ($notes as $x): ?>
      <li class="list-group-item <?= $x['read'] ? '' : 'notif-unread' ?>"><div class="small"><?= e($x['message']) ?></div>
        <div class="text-muted" style="font-size:.75rem"><?= e(fmt_date($x['created_at'])) ?></div></li>
    <?php endforeach; if (!$notes): ?><li class="list-group-item text-muted text-center py-4">No notifications yet.</li><?php endif; ?>
    </ul></div></div>
</div>
<?php layout_end();
