<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
$u = Auth::requireRole(ROLE_STAFF);
$q = Validator::clean($_GET['q'] ?? ''); $st = (string)($_GET['status'] ?? '');
$rows = array_values(array_filter(Complaint::visibleTo($u), fn($c) =>
    ($q === '' || stripos($c['title'] . ' ' . $c['reference'], $q) !== false) && ($st === '' || $c['status'] === $st)));
$p = paginate($rows, (int)($_GET['page'] ?? 1));
layout_start('Assigned Complaints', $u); ?>
<h1 class="h4 mb-3">Assigned Complaints</h1>
<div class="card mb-3"><div class="card-body"><form class="row g-2" method="get">
  <div class="col-md-6"><input class="form-control" name="q" placeholder="Search title or reference" value="<?= e($q) ?>"></div>
  <div class="col-md-4"><select class="form-select" name="status"><option value="">All statuses</option>
    <?php foreach (STATUS_LABELS as $k => $l): ?><option value="<?= $k ?>" <?= $st === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><button class="btn btn-primary w-100" data-no-loading="1"><i class="bi bi-funnel me-1"></i>Filter</button></div>
</form></div></div>
<div class="card"><div class="card-body p-0"><?php render_complaint_table($p['items'], $u, true); ?></div>
  <?php if ($p['pages'] > 1): ?><div class="card-footer bg-white"><?php render_pagination($p, ['q' => $q, 'status' => $st]); ?></div><?php endif; ?></div>
<?php layout_end();
