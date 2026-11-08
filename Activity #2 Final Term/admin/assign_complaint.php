<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
$u = Auth::requireRole(ROLE_ADMIN);

$work = Complaint::staffWorkload();
$staff = Storage::where('users', fn($x) => $x['role'] === ROLE_STAFF && $x['active']);
$assignable = [STATUS_SUBMITTED, STATUS_REVIEW, STATUS_REOPENED];
$queue = array_values(array_filter(Complaint::visibleTo($u), fn($c) => in_array($c['status'], $assignable, true)));
usort($queue, fn($a, $b) => array_search($b['priority'], array_keys(PRIORITIES)) <=> array_search($a['priority'], array_keys(PRIORITIES)) ?: strcmp($a['created_at'], $b['created_at']));
$focus = (int)($_GET['id'] ?? 0);
$action = base_url('actions/assignment_process.php');
layout_start('Assign Complaints', $u); ?>
<h1 class="h4 mb-1">Assignment Queue</h1>
<p class="text-muted">Complaints waiting to be assigned, most urgent first. Assigning a new complaint also starts its review. Set the priority here; users cannot choose it themselves.</p>
<?php if (!$staff): ?><div class="alert alert-warning">There are no active staff accounts. <a href="<?= e(base_url('admin/staff.php')) ?>">Create a staff account</a> first.</div><?php endif; ?>
<div class="card"><div class="card-body p-0">
<?php if (!$queue): ?><div class="text-center text-muted py-5"><i class="bi bi-check2-circle fs-1 d-block mb-2"></i>Nothing to assign. New complaints appear here.</div>
<?php else: ?><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Complaint</th><th>Status</th><th style="min-width:420px">Assign</th></tr></thead><tbody>
<?php foreach ($queue as $c): ?>
  <tr class="<?= $focus === (int)$c['id'] ? 'table-warning' : '' ?>">
    <td><a class="fw-semibold" href="<?= e(base_url('admin/complaint_details.php?id=' . (int)$c['id'])) ?>"><?= e($c['reference']) ?></a>
      <div><?= e(str_cut($c['title'], 55)) ?></div>
      <div class="small text-muted"><?= e(category_name((int)$c['category_id'])) ?> · <?= e(user_name((int)$c['user_id'])) ?> · <?= e(fmt_date($c['created_at'], false)) ?></div></td>
    <td><?= status_badge($c['status']) ?></td>
    <td><form method="post" action="<?= e($action) ?>" class="row g-2"><?= csrf_field() ?>
      <input type="hidden" name="complaint_id" value="<?= (int)$c['id'] ?>"><input type="hidden" name="back" value="queue">
      <div class="col-sm-6"><select class="form-select form-select-sm" name="staff_id" required aria-label="Staff member"><option value="">Select staff…</option>
        <?php foreach ($staff as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?> (<?= (int)($work[(int)$s['id']] ?? 0) ?> open)</option><?php endforeach; ?></select></div>
      <div class="col-sm-4"><select class="form-select form-select-sm" name="priority" aria-label="Priority">
        <?php foreach (PRIORITIES as $k => $l): ?><option value="<?= e($k) ?>" <?= $c['priority'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
      <div class="col-sm-2"><button class="btn btn-sm btn-primary w-100" <?= $staff ? '' : 'disabled' ?>>Assign</button></div></form></td></tr>
<?php endforeach; ?></tbody></table></div><?php endif; ?></div></div>
<?php layout_end();
