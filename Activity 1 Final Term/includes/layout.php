<?php
declare(strict_types=1);

function layout_start(string $title, array $user): void
{
    $pageTitle = $title;
    include __DIR__ . '/header.php';
    ?>
<nav class="navbar bg-white border-bottom sticky-top px-3">
  <span class="navbar-brand mb-0 d-flex align-items-center gap-2"><?= brand_logo(38) ?><span class="d-flex flex-column lh-sm text-truncate"><span class="fw-bold text-primary"><?= e(school_short()) ?></span><small class="text-muted fw-normal d-none d-sm-block" style="font-size:.7rem"><?= e(app_name()) ?></small></span></span>
  <div class="d-flex align-items-center gap-2 gap-sm-3">
    <button type="button" class="btn btn-outline-secondary theme-toggle" data-theme-toggle aria-label="Toggle dark mode" title="Toggle dark mode"><i class="bi bi-moon-stars-fill when-light"></i><i class="bi bi-sun-fill when-dark"></i></button>
    <div class="dropdown">
      <button class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown" aria-label="Account menu"><i class="bi bi-person-circle me-sm-1"></i><span class="d-none d-sm-inline"><?= e($user['name']) ?></span></button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><span class="dropdown-item-text small text-muted text-capitalize"><?= e($user['role']) ?></span></li>
        <li><hr class="dropdown-divider"></li>
        <li><form method="post" action="<?= e(base_url('logout.php')) ?>"><?= csrf_field() ?>
          <button class="dropdown-item text-danger" type="submit" data-no-loading="1">Sign out</button></form></li>
      </ul>
    </div>
  </div>
</nav>
<main class="p-3 p-lg-4 main-content container" id="main">
    <?php include __DIR__ . '/alerts.php';
}

function layout_end(): void
{
    echo school_footer() . '</main>';
    include __DIR__ . '/footer.php';
}
