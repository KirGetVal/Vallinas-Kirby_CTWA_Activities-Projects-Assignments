<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
$u = Auth::requireRole(ROLE_ADMIN);
$cats = Storage::all('categories');
$count = [];
foreach (Storage::read('complaints') as $c) { $count[(int)$c['category_id']] = ($count[(int)$c['category_id']] ?? 0) + 1; }
$action = base_url('actions/category_process.php');
$GLOBALS['cms_page_scripts'] = '<script>document.addEventListener("click",function(e){var b=e.target.closest("[data-rename]");if(!b)return;'
    . 'document.getElementById("c_id").value=b.dataset.id;document.getElementById("c_name").value=b.dataset.name;});</script>';
layout_start('Categories', $u); ?>
<h1 class="h4 mb-3">Complaint Categories</h1>
<div class="row g-4"><div class="col-lg-4"><div class="card"><div class="card-header bg-white fw-semibold">Add category</div><div class="card-body">
  <form method="post" action="<?= e($action) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="add">
    <label class="form-label" for="n_name">Name</label><input class="form-control mb-3" id="n_name" name="name" required minlength="2" maxlength="80">
    <button class="btn btn-accent w-100"><i class="bi bi-plus-lg me-1"></i>Add category</button></form>
  <p class="text-muted small mt-3 mb-0">Deactivated categories disappear from the complaint form but stay on existing complaints and in reports.</p></div></div></div>
<div class="col-lg-8"><div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table align-middle mb-0">
  <thead class="table-light"><tr><th>Category</th><th>Complaints</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
  <?php foreach ($cats as $c): ?><tr><td class="fw-semibold"><?= e($c['name']) ?></td><td><?= (int)($count[(int)$c['id']] ?? 0) ?></td>
    <td><span class="badge text-bg-<?= $c['active'] ? 'success' : 'secondary' ?>"><?= $c['active'] ? 'Active' : 'Inactive' ?></span></td>
    <td class="text-end text-nowrap">
      <button class="btn btn-sm btn-outline-primary" data-rename data-id="<?= (int)$c['id'] ?>" data-name="<?= e($c['name']) ?>" data-bs-toggle="modal" data-bs-target="#renameModal" data-no-loading="1" title="Rename"><i class="bi bi-pencil"></i></button>
      <form class="d-inline" method="post" action="<?= e($action) ?>" data-confirm="<?= $c['active'] ? 'Deactivate this category?' : 'Activate this category?' ?>"><?= csrf_field() ?>
        <input type="hidden" name="action" value="toggle"><input type="hidden" name="category_id" value="<?= (int)$c['id'] ?>">
        <button class="btn btn-sm btn-outline-<?= $c['active'] ? 'danger' : 'success' ?>" title="<?= $c['active'] ? 'Deactivate' : 'Activate' ?>"><i class="bi bi-<?= $c['active'] ? 'toggle-on' : 'toggle-off' ?>"></i></button></form></td></tr>
  <?php endforeach; if (!$cats): ?><tr><td colspan="4" class="text-center text-muted py-4">No categories yet.</td></tr><?php endif; ?>
  </tbody></table></div></div></div></div></div>
<div class="modal fade" id="renameModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form class="modal-content" method="post" action="<?= e($action) ?>"><?= csrf_field() ?>
  <input type="hidden" name="action" value="rename"><input type="hidden" name="category_id" id="c_id">
  <div class="modal-header"><h5 class="modal-title">Rename category</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
  <div class="modal-body"><label class="form-label" for="c_name">Name</label><input class="form-control" id="c_name" name="name" required minlength="2" maxlength="80"></div>
  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save</button></div></form></div></div>
<?php layout_end();
