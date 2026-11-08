<?php
// Shared account manager. Expects $u (admin) and $role (ROLE_USER or ROLE_STAFF).
$isStaff = $role === ROLE_STAFF;
$label = $isStaff ? 'Staff' : 'Users';
$back = $isStaff ? 'staff' : 'users';
$q = Validator::clean($_GET['q'] ?? '');
$state = in_array($_GET['state'] ?? '', ['active', 'inactive'], true) ? $_GET['state'] : '';
$allComplaints = Storage::read('complaints');
$work = Complaint::staffWorkload();
$rows = Storage::where('users', function ($x) use ($role, $q, $state) {
    return $x['role'] === $role
        && ($q === '' || stripos($x['name'] . ' ' . $x['email'], $q) !== false)
        && ($state === '' || ($state === 'active') === (bool)$x['active']);
});
usort($rows, fn($a, $b) => strcasecmp($a['name'], $b['name']));
$p = paginate($rows, (int)($_GET['page'] ?? 1));
$query = array_filter(['q' => $q, 'state' => $state]);
$action = base_url('actions/user_process.php');
$GLOBALS['cms_page_scripts'] = '<script src="' . e(base_url('assets/js/accounts.js')) . '"></script>';
layout_start($isStaff ? 'Staff Accounts' : 'User Accounts', $u); ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <h1 class="h4 mb-0"><?= $isStaff ? 'Staff Accounts' : 'User Accounts' ?> <span class="text-muted fs-6">(<?= $p['total'] ?>)</span></h1>
  <?php if ($isStaff): ?><button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#addStaff" data-no-loading="1"><i class="bi bi-person-plus me-1"></i>Add staff member</button><?php endif; ?>
</div>
<?php if ($isStaff): ?>
<div class="collapse <?= !empty($_SESSION['old']) ? 'show' : '' ?>" id="addStaff"><div class="card card-body mb-3">
  <form method="post" action="<?= e($action) ?>" class="row g-3"><?= csrf_field() ?>
    <input type="hidden" name="action" value="create_staff"><input type="hidden" name="back" value="staff">
    <div class="col-md-4"><label class="form-label" for="s_name">Full name *</label><input class="form-control" id="s_name" name="name" required minlength="2" maxlength="100" value="<?= old('name') ?>"></div>
    <div class="col-md-4"><label class="form-label" for="s_email">Email *</label><input class="form-control" type="email" id="s_email" name="email" required maxlength="150" value="<?= old('email') ?>"></div>
    <div class="col-md-4"><label class="form-label" for="s_phone">Phone</label><input class="form-control" id="s_phone" name="phone" maxlength="30" value="<?= old('phone') ?>"></div>
    <div class="col-md-6"><label class="form-label" for="s_pw">Temporary password *</label><input class="form-control" type="text" id="s_pw" name="password" required minlength="8" autocomplete="new-password">
      <div class="form-text">At least 8 characters with a letter and a number. Share it securely; the staff member can change it in My Profile.</div></div>
    <div class="col-md-6 d-flex align-items-end"><button class="btn btn-accent">Create staff account</button></div>
  </form></div></div>
<?php unset($_SESSION['old']); endif; ?>
<form class="card card-body mb-3" method="get"><div class="row g-2">
  <div class="col-md-6"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search by name or email" aria-label="Search"></div>
  <div class="col-md-3"><select class="form-select" name="state" aria-label="Status"><option value="">All statuses</option>
    <option value="active" <?= $state === 'active' ? 'selected' : '' ?>>Active</option><option value="inactive" <?= $state === 'inactive' ? 'selected' : '' ?>>Inactive</option></select></div>
  <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary" data-no-loading="1">Filter</button>
    <?php if ($query): ?><a class="btn btn-outline-secondary" href="?">Reset</a><?php endif; ?></div></div></form>
<div class="card"><div class="card-body p-0">
<?php if (!$p['items']): ?><div class="text-center text-muted py-5"><i class="bi bi-people fs-1 d-block mb-2"></i>No <?= strtolower($label) ?> found.</div>
<?php else: ?><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr>
  <th>Name</th><th>Email</th><th>Phone</th><th><?= $isStaff ? 'Open cases' : 'Complaints' ?></th><th>Status</th><th>Joined</th><th class="text-end">Actions</th></tr></thead><tbody>
