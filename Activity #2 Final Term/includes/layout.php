<?php
declare(strict_types=1);

function nav_items(string $role): array
{
    return match ($role) {
        ROLE_ADMIN => [
            ['admin/dashboard.php', 'speedometer2', 'Dashboard'],
            ['admin/complaints.php', 'folder2-open', 'All Complaints'],
            ['admin/assign_complaint.php', 'person-plus', 'Assign Complaints'],
            ['admin/messages.php', 'chat-dots', 'Messages'],
            ['admin/users.php', 'people', 'Users'],
            ['admin/staff.php', 'person-badge', 'Staff'],
            ['admin/categories.php', 'tags', 'Categories'],
            ['admin/reports.php', 'bar-chart-line', 'Reports'],
            ['admin/activity_logs.php', 'clock-history', 'Activity Logs'],
            ['admin/settings.php', 'gear', 'Settings'],
            ['admin/notifications.php', 'bell', 'Notifications'],
            ['admin/profile.php', 'person-circle', 'My Profile'],
        ],
        ROLE_STAFF => [
            ['staff/dashboard.php', 'speedometer2', 'Dashboard'],
            ['staff/assigned_complaints.php', 'folder2-open', 'Assigned Complaints'],
            ['staff/investigation.php', 'journal-text', 'Investigation Log'],
            ['staff/messages.php', 'chat-dots', 'Messages'],
            ['staff/notifications.php', 'bell', 'Notifications'],
            ['staff/profile.php', 'person-circle', 'My Profile'],
        ],
        default => [
            ['user/dashboard.php', 'speedometer2', 'Dashboard'],
            ['user/submit_complaint.php', 'plus-circle', 'Submit Complaint'],
            ['user/my_complaints.php', 'list-check', 'My Complaints'],
            ['user/messages.php', 'chat-dots', 'Messages'],
            ['user/notifications.php', 'bell', 'Notifications'],
            ['user/profile.php', 'person-circle', 'My Profile'],
        ],
    };
}

function layout_start(string $title, array $user): void
{
    $pageTitle = $title;
    include __DIR__ . '/header.php';
    $self = ltrim(str_replace(base_url(), '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), '/');
    $unread = Notification::unreadCount((int)$user['id']);
    $notifUrl = base_url($user['role'] . '/notifications.php');
    ?>
<nav class="navbar bg-white border-bottom sticky-top px-3">
  <div class="d-flex align-items-center gap-2">
    <button class="btn btn-outline-secondary d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-label="Menu"><i class="bi bi-list"></i></button>
    <span class="navbar-brand fw-bold text-primary mb-0"><i class="bi bi-clipboard2-check me-1"></i><?= e(app_name()) ?></span>
  </div>
  <div class="d-flex align-items-center gap-3">
    <a href="<?= e($notifUrl) ?>" class="position-relative text-secondary fs-5" aria-label="Notifications">
      <i class="bi bi-bell"></i>
      <?php if ($unread): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $unread ?></span><?php endif; ?>
    </a>
    <div class="dropdown">
      <button class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-person-circle me-1"></i><?= e($user['name']) ?></button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><span class="dropdown-item-text small text-muted text-capitalize"><?= e($user['role']) ?></span></li>
        <li><a class="dropdown-item" href="<?= e(base_url($user['role'] . '/profile.php')) ?>">My Profile</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><form method="post" action="<?= e(base_url('logout.php')) ?>"><?= csrf_field() ?>
          <button class="dropdown-item text-danger" type="submit" data-no-loading="1">Sign out</button></form></li>
      </ul>
    </div>
  </div>
</nav>
<div class="d-flex">
  <aside class="offcanvas-lg offcanvas-start sidebar" id="sidebar" tabindex="-1">
    <div class="offcanvas-header d-lg-none"><h5 class="offcanvas-title">Menu</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
    <div class="offcanvas-body flex-column p-3">
      <ul class="nav nav-pills flex-column gap-1 w-100">
        <?php foreach (nav_items($user['role']) as [$href, $icon, $label]): ?>
          <li class="nav-item"><a class="nav-link <?= $self === $href ? 'active' : '' ?>" href="<?= e(base_url($href)) ?>">
            <i class="bi bi-<?= $icon ?> me-2"></i><?= e($label) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </aside>
  <main class="flex-grow-1 p-3 p-lg-4 main-content">
    <?php include __DIR__ . '/alerts.php';
}

function layout_end(): void
{
    echo '</main></div>';
    include __DIR__ . '/footer.php';
}
