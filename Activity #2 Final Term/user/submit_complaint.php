<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
$u = Auth::requireRole(ROLE_USER);
$errors = $_SESSION['errors'] ?? []; unset($_SESSION['errors']);
$_SESSION['form_nonce'] = bin2hex(random_bytes(16));   // one-time token: blocks double submits
$cats = Storage::where('categories', fn($c) => $c['active']);
$inv = fn($k) => isset($errors[$k]) ? 'is-invalid' : '';
$err = fn($k) => isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . e($errors[$k]) . '</div>' : '';
layout_start('Submit Complaint', $u); ?>
<h1 class="h4 mb-3">Submit a New Complaint</h1>
<div class="card"><div class="card-body p-4">
<form method="post" action="<?= e(base_url('actions/complaint_process.php')) ?>" enctype="multipart/form-data" novalidate>
  <?= csrf_field() ?><input type="hidden" name="form_nonce" value="<?= e($_SESSION['form_nonce']) ?>">
  <div class="row g-3">
    <div class="col-md-8"><label class="form-label" for="title">Complaint title *</label>
      <input class="form-control <?= $inv('title') ?>" id="title" name="title" maxlength="150" required value="<?= old('title') ?>"><?= $err('title') ?></div>
    <div class="col-md-4"><label class="form-label" for="category_id">Category *</label>
      <select class="form-select <?= $inv('category_id') ?>" id="category_id" name="category_id" required>
        <option value="">Select category</option>
        <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= old('category_id') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
      </select><?= $err('category_id') ?></div>
    <div class="col-12"><label class="form-label" for="description">Description *</label>
      <textarea class="form-control <?= $inv('description') ?>" id="description" name="description" rows="5" maxlength="3000" required><?= old('description') ?></textarea><?= $err('description') ?></div>
    <div class="col-md-6"><label class="form-label" for="location">Incident location *</label>
      <input class="form-control <?= $inv('location') ?>" id="location" name="location" maxlength="150" required value="<?= old('location') ?>"><?= $err('location') ?></div>
    <div class="col-md-6"><label class="form-label" for="incident_date">Incident date *</label>
      <input class="form-control <?= $inv('incident_date') ?>" type="date" id="incident_date" name="incident_date" max="<?= date('Y-m-d') ?>" required value="<?= old('incident_date') ?>"><?= $err('incident_date') ?></div>
    <div class="col-12"><label class="form-label" for="evidence">Supporting evidence (optional)</label>
      <input class="form-control <?= $inv('evidence') ?>" type="file" id="evidence" name="evidence[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.txt">
      <div class="form-text">JPG, PNG, PDF, DOC, DOCX or TXT. Max 5 MB per file, up to 5 files.</div><?= $err('evidence') ?></div>
    <div class="col-12"><label class="form-label" for="additional_info">Additional information</label>
      <textarea class="form-control" id="additional_info" name="additional_info" rows="2" maxlength="1500"><?= old('additional_info') ?></textarea></div>
  </div>
  <div class="mt-4 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-send me-1"></i>Submit complaint</button>
    <a class="btn btn-outline-secondary" href="<?= e(base_url('user/dashboard.php')) ?>">Cancel</a></div>
</form></div></div>
<?php unset($_SESSION['old']); layout_end();
