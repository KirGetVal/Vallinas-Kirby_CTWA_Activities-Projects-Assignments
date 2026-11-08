<?php
declare(strict_types=1);

/** Organisation name from Settings (falls back to the built-in default). */
function app_name(): string
{
    $n = trim((string)(Storage::read('settings')['org_name'] ?? ''));
    return $n !== '' ? $n : APP_NAME;
}

function category_name(?int $id): string
{
    $c = $id ? Storage::find('categories', $id) : null;
    return $c['name'] ?? 'Uncategorized';
}

function user_role_of(int $id): string
{
    $u = Storage::find('users', $id);
    return $u['role'] ?? ROLE_USER;
}

function user_name(?int $id): string
{
    $u = $id ? Storage::find('users', $id) : null;
    return $u['name'] ?? '-';
}

function priority_badge(string $p): string
{
    $m = ['low' => 'secondary', 'medium' => 'info', 'high' => 'warning', 'urgent' => 'danger'];
    return '<span class="badge text-bg-' . ($m[$p] ?? 'secondary') . '">' . e(PRIORITIES[$p] ?? $p) . '</span>';
}

function is_overdue(array $c): bool
{
    if (in_array($c['status'], [STATUS_RESOLVED, STATUS_CLOSED, STATUS_REJECTED], true)) { return false; }
    $days = (int)(Storage::read('settings')['overdue_days'] ?? 7);
    return strtotime($c['created_at']) < strtotime("-$days days");
}

function count_by_status(array $complaints): array
{
    $out = array_fill_keys(array_keys(STATUS_LABELS), 0);
    foreach ($complaints as $c) { $out[$c['status']] = ($out[$c['status']] ?? 0) + 1; }
    return $out;
}

/** Messages in complaints visible to $u that were sent by others and not yet read by $u. */
function unread_messages_by_complaint(array $u): array
{
    $visible = array_column(Complaint::visibleTo($u), 'id');
    $out = [];
    foreach (Storage::read('messages') as $m) {
        if (in_array($m['complaint_id'], $visible, true) && (int)$m['sender_id'] !== (int)$u['id']
            && !in_array((int)$u['id'], $m['read_by'] ?? [], true)) {
            $out[$m['complaint_id']] = ($out[$m['complaint_id']] ?? 0) + 1;
        }
    }
    return $out;
}

function mark_messages_read(int $complaintId, int $userId): void
{
    Storage::mutate('messages', function (array $rows) use ($complaintId, $userId) {
        foreach ($rows as $i => $m) {
            if ((int)$m['complaint_id'] === $complaintId && !in_array($userId, $m['read_by'] ?? [], true)) {
                $rows[$i]['read_by'][] = $userId;
            }
        }
        return $rows;
    });
}

function render_pagination(array $p, array $query = []): void
{
    if ($p['pages'] <= 1) { return; }
    echo '<nav><ul class="pagination pagination-sm justify-content-center mb-0">';
    for ($i = 1; $i <= $p['pages']; $i++) {
        $q = http_build_query(array_merge($query, ['page' => $i]));
        echo '<li class="page-item' . ($i === $p['page'] ? ' active' : '') . '"><a class="page-link" href="?' . e($q) . '">' . $i . '</a></li>';
    }
    echo '</ul></nav>';
}

/** Role-aware complaint detail URL. */
function complaint_link(array $c, array $u): ?string
{
    return match ($u['role']) {
        ROLE_USER => base_url('user/complaint_details.php?id=' . (int)$c['id']),
        ROLE_STAFF => base_url('staff/complaint_details.php?id=' . (int)$c['id']),
        default => base_url('admin/complaint_details.php?id=' . (int)$c['id']),
    };
}

function due_date(array $c): int
{
    $days = (int)(Storage::read('settings')['overdue_days'] ?? 7);
    return strtotime($c['created_at'] . " +$days days");
}

function is_open(array $c): bool
{
    return !in_array($c['status'], [STATUS_RESOLVED, STATUS_CLOSED, STATUS_REJECTED], true);
}

function render_complaint_table(array $rows, array $u, bool $showOwner = false): void
{
    if (!$rows) {
        echo '<div class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>No complaints to show.</div>';
        return;
    }
    echo '<div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr>'
       . '<th>Reference</th><th>Title</th><th>Category</th>' . ($showOwner ? '<th>Complainant</th>' : '')
       . '<th>Status</th><th>Priority</th><th>Submitted</th></tr></thead><tbody>';
    foreach ($rows as $c) {
        $link = complaint_link($c, $u);
        $ref = $link ? '<a class="fw-semibold" href="' . e($link) . '">' . e($c['reference']) . '</a>' : '<span class="fw-semibold">' . e($c['reference']) . '</span>';
        echo '<tr><td>' . $ref . '</td><td>' . e($c['title']) . '</td><td>' . e(category_name((int)$c['category_id'])) . '</td>'
           . ($showOwner ? '<td>' . e(user_name((int)$c['user_id'])) . '</td>' : '')
           . '<td>' . status_badge($c['status']) . (is_overdue($c) ? ' <span class="badge text-bg-danger">Overdue</span>' : '') . '</td>'
           . '<td>' . priority_badge($c['priority']) . '</td><td class="text-nowrap">' . e(fmt_date($c['created_at'], false)) . '</td></tr>';
    }
    echo '</tbody></table></div>';
}

function stat_card(string $label, int|string $value, string $icon, string $color = 'primary'): void
{
    echo '<div class="col-6 col-lg-3 col-xl-2"><div class="card h-100"><div class="card-body d-flex align-items-center gap-3">'
       . '<div class="stat-icon text-bg-' . e($color) . '"><i class="bi bi-' . e($icon) . '"></i></div>'
       . '<div><div class="fs-4 fw-bold lh-1">' . e((string)$value) . '</div><div class="text-muted small">' . e($label) . '</div></div>'
       . '</div></div></div>';
}

/** Table of staff workload rows produced by Report::build()['staff']. */
function render_staff_workload(array $staff): void
{
    if (!$staff) { echo '<p class="text-muted text-center my-4">No complaints have been assigned yet.</p>'; return; }
    echo '<div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Staff</th><th class="text-center">Assigned</th>'
       . '<th class="text-center">Open</th><th class="text-center">Resolved</th><th class="text-center">Overdue</th><th class="text-end">Avg. resolution</th></tr></thead><tbody>';
    foreach ($staff as $s) {
        echo '<tr><td>' . e($s['name']) . '</td><td class="text-center">' . (int)$s['assigned'] . '</td><td class="text-center">' . (int)$s['open']
           . '</td><td class="text-center">' . (int)$s['resolved'] . '</td><td class="text-center">'
           . ($s['overdue'] ? '<span class="badge text-bg-danger">' . (int)$s['overdue'] . '</span>' : '0')
           . '</td><td class="text-end">' . ($s['avg_days'] === null ? '-' : e(number_format($s['avg_days'], 1)) . ' days') . '</td></tr>';
    }
    echo '</tbody></table></div>';
}
