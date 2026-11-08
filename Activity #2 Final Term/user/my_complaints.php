<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
$u = Auth::requireRole(ROLE_USER);
$q = Validator::clean($_GET['q'] ?? ''); $st = (string)($_GET['status'] ?? ''); $cat = (int)($_GET['category'] ?? 0);
$rows = array_filter(Complaint::visibleTo($u), function ($c) use ($q, $st, $cat) {
    return ($q === '' || stripos($c['title'] . ' ' . $c['reference'], $q) !== false)
        && ($st === '' || $c['status'] === $st) && (!$cat || (int)$c['category_id'] === $cat);
});
$p = paginate(array_values($rows), (int)($_GET['page'] ?? 1));
layout_start('My Complaints', $u); ?>
<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h4 mb-0">My Complaints</h1>
  <a class="btn btn-accent" href="<?= e(base_url('user/submit_complaint.php')) ?>"><i class="bi bi-plus-circle me-1"></i>New</a></div>
<div class="card mb-3"><div class="card-body"><form class="row g-2" method="get">
  <div class="col-md-5"><input class="form-control" name="q" placeholder="Search title or reference" value="<?= e($q) ?>"></div>
  <div class="col-md-3"><select class="form-select" name="status"><option value="">All statuses</option>
    <?php foreach (STATUS_LABELS as $k => $l): ?><option value="<?= $k ?>" <?= $st === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><select class="form-select" name="category"><option value="0">All categories</option>
    <?php foreach (Storage::all('categories') as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $cat === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-1"><button class="btn btn-primary w-100" data-no-loading="1"><i class="bi bi-funnel"></i></button></div>
</form></div></div>
<div class="card"><div class="card-body p-0"><?php render_complaint_table($p['items'], $u); ?></div>
  <?php if ($p['pages'] > 1): ?><div class="card-footer bg-white"><?php render_pagination($p, ['q' => $q, 'status' => $st, 'category' => $cat]); ?></div><?php endif; ?></div>
<?php layout_end();
