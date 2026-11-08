<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
$u = Auth::requireRole(ROLE_ADMIN);
$q = Validator::clean($_GET['q'] ?? '');
$from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : '';
$to = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '') ? $_GET['to'] : '';
$logs = Storage::where('activity_logs', function ($l) use ($q, $from, $to) {
    $d = substr($l['created_at'], 0, 10);
    return ($from === '' || $d >= $from) && ($to === '' || $d <= $to)
        && ($q === '' || stripos($l['action'] . ' ' . $l['user_name'], $q) !== false);
});
usort($logs, fn($a, $b) => strcmp($b['created_at'], $a['created_at']) ?: ($b['id'] <=> $a['id']));
$p = paginate($logs, (int)($_GET['page'] ?? 1), 25);
$query = array_filter(['q' => $q, 'from' => $from, 'to' => $to]);
layout_start('Activity Logs', $u); ?>
<h1 class="h4 mb-3">Activity Logs <span class="text-muted fs-6">(<?= $p['total'] ?>)</span></h1>
<form class="card card-body mb-3" method="get"><div class="row g-2 align-items-end">
  <div class="col-md-5"><label class="form-label small mb-1" for="q">Search action or user</label><input class="form-control" id="q" name="q" value="<?= e($q) ?>"></div>
  <div class="col-6 col-md-2"><label class="form-label small mb-1" for="from">From</label><input class="form-control" type="date" id="from" name="from" value="<?= e($from) ?>"></div>
  <div class="col-6 col-md-2"><label class="form-label small mb-1" for="to">To</label><input class="form-control" type="date" id="to" name="to" value="<?= e($to) ?>"></div>
  <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary" data-no-loading="1">Filter</button><?php if ($query): ?><a class="btn btn-outline-secondary" href="?">Reset</a><?php endif; ?></div></div></form>
<div class="card"><div class="card-body p-0">
<?php if (!$p['items']): ?><div class="text-center text-muted py-5"><i class="bi bi-clock-history fs-1 d-block mb-2"></i>No activity matches your filters.</div>
<?php else: ?><div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0"><thead class="table-light"><tr><th>When</th><th>User</th><th>Action</th><th>Record</th><th>IP</th></tr></thead><tbody>
<?php foreach ($p['items'] as $l): ?><tr><td class="text-nowrap"><?= e(fmt_date($l['created_at'])) ?></td><td><?= e($l['user_name']) ?></td><td><?= e($l['action']) ?></td>
  <td><?= $l['related_id'] ? '#' . (int)$l['related_id'] : '-' ?></td><td class="text-muted small"><?= e($l['ip'] ?? '') ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div>
<?php if ($p['pages'] > 1): ?><div class="card-footer bg-white"><?php render_pagination($p, $query); ?></div><?php endif; ?></div>
<?php layout_end();
