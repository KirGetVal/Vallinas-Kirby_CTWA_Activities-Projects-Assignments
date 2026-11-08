<?php
require_once __DIR__ . '/../core/bootstrap.php';
$u = Auth::requireRole(ROLE_ADMIN);
require_post();
$days = filter_var($_POST['overdue_days'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 90]]);
$org = Validator::clean($_POST['org_name'] ?? '');
if ($days === false) { flash('error', 'Overdue threshold must be a whole number from 1 to 90 days.'); redirect('admin/settings.php'); }
if (str_len($org) < 2 || str_len($org) > 80) { flash('error', 'Organisation name must be between 2 and 80 characters.'); redirect('admin/settings.php'); }
Storage::mutate('settings', fn(array $s) => array_merge($s, ['overdue_days' => $days, 'org_name' => $org]));
log_activity("Updated system settings (overdue after $days days)");
flash('success', 'Settings saved.');
redirect('admin/settings.php');
