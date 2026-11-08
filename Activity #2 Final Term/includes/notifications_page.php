<?php
// Shared notifications view. Expects $u (authenticated user).
$list = Notification::forUser((int)$u['id']);
$p = paginate($list, (int)($_GET['page'] ?? 1), 15);
layout_start('Notifications', $u); ?>
<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h4 mb-0">Notifications</h1>
  <?php if (Notification::unreadCount((int)$u['id'])): ?>
  <form method="post" action="<?= e(base_url('actions/notification_process.php')) ?>"><?= csrf_field() ?>
    <input type="hidden" name="back" value="<?= e($u['role']) ?>"><button class="btn btn-outline-primary btn-sm">Mark all as read</button></form><?php endif; ?></div>
<div class="card"><ul class="list-group list-group-flush">
<?php foreach ($p['items'] as $n): ?>
  <li class="list-group-item <?= $n['read'] ? '' : 'notif-unread' ?>">
    <?php if ($n['link'] && str_starts_with($n['link'], $u['role'] . '/')): ?><a href="<?= e(base_url($n['link'])) ?>" class="text-decoration-none"><?= e($n['message']) ?></a><?php else: echo e($n['message']); endif; ?>
    <div class="text-muted small"><?= e(fmt_date($n['created_at'])) ?></div></li>
<?php endforeach; if (!$p['items']): ?><li class="list-group-item text-center text-muted py-5"><i class="bi bi-bell-slash fs-1 d-block mb-2"></i>You're all caught up.</li><?php endif; ?>
</ul><?php if ($p['pages'] > 1): ?><div class="card-footer bg-white"><?php render_pagination($p); ?></div><?php endif; ?></div>
<?php layout_end();
