<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
$u = Auth::requireRole(ROLE_ADMIN);
$s = Storage::read('settings');
$demo = array_filter(Storage::read('users'), fn($x) => in_array($x['email'], ['admin@cms.local', 'staff@cms.local', 'user@cms.local'], true) && $x['active']);
layout_start('Settings', $u); ?>
<h1 class="h4 mb-3">System Settings</h1>
<div class="card" style="max-width:640px"><div class="card-body">
  <form method="post" action="<?= e(base_url('actions/settings_process.php')) ?>"><?= csrf_field() ?>
    <div class="mb-3"><label class="form-label" for="org_name">Organisation / system name</label>
      <input class="form-control" id="org_name" name="org_name" required minlength="2" maxlength="80" value="<?= e($s['org_name'] ?? APP_NAME) ?>">
      <div class="form-text">Shown in the page title, the top bar and the sign-in page.</div></div>
    <div class="mb-3"><label class="form-label" for="overdue_days">Overdue threshold (days)</label>
      <input class="form-control" type="number" id="overdue_days" name="overdue_days" min="1" max="90" required value="<?= (int)($s['overdue_days'] ?? 7) ?>">
      <div class="form-text">An unresolved complaint is flagged overdue this many days after submission. Affects dashboards, lists and reports.</div></div>
    <button class="btn btn-primary">Save settings</button></form></div></div>
<?php layout_end();
