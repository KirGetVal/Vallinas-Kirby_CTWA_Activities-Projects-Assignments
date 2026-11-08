<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
$u = Auth::requireRole(ROLE_STAFF);
// Handling history: every note and status change this staff member has made, newest first.
$mine = (int)$u['id'];
$events = [];
foreach (Storage::read('notes') as $n) {
    if ((int)$n['author_id'] === $mine) {
        $events[] = ['at' => $n['created_at'], 'cid' => (int)$n['complaint_id'], 'type' => 'Note (' . $n['visibility'] . ')', 'detail' => $n['body']];
    }
}
foreach (Storage::read('status_history') as $h) {
    if ((int)$h['updated_by'] === $mine) {
        $events[] = ['at' => $h['created_at'], 'cid' => (int)$h['complaint_id'], 'type' => 'Status change',
            'detail' => (STATUS_LABELS[$h['previous_status']] ?? 'None') . ' → ' . STATUS_LABELS[$h['new_status']] . ($h['remarks'] ? ' — ' . $h['remarks'] : '')];
    }
}
usort($events, fn($a, $b) => strcmp($b['at'], $a['at']));
$p = paginate($events, (int)($_GET['page'] ?? 1), 15);
layout_start('Investigation Log', $u); ?>
<h1 class="h4 mb-1">Investigation Log</h1><p class="text-muted">Your complaint handling history.</p>
<div class="card"><div class="card-body p-0"><?php if ($p['items']): ?>
  <div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>When</th><th>Complaint</th><th>Type</th><th>Detail</th></tr></thead><tbody>
  <?php foreach ($p['items'] as $ev): $c = Storage::find('complaints', $ev['cid']); $ok = $c && Complaint::canView($c, $u); ?>
    <tr><td class="text-nowrap small"><?= e(fmt_date($ev['at'])) ?></td>
      <td><?= $ok ? '<a href="' . e(complaint_link($c, $u)) . '">' . e($c['reference']) . '</a>' : e($c['reference'] ?? '-') ?></td>
      <td><?= e($ev['type']) ?></td><td class="small"><?= e(str_cut($ev['detail'], 160)) ?></td></tr>
  <?php endforeach; ?></tbody></table></div>
<?php else: ?><div class="text-center text-muted py-5"><i class="bi bi-journal-text fs-1 d-block mb-2"></i>No activity yet. Notes and status changes you make will appear here.</div><?php endif; ?></div>
  <?php if ($p['pages'] > 1): ?><div class="card-footer bg-white"><?php render_pagination($p); ?></div><?php endif; ?></div>
<?php layout_end();
