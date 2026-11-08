<?php
require_once __DIR__ . '/../core/bootstrap.php';
$u = Auth::require();
require_post();
Notification::markAllRead((int)$u['id']);
flash('success', 'All notifications marked as read.');
redirect($u['role'] . '/notifications.php');
