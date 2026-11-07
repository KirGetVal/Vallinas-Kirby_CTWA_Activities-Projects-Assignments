<?php
require_once __DIR__ . '/core/bootstrap.php';
if ($u = Auth::user()) { redirect(Auth::homeFor($u['role'])); }
$errors = $_SESSION['errors'] ?? []; unset($_SESSION['errors']);
$pageTitle = 'Register'; $bodyClass = 'auth';
include __DIR__ . '/includes/header.php';
$field = function (string $k, string $label, string $type = 'text', string $extra = '') use ($errors) { ?>
  <div class="mb-3">
    <label class="form-label" for="<?= $k ?>"><?= e($label) ?></label>
    <input class="form-control <?= isset($errors[$k]) ? 'is-invalid' : '' ?>" type="<?= $type ?>" id="<?= $k ?>" name="<?= $k ?>"
           required <?= $extra ?> <?= $type !== 'password' ? 'value="' . old($k) . '"' : '' ?>>
    <?php if (isset($errors[$k])): ?><div class="invalid-feedback"><?= e($errors[$k]) ?></div><?php endif; ?>
  </div>
<?php };
?>
<div class="auth-wrap"><div class="auth-card">
  <div class="text-center mb-4">
    <?= brand_logo(72, 'person-plus') ?>
    <div class="fw-semibold text-primary mt-3"><?= e(school_name()) ?></div>
    <h1 class="h4 mb-1">Create your account</h1>
  </div>
  <div class="card"><div class="card-body p-4">
    <?php include __DIR__ . '/includes/alerts.php'; ?>
    <form method="post" action="<?= e(base_url('actions/register_process.php')) ?>" novalidate>
      <?= csrf_field() ?>
      <?php $field('name', 'Full name'); $field('email', 'Email', 'email'); $field('phone', 'Phone (optional)', 'tel', ''); ?>
      <?php $field('password', 'Password', 'password', 'minlength="8" autocomplete="new-password"'); ?>
      <?php $field('password_confirm', 'Confirm password', 'password', 'data-match="#password" autocomplete="new-password"'); ?>
      <button class="btn btn-primary w-100" type="submit">Register</button>
    </form>
    <p class="text-center small mt-3 mb-0">Already registered? <a href="<?= e(base_url('login.php')) ?>">Sign in</a></p>
  </div></div>
  <?= school_footer('mt-4') ?>
</div></div>
<?php unset($_SESSION['old']); include __DIR__ . '/includes/footer.php'; ?>
