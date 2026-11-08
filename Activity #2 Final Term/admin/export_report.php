<?php
require_once __DIR__ . '/../core/bootstrap.php';
$u = Auth::requireRole(ROLE_ADMIN);
$f = Report::filters($_GET);
$rows = Report::complaints($f);
log_activity('Exported complaints report to CSV (' . count($rows) . ' rows)');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="complaints-report-' . date('Ymd-His') . '.csv"');
header('X-Content-Type-Options: nosniff');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");   // UTF-8 BOM so Excel reads accents correctly
fputcsv($out, ['Reference', 'Title', 'Category', 'Complainant', 'Status', 'Priority', 'Assigned To', 'Submitted', 'Resolved At', 'Resolution Days', 'Overdue']);
foreach ($rows as $c) {
    $days = !empty($c['resolved_at']) ? round((strtotime($c['resolved_at']) - strtotime($c['created_at'])) / 86400, 1) : '';
    fputcsv($out, array_map([Report::class, 'csvCell'], [
        $c['reference'], $c['title'], category_name((int)$c['category_id']), user_name((int)$c['user_id']),
        STATUS_LABELS[$c['status']] ?? $c['status'], PRIORITIES[$c['priority']] ?? $c['priority'],
        $c['assigned_to'] ? user_name((int)$c['assigned_to']) : '', $c['created_at'], $c['resolved_at'] ?? '', $days, is_overdue($c) ? 'Yes' : 'No',
    ]));
}
fclose($out);
