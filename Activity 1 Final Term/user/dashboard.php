<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
$u = Auth::requireRole(ROLE_USER);
layout_start('Dashboard', $u); ?>
<div class="mb-4">
  <h1 class="h4 mb-0">Welcome, <?= e($u['name']) ?></h1>
  <p class="text-muted mb-0">Your dashboard.</p>
</div>
<div class="card"><div class="card-body d-flex align-items-center gap-3">
  <div class="stat-icon text-bg-primary"><i class="bi bi-person-circle"></i></div>
  <div>
    <div class="fw-semibold"><?= e($u['email']) ?></div>
    <div class="text-muted small text-capitalize">Signed in as <?= e($u['role']) ?></div>
  </div>
</div></div>
<?php layout_end();
