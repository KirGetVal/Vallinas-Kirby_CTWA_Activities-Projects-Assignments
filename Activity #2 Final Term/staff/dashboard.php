<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
$u = Auth::requireRole(ROLE_STAFF);
$mine = Complaint::visibleTo($u);
$n = count_by_status($mine);
$open = array_values(array_filter($mine, 'is_open'));
usort($open, fn($a, $b) => due_date($a) <=> due_date($b));
$overdue = count(array_filter($open, 'is_overdue'));
layout_start('Staff Dashboard', $u); ?>
<h1 class="h4 mb-4">Staff Dashboard</h1>
<div class="row g-3 mb-4">
  <?php stat_card('Total Assigned', count($mine), 'collection');
        stat_card('Pending Review', $n[STATUS_ASSIGNED] + $n[STATUS_REOPENED], 'inbox', 'secondary');
        stat_card('In Progress', $n[STATUS_PROGRESS], 'hourglass-split', 'warning');
        stat_card('Awaiting Response', $n[STATUS_AWAITING], 'clock-history', 'info');
        stat_card('Resolved', $n[STATUS_RESOLVED], 'check-circle', 'success');
        stat_card('Overdue', $overdue, 'exclamation-triangle', 'danger'); ?>
</div>
<div class="row g-4">
  <div class="col-xl-7"><div class="card"><div class="card-header bg-white fw-semibold">Recent Assigned Complaints</div>
    <div class="card-body p-0"><?php render_complaint_table(array_slice($mine, 0, 6), $u, true); ?></div></div></div>
  <div class="col-xl-5"><div class="card"><div class="card-header bg-white fw-semibold">Upcoming &amp; Overdue Cases</div>
    <ul class="list-group list-group-flush">
    <?php foreach (array_slice($open, 0, 6) as $c): $late = is_overdue($c); ?>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <div><a class="fw-semibold" href="<?= e(complaint_link($c, $u)) ?>"><?= e($c['reference']) ?></a><div class="small text-muted"><?= e(str_cut($c['title'], 40)) ?></div></div>
        <span class="badge text-bg-<?= $late ? 'danger' : 'secondary' ?>"><?= $late ? 'Overdue · ' : 'Due ' ?><?= e(date('M j', due_date($c))) ?></span></li>
    <?php endforeach; if (!$open): ?><li class="list-group-item text-center text-muted py-4">No open cases. Nice work!</li><?php endif; ?>
    </ul></div></div>
</div>
<?php layout_end();
