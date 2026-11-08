<?php
// Shared profile view. Expects $u.
$errors = $_SESSION['errors'] ?? []; unset($_SESSION['errors']);
$inv = fn($k) => isset($errors[$k]) ? 'is-invalid' : '';
$err = fn($k) => isset($errors[$k]) ? '<div class="invalid-feedback">' . e($errors[$k]) . '</div>' : '';
layout_start('My Profile', $u); ?>
<h1 class="h4 mb-3">My Profile</h1>
<div class="row g-4">
  <div class="col-lg-6"><div class="card"><div class="card-header bg-white fw-semibold">Account details</div><div class="card-body">
    <form method="post" action="<?= e(base_url('actions/profile_process.php')) ?>" novalidate><?= csrf_field() ?>
      <input type="hidden" name="action" value="profile">
      <div class="mb-3"><label class="form-label">Email</label><input class="form-control" value="<?= e($u['email']) ?>" disabled></div>
      <div class="mb-3"><label class="form-label" for="name">Full name</label><input class="form-control <?= $inv('name') ?>" id="name" name="name" required value="<?= e($u['name']) ?>"><?= $err('name') ?></div>
      <div class="mb-3"><label class="form-label" for="phone">Phone</label><input class="form-control <?= $inv('phone') ?>" id="phone" name="phone" value="<?= e($u['phone']) ?>"><?= $err('phone') ?></div>
      <button class="btn btn-primary" type="submit">Save changes</button></form></div></div></div>
  <div class="col-lg-6"><div class="card"><div class="card-header bg-white fw-semibold">Change password</div><div class="card-body">
    <form method="post" action="<?= e(base_url('actions/profile_process.php')) ?>" novalidate><?= csrf_field() ?>
      <input type="hidden" name="action" value="password">
      <div class="mb-3"><label class="form-label" for="current">Current password</label><input class="form-control <?= $inv('current') ?>" type="password" id="current" name="current" required><?= $err('current') ?></div>
      <div class="mb-3"><label class="form-label" for="password">New password</label><input class="form-control <?= $inv('password') ?>" type="password" id="password" name="password" required minlength="8"><?= $err('password') ?></div>
      <div class="mb-3"><label class="form-label" for="confirm">Confirm new password</label><input class="form-control <?= $inv('confirm') ?>" type="password" id="confirm" name="confirm" required data-match="#password"><?= $err('confirm') ?></div>
      <button class="btn btn-primary" type="submit">Update password</button></form></div></div></div>
</div>
<?php layout_end();
