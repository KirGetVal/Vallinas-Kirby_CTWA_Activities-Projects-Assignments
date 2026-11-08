<?php
// Expects: $pageTitle (string). Optional: $bodyClass.
$pageTitle = $pageTitle ?? app_name();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> | <?= e(app_name()) ?></title>
<!-- Local-first assets: see README for downloading Bootstrap/Icons into assets/vendor/ for offline use. -->
<link rel="stylesheet" href="<?= e(base_url('assets/vendor/bootstrap/bootstrap.min.css')) ?>"
      onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'">
<link rel="stylesheet" href="<?= e(base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>"
      onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css'">
<link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>">
</head>
<body class="<?= e($bodyClass ?? '') ?>">
