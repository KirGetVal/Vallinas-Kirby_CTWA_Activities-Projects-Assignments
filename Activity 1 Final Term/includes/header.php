<?php
// Expects: $pageTitle (string). Optional: $bodyClass.
$pageTitle = $pageTitle ?? app_name();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#14532D">
<script>
/* Apply the saved theme before first paint to avoid a light flash. */
(function () {
  var t = 'light';
  try { t = localStorage.getItem('cms-theme') === 'dark' ? 'dark' : 'light'; } catch (e) {}
  document.documentElement.setAttribute('data-bs-theme', t);
  var m = document.querySelector('meta[name="theme-color"]');
  if (m && t === 'dark') { m.setAttribute('content', '#0B1F14'); }
})();
</script>
<title><?= e($pageTitle) ?> | <?= e(school_short()) ?> <?= e(app_name()) ?></title>
<!-- Local-first assets: see README for downloading Bootstrap/Icons into assets/vendor/ for offline use. -->
<link rel="stylesheet" href="<?= e(base_url('assets/vendor/bootstrap/bootstrap.min.css')) ?>"
      onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'">
<link rel="stylesheet" href="<?= e(base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>"
      onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css'">
<?php if (is_file(APP_ROOT . '/assets/images/logo.png')): ?><link rel="icon" type="image/png" href="<?= e(base_url('assets/images/logo.png')) ?>">
<?php else: ?><link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 16 16%27%3E%3Crect width=%2716%27 height=%2716%27 rx=%274%27 fill=%27%2314532D%27/%3E%3Cpath d=%27M4 8.5l2.5 2.5L12 5.5%27 stroke=%27%23fff%27 stroke-width=%271.6%27 fill=%27none%27/%3E%3C/svg%3E">
<?php endif; ?>
<link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>">
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<a class="skip-link" href="#main">Skip to main content</a>
<?php if (str_contains((string)($bodyClass ?? ''), 'auth')): ?>
<button type="button" class="btn btn-outline-secondary theme-toggle theme-toggle-fixed" data-theme-toggle aria-label="Toggle dark mode" title="Toggle dark mode"><i class="bi bi-moon-stars-fill when-light"></i><i class="bi bi-sun-fill when-dark"></i></button>
<?php endif; ?>
