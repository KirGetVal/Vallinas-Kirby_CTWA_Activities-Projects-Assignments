<?php
require_once __DIR__ . '/core/bootstrap.php';
if ($u = Auth::user()) { redirect(Auth::homeFor($u['role'])); }
$pageTitle = 'Sign in'; $bodyClass = 'auth';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap"><div class="auth-card">
  <div class="text-center mb-4">
    <span class="brand-mark"><i class="bi bi-clipboard2-check"></i></span>
    <h1 class="h4 mt-3 mb-1"><?= e(app_name()) ?></h1>
    <p class="text-muted small mb-0">Sign in to submit and track complaints</p>
  </div>
  <div class="card"><div class="card-body p-4">
    <?php include __DIR__ . '/includes/alerts.php'; ?>
    <form method="post" action="<?= e(base_url('actions/login_process.php')) ?>" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" type="email" id="email" name="email" required autofocus value="<?= old('email') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label" for="password">Password</label>
        <input class="form-control" type="password" id="password" name="password" required>
      </div>
      <button class="btn btn-primary w-100" type="submit">Sign in</button>
    </form>
    <p class="text-center small mt-3 mb-0">No account? <a href="<?= e(base_url('register.php')) ?>">Register</a></p>
  </div></div>
  <?php if (SEED_DEMO_DATA): ?>
  <div class="alert alert-warning small mt-3 mb-0">
    <strong>Demo credentials (development only):</strong><br>
    Admin: admin@cms.local / Admin123!<br>Staff: staff@cms.local / Staff123!<br>User: user@cms.local / User123!<br>
  </div>
  <?php endif; ?>
</div></div>
<?php unset($_SESSION['old']); include __DIR__ . '/includes/footer.php'; ?>
