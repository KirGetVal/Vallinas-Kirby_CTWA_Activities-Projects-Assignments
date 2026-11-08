<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
$u = Auth::requireRole(ROLE_ADMIN);
$f = Report::filters($_GET);
$rows = Report::complaints($f);
$r = Report::build($rows);
$qs = http_build_query(array_filter($f, fn($v) => $v !== '' && $v !== 0));
$cats = Storage::all('categories');
$status = [];
foreach ($r['by_status'] as $k => $v) { if ($v) { $status[STATUS_LABELS[$k]] = $v; } }

$GLOBALS['cms_needs_chartjs'] = true;
$GLOBALS['cms_page_scripts'] = '<script>window.CMS_CHARTS=' . json_encode(['status' => $status, 'monthly' => $r['monthly'], 'category' => $r['by_category']],
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script><script src="' . e(base_url('assets/js/dashboard.js')) . '"></script>';
layout_start('Reports', $u); ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <h1 class="h4 mb-0">Reports &amp; Analytics</h1>
  <div class="d-flex gap-2 no-print">
    <button class="btn btn-outline-secondary" type="button" onclick="window.print()" data-no-loading="1"><i class="bi bi-printer me-1"></i>Print / Save as PDF</button>
    <a class="btn btn-accent" href="<?= e(base_url('admin/export_report.php' . ($qs ? '?' . $qs : ''))) ?>"><i class="bi bi-filetype-csv me-1"></i>Export CSV</a></div>
</div>
<form class="card card-body mb-3 no-print" method="get"><div class="row g-2 align-items-end">
  <div class="col-6 col-md-2"><label class="form-label small mb-1" for="from">From</label><input class="form-control" type="date" id="from" name="from" value="<?= e($f['from']) ?>"></div>
  <div class="col-6 col-md-2"><label class="form-label small mb-1" for="to">To</label><input class="form-control" type="date" id="to" name="to" value="<?= e($f['to']) ?>"></div>
  <div class="col-md-3"><label class="form-label small mb-1" for="category_id">Category</label><select class="form-select" id="category_id" name="category_id"><option value="">All categories</option>
    <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $f['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><label class="form-label small mb-1" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option>
    <?php foreach (STATUS_LABELS as $k => $l): ?><option value="<?= e($k) ?>" <?= $f['status'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2 d-flex gap-2"><button class="btn btn-primary" data-no-loading="1">Apply</button><?php if ($qs): ?><a class="btn btn-outline-secondary" href="?">Reset</a><?php endif; ?></div>
</div></form>
<p class="text-muted small">Report generated <?= e(fmt_date(now())) ?> · Period: <?= e($f['from'] ?: 'beginning') ?> to <?= e($f['to'] ?: 'today') ?>
  · Category: <?= $f['category_id'] ? e(category_name($f['category_id'])) : 'All' ?> · Status: <?= $f['status'] ? e(STATUS_LABELS[$f['status']]) : 'All' ?></p>

<div class="row g-3 mb-4">
  <?php stat_card('Total', $r['total'], 'collection');
        stat_card('Resolved / Closed', $r['resolved'], 'check-circle', 'success');
        stat_card('Unresolved', $r['unresolved'], 'hourglass-split', 'warning');
        stat_card('Rejected', $r['rejected'], 'x-circle', 'danger');
        stat_card('Overdue', $r['overdue'], 'exclamation-triangle', 'danger');
        stat_card('Avg. resolution', $r['avg_days'] === null ? '-' : number_format($r['avg_days'], 1) . ' d', 'stopwatch', 'info'); ?>
</div>
<?php if (!$rows): ?><div class="card"><div class="card-body text-center text-muted py-5"><i class="bi bi-bar-chart fs-1 d-block mb-2"></i>No complaints match these filters.</div></div>
<?php else: ?>
<div class="row g-4 mb-4">
  <div class="col-lg-4"><div class="card h-100"><div class="card-header bg-white fw-semibold">By status</div><div class="card-body"><div class="chart-box"><canvas id="chartStatusR"></canvas></div></div></div></div>
  <div class="col-lg-4"><div class="card h-100"><div class="card-header bg-white fw-semibold">Monthly submissions</div><div class="card-body"><div class="chart-box"><canvas id="chartMonthlyR"></canvas></div></div></div></div>
  <div class="col-lg-4"><div class="card h-100"><div class="card-header bg-white fw-semibold">By category</div><div class="card-body"><div class="chart-box"><canvas id="chartCategoryR"></canvas></div></div></div></div>
</div>
<div class="row g-4 mb-4">
  <div class="col-md-6"><div class="card h-100"><div class="card-header bg-white fw-semibold">Complaints by status</div><div class="card-body p-0"><table class="table mb-0">
    <?php foreach ($r['by_status'] as $k => $v): ?><tr><td><?= status_badge($k) ?></td><td class="text-end"><?= $v ?></td><td class="text-end text-muted" style="width:70px"><?= e(number_format($v / $r['total'] * 100, 1)) ?>%</td></tr><?php endforeach; ?></table></div></div></div>
  <div class="col-md-6"><div class="card h-100"><div class="card-header bg-white fw-semibold">Complaints by category</div><div class="card-body p-0"><table class="table mb-0">
    <?php foreach ($r['by_category'] as $k => $v): ?><tr><td><?= e($k) ?></td><td class="text-end"><?= $v ?></td><td class="text-end text-muted" style="width:70px"><?= e(number_format($v / $r['total'] * 100, 1)) ?>%</td></tr><?php endforeach; ?></table></div></div></div>
</div>
<div class="card mb-4"><div class="card-header bg-white fw-semibold">Staff workload &amp; handling performance</div><div class="card-body p-0"><?php render_staff_workload($r['staff']); ?></div>
  <?php if ($r['avg_rating'] !== null): ?><div class="card-footer bg-white small">Average satisfaction: <strong><?= e(number_format($r['avg_rating'], 1)) ?> / 5</strong> from <?= (int)$r['rating_count'] ?> feedback response(s)</div><?php endif; ?></div>
<div class="card"><div class="card-header bg-white fw-semibold">Complaint details (<?= count($rows) ?>)</div><div class="card-body p-0"><div class="table-responsive"><table class="table table-sm table-striped align-middle mb-0">
  <thead class="table-light"><tr><th>Reference</th><th>Title</th><th>Category</th><th>Status</th><th>Priority</th><th>Assigned</th><th>Submitted</th></tr></thead><tbody>
  <?php foreach ($rows as $c): ?><tr><td><a href="<?= e(base_url('admin/complaint_details.php?id=' . (int)$c['id'])) ?>"><?= e($c['reference']) ?></a></td><td><?= e(str_cut($c['title'], 45)) ?></td>
    <td><?= e(category_name((int)$c['category_id'])) ?></td><td><?= e(STATUS_LABELS[$c['status']]) ?></td><td><?= e(PRIORITIES[$c['priority']] ?? $c['priority']) ?></td>
    <td><?= $c['assigned_to'] ? e(user_name((int)$c['assigned_to'])) : '-' ?></td><td class="text-nowrap"><?= e(fmt_date($c['created_at'], false)) ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
<?php endif; layout_end();