<?php foreach ($p['items'] as $r):
    $cnt = $isStaff ? (int)($work[(int)$r['id']] ?? 0) : count(array_filter($allComplaints, fn($c) => (int)$c['user_id'] === (int)$r['id'])); ?>
  <tr><td class="fw-semibold"><?= e($r['name']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['phone'] ?: '-') ?></td><td><?= $cnt ?></td>
    <td><span class="badge text-bg-<?= $r['active'] ? 'success' : 'secondary' ?>"><?= $r['active'] ? 'Active' : 'Inactive' ?></span></td>
    <td class="text-nowrap"><?= e(fmt_date($r['created_at'], false)) ?></td>
    <td class="text-end text-nowrap">
      <button class="btn btn-sm btn-outline-primary" data-edit data-id="<?= (int)$r['id'] ?>" data-name="<?= e($r['name']) ?>" data-email="<?= e($r['email']) ?>" data-phone="<?= e($r['phone']) ?>"
        data-bs-toggle="modal" data-bs-target="#editModal" data-no-loading="1" title="Edit"><i class="bi bi-pencil"></i></button>
      <button class="btn btn-sm btn-outline-secondary" data-reset data-id="<?= (int)$r['id'] ?>" data-name="<?= e($r['name']) ?>"
        data-bs-toggle="modal" data-bs-target="#resetModal" data-no-loading="1" title="Reset password"><i class="bi bi-key"></i></button>
      <form class="d-inline" method="post" action="<?= e($action) ?>" data-confirm="<?= $r['active'] ? 'Deactivate ' . e($r['name']) . '? They will no longer be able to sign in.' : 'Activate ' . e($r['name']) . '?' ?>"><?= csrf_field() ?>
        <input type="hidden" name="action" value="toggle"><input type="hidden" name="user_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="back" value="<?= $back ?>">
        <button class="btn btn-sm btn-outline-<?= $r['active'] ? 'danger' : 'success' ?>" title="<?= $r['active'] ? 'Deactivate' : 'Activate' ?>"><i class="bi bi-<?= $r['active'] ? 'person-x' : 'person-check' ?>"></i></button></form>
    </td></tr>
<?php endforeach; ?></tbody></table></div><?php endif; ?></div>
<?php if ($p['pages'] > 1): ?><div class="card-footer bg-white"><?php render_pagination($p, $query); ?></div><?php endif; ?></div>

<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form class="modal-content" method="post" action="<?= e($action) ?>"><?= csrf_field() ?>
  <input type="hidden" name="action" value="update"><input type="hidden" name="user_id" id="e_id"><input type="hidden" name="back" value="<?= $back ?>">
  <div class="modal-header"><h5 class="modal-title">Edit account</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
  <div class="modal-body vstack gap-3">
    <div><label class="form-label" for="e_name">Full name</label><input class="form-control" id="e_name" name="name" required minlength="2" maxlength="100"></div>
    <div><label class="form-label" for="e_email">Email</label><input class="form-control" type="email" id="e_email" name="email" required maxlength="150"></div>
    <div><label class="form-label" for="e_phone">Phone</label><input class="form-control" id="e_phone" name="phone" maxlength="30"></div></div>
  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save changes</button></div></form></div></div>
<div class="modal fade" id="resetModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form class="modal-content" method="post" action="<?= e($action) ?>"><?= csrf_field() ?>
  <input type="hidden" name="action" value="reset_password"><input type="hidden" name="user_id" id="r_id"><input type="hidden" name="back" value="<?= $back ?>">
  <div class="modal-header"><h5 class="modal-title">Reset password</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
  <div class="modal-body"><p class="mb-2">Set a new password for <strong id="r_name"></strong>.</p>
    <label class="form-label" for="r_pw">New password</label><input class="form-control" type="text" id="r_pw" name="password" required minlength="8" autocomplete="new-password">
    <div class="form-text">At least 8 characters with a letter and a number.</div></div>
  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Reset password</button></div></form></div></div>
<?php layout_end();
