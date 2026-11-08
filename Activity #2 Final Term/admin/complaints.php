<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
$u = Auth::requireRole(ROLE_ADMIN);

$q = Validator::clean($_GET['q'] ?? '');
$status = isset(STATUS_LABELS[$_GET['status'] ?? '']) ? $_GET['status'] : '';
$cat = (int)($_GET['category_id'] ?? 0);
$prio = isset(PRIORITIES[$_GET['priority'] ?? '']) ? $_GET['priority'] : '';
$assigned = (string)($_GET['assigned'] ?? '');     // '', 'none', or staff id
$overdueOnly = !empty($_GET['overdue']);

$rows = array_values(array_filter(Complaint::visibleTo($u), function ($c) use ($q, $status, $cat, $prio, $assigned, $overdueOnly) {
    if ($status !== '' && $c['status'] !== $status) { return false; }
    if ($cat && (int)$c['category_id'] !== $cat) { return false; }
    if ($prio !== '' && $c['priority'] !== $prio) { return false; }
    if ($assigned === 'none' && $c['assigned_to']) { return false; }
    if (ctype_digit($assigned) && (int)$c['assigned_to'] !== (int)$assigned) { return false; }
    if ($overdueOnly && !is_overdue($c)) { return false; }
    if ($q !== '' && stripos($c['reference'] . ' ' . $c['title'] . ' ' . user_name((int)$c['user_id']), $q) === false) { return false; }
    return true;
}));
$p = paginate($rows, (int)($_GET['page'] ?? 1));
$query = array_filter(['q' => $q, 'status' => $status, 'category_id' => $cat ?: '', 'priority' => $prio, 'assigned' => $assigned, 'overdue' => $overdueOnly ? 1 : ''], fn($v) => $v !== '');
$staff = Storage::where('users', fn($x) => $x['role'] === ROLE_STAFF);
$cats = Storage::all('categories');
layout_start('All Complaints', $u); ?>
<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h4 mb-0">All Complaints <span class="text-muted fs-6">(<?= $p['total'] ?>)</span></h1></div>
<form class="card card-body mb-3" method="get"><div class="row g-2 align-items-end">
  <div class="col-md-3"><label class="form-label small mb-1" for="q">Search</label><input class="form-control" id="q" name="q" value="<?= e($q) ?>" placeholder="Reference, title or complainant"></div>
  <div class="col-6 col-md-2"><label class="form-label small mb-1" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All</option>
    <?php foreach (STATUS_LABELS as $k => $l): ?><option value="<?= e($k) ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
  <div class="col-6 col-md-2"><label class="form-label small mb-1" for="category_id">Category</label><select class="form-select" id="category_id" name="category_id"><option value="">All</option>
    <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $cat === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
  <div class="col-6 col-md-1"><label class="form-label small mb-1" for="priority">Priority</label><select class="form-select" id="priority" name="priority"><option value="">All</option>
    <?php foreach (PRIORITIES as $k => $l): ?><option value="<?= e($k) ?>" <?= $prio === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
  <div class="col-6 col-md-2"><label class="form-label small mb-1" for="assigned">Assigned to</label><select class="form-select" id="assigned" name="assigned"><option value="">Anyone</option>
    <option value="none" <?= $assigned === 'none' ? 'selected' : '' ?>>Unassigned</option>
    <?php foreach ($staff as $s): ?><option value="<?= (int)$s['id'] ?>" <?= $assigned === (string)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2 d-flex gap-2 align-items-center">
    <div class="form-check mb-0"><input class="form-check-input" type="checkbox" name="overdue" value="1" id="overdue" <?= $overdueOnly ? 'checked' : '' ?>><label class="form-check-label small" for="overdue">Overdue</label></div>
    <button class="btn btn-primary" data-no-loading="1">Filter</button>
    <?php if ($query): ?><a class="btn btn-outline-secondary" href="<?= e(base_url('admin/complaints.php')) ?>">Reset</a><?php endif; ?></div>
</div></form>
<div class="card"><div class="card-body p-0">
<?php if (!$p['items']): ?><div class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>No complaints match your filters.</div>
<?php else: ?><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr>
  <th>Reference</th><th>Title</th><th>Complainant</th><th>Category</th><th>Status</th><th>Priority</th><th>Assigned</th><th>Submitted</th><th></th></tr></thead><tbody>
<?php foreach ($p['items'] as $c): $link = base_url('admin/complaint_details.php?id=' . (int)$c['id']); ?>
  <tr><td><a class="fw-semibold" href="<?= e($link) ?>"><?= e($c['reference']) ?></a></td><td><?= e(str_cut($c['title'], 50)) ?></td>
    <td><?= e(user_name((int)$c['user_id'])) ?></td><td><?= e(category_name((int)$c['category_id'])) ?></td>
    <td><?= status_badge($c['status']) ?><?= is_overdue($c) ? ' <span class="badge text-bg-danger">Overdue</span>' : '' ?></td>
    <td><?= priority_badge($c['priority']) ?></td><td><?= $c['assigned_to'] ? e(user_name((int)$c['assigned_to'])) : '<span class="text-muted">Unassigned</span>' ?></td>
    <td class="text-nowrap"><?= e(fmt_date($c['created_at'], false)) ?></td>
    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= e($link) ?>">Manage</a></td></tr>
<?php endforeach; ?></tbody></table></div><?php endif; ?></div>
<?php if ($p['pages'] > 1): ?><div class="card-footer bg-white"><?php render_pagination($p, $query); ?></div><?php endif; ?></div>
<?php layout_end();
