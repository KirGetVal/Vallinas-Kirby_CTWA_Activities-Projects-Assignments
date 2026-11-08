<?php
declare(strict_types=1);

function e(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    // Only internal paths are allowed (prevents open redirects).
    if (preg_match('#^[a-z][a-z0-9+.-]*:|^//#i', $path)) {
        $path = 'index.php';
    }
    header('Location: ' . base_url($path));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $t = $_POST['csrf_token'] ?? '';
    $s = $_SESSION['csrf'] ?? '';
    // Both must be non-empty: otherwise a missing token would equal a missing session token.
    if (!is_string($t) || $t === '' || $s === '' || !hash_equals($s, $t)) {
        http_response_code(419);
        exit('Invalid or expired form token. Go back, refresh the page, and try again.');
    }
}

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        exit('Method not allowed.');
    }
    csrf_verify();
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function flash_pull(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function old(string $key, string $default = ''): string
{
    return e($_SESSION['old'][$key] ?? $default);
}

/** Character length that works with or without the mbstring extension. */
function str_len(string $s): int
{
    return function_exists('mb_strlen') ? mb_strlen($s) : (int)preg_match_all('/./su', $s);
}

/** Lower-case that works with or without mbstring. */
function str_lower(string $s): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($s) : strtolower($s);
}

/** UTF-8 safe truncate that works without mbstring. */
function str_cut(string $s, int $n): string
{
    return preg_match('/^.{0,' . $n . '}/su', $s, $m) ? $m[0] . (str_len($s) > $n ? '…' : '') : '';
}

function now(): string
{
    return date('c');
}

function fmt_date(?string $iso, bool $time = true): string
{
    if (!$iso) {
        return '-';
    }
    $t = strtotime($iso);
    return $t ? date($time ? 'M j, Y g:i A' : 'M j, Y', $t) : '-';
}

function log_activity(string $action, ?int $relatedId = null): void
{
    $u = Auth::user();
    Storage::insert('activity_logs', [
        'user_id' => $u['id'] ?? null,
        'user_name' => $u ? $u['name'] . ' (' . $u['role'] . ')' : 'Guest',
        'action' => $action,
        'related_id' => $relatedId,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'created_at' => now(),
    ]);
}

function status_badge(string $status): string
{
    $map = [
        STATUS_SUBMITTED => 'secondary', STATUS_REVIEW => 'info', STATUS_ASSIGNED => 'primary',
        STATUS_PROGRESS => 'warning', STATUS_AWAITING => 'warning', STATUS_RESOLVED => 'success',
        STATUS_CLOSED => 'dark', STATUS_REJECTED => 'danger', STATUS_REOPENED => 'info',
    ];
    return '<span class="badge text-bg-' . ($map[$status] ?? 'secondary') . '">'
        . e(STATUS_LABELS[$status] ?? $status) . '</span>';
}

function paginate(array $items, int $page, int $perPage = PAGE_SIZE): array
{
    $total = count($items);
    $pages = max(1, (int)ceil($total / $perPage));
    $page = min(max(1, $page), $pages);
    return ['items' => array_slice($items, ($page - 1) * $perPage, $perPage),
            'page' => $page, 'pages' => $pages, 'total' => $total];
}
