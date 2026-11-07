<?php
// Standalone error screen (works for signed-in and anonymous visitors). Expects $code, $title, $detail.
$pageTitle = $title;
$u = Auth::user();
require __DIR__ . '/header.php'; ?>
<div class="auth-wrap"><div class="card auth-card text-center"><div class="card-body p-4 p-md-5">
  <div class="mb-3"><?= brand_logo(64) ?><div class="fw-semibold text-primary mt-2"><?= e(school_name()) ?></div></div>
  <div class="display-4 fw-bold text-primary mb-1"><?= (int)$code ?></div>
  <h1 class="h4 mb-2"><?= e($title) ?></h1>
  <p class="text-muted mb-4"><?= e($detail) ?></p>
  <div class="d-flex flex-wrap gap-2 justify-content-center">
    <?php if ($u): ?><a class="btn btn-primary" href="<?= e(base_url(Auth::homeFor($u['role']))) ?>"><i class="bi bi-house me-1"></i>Go to my dashboard</a>
    <?php else: ?><a class="btn btn-primary" href="<?= e(base_url('login.php')) ?>"><i class="bi bi-box-arrow-in-right me-1"></i>Sign in</a><?php endif; ?>
    <a class="btn btn-outline-secondary" href="javascript:history.back()" data-no-loading="1">Go back</a>
  </div>
</div></div></div>
<?php require __DIR__ . '/footer.php';
