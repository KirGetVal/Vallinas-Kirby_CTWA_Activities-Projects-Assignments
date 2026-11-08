<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
$u = Auth::requireRole(ROLE_ADMIN);
$all = Complaint::visibleTo($u);
$n = count_by_status($all);
$overdue = count(array_filter($all, 'is_overdue'));
$logs = Storage::all('activity_logs'); usort($logs, fn($a, $b) => strcmp($b['created_at'], $a['created_at']) ?: ($b['id'] <=> $a['id']));
$rep = Report::build($all);
$queue = count(array_filter($all, fn($c) => in_array($c['status'], [STATUS_SUBMITTED, STATUS_REVIEW, STATUS_REOPENED], true)));
$awaitingApproval = count(array_filter($all, fn($c) => $c['status'] === STATUS_RESOLVED));
$monthly = array_slice($rep['monthly'], -12, null, true);
$status = [];
foreach ($rep['by_status'] as $k => $v) { if ($v) { $status[STATUS_LABELS[$k]] = $v; } }

$GLOBALS['cms_needs_chartjs'] = true;
$charts = ['status' => $status, 'monthly' => $monthly, 'category' => $rep['by_category']];
$GLOBALS['cms_page_scripts'] = '<script>window.CMS_CHARTS=' . json_encode($charts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
    . ';</script><script src="' . e(base_url('assets/js/dashboard.js')) . '"></script>';
layout_start('Admin Dashboard', $u); ?>
<h1 class="h4 mb-4">Admin Dashboard</h1>
<?php if ($queue || $awaitingApproval): ?>
<div class="alert alert-warning d-flex flex-wrap gap-3 align-items-center py-2">
  <i class="bi bi-bell"></i>
  <?php if ($queue): ?><span><strong><?= $queue ?></strong> complaint(s) waiting for review or assignment —
    <a href="<?= e(base_url('admin/assign_complaint.php')) ?>">open assignment queue</a></span><?php endif; ?>
  <?php if ($awaitingApproval): ?><span><strong><?= $awaitingApproval ?></strong> resolution(s) awaiting your approval —
    <a href="<?= e(base_url('admin/complaints.php?status=resolved')) ?>">review</a></span><?php endif; ?>
</div>
<?php endif; ?>
<div class="row g-3 mb-4">
  <?php stat_card('Total Complaints', count($all), 'collection');
        stat_card('New', $n[STATUS_SUBMITTED], 'send', 'secondary');
        stat_card('Pending', $n[STATUS_SUBMITTED] + $n[STATUS_REVIEW] + $n[STATUS_ASSIGNED], 'inbox', 'info');
        stat_card('In Progress', $n[STATUS_PROGRESS] + $n[STATUS_AWAITING], 'hourglass-split', 'warning');
        stat_card('Resolved', $n[STATUS_RESOLVED], 'check-circle', 'success');
        stat_card('Closed', $n[STATUS_CLOSED], 'lock', 'dark');
        stat_card('Rejected', $n[STATUS_REJECTED], 'x-circle', 'danger');
        stat_card('Overdue', $overdue, 'exclamation-triangle', 'danger'); ?>
</div>
<div class="row g-4 mb-4">
  <div class="col-lg-4"><div class="card h-100"><div class="card-header bg-white fw-semibold">Status Distribution</div>
    <div class="card-body"><?php if ($all): ?><div class="chart-box"><canvas id="chartStatus"></canvas></div><?php else: ?><p class="text-muted text-center my-5">No data yet.</p><?php endif; ?></div></div></div>
  <div class="col-lg-4"><div class="card h-100"><div class="card-header bg-white fw-semibold">Monthly Submissions</div>
    <div class="card-body"><?php if ($all): ?><div class="chart-box"><canvas id="chartMonthly"></canvas></div><?php else: ?><p class="text-muted text-center my-5">No data yet.</p><?php endif; ?></div></div></div>
  <div class="col-lg-4"><div class="card h-100"><div class="card-header bg-white fw-semibold">Complaints by Category</div>
    <div class="card-body"><?php if ($all): ?><div class="chart-box"><canvas id="chartCategory"></canvas></div><?php else: ?><p class="text-muted text-center my-5">No data yet.</p><?php endif; ?></div></div></div>
</div>
<div class="row g-4">
  <div class="col-xl-8">
    <div class="card mb-4"><div class="card-header bg-white d-flex justify-content-between fw-semibold">Recent Complaints
      <a class="small fw-normal" href="<?= e(base_url('admin/complaints.php')) ?>">View all</a></div>
      <div class="card-body p-0"><?php render_complaint_table(array_slice($all, 0, 8), $u, true); ?></div></div>
    <div class="card"><div class="card-header bg-white fw-semibold">Staff Workload</div>
      <div class="card-body p-0"><?php render_staff_workload($rep['staff']); ?></div></div>
  </div>
  <div class="col-xl-4"><div class="card"><div class="card-header bg-white d-flex justify-content-between fw-semibold">Recent Activity
      <a class="small fw-normal" href="<?= e(base_url('admin/activity_logs.php')) ?>">View all</a></div>
    <ul class="list-group list-group-flush">
    <?php foreach (array_slice($logs, 0, 10) as $l): ?><li class="list-group-item small"><?= e($l['action']) ?>
      <div class="text-muted"><?= e($l['user_name']) ?> · <?= e(fmt_date($l['created_at'])) ?></div></li><?php endforeach;
    if (!$logs) echo '<li class="list-group-item text-muted text-center py-4">No activity yet.</li>'; ?>
    </ul></div></div>
</div>
<?php layout_end();
