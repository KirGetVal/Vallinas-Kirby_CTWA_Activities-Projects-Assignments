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

/** Stop the request with a friendly error page (HTML) and the correct HTTP status. */
function http_error(int $code, string $detail = ''): never
{
    $titles = [400 => 'Bad request', 403 => 'Access denied', 404 => 'Page not found', 405 => 'Method not allowed'];
    $defaults = [400 => 'The request could not be understood.', 403 => 'You do not have permission to do that.',
                 404 => 'The page or record you asked for does not exist, or is not available to you.', 405 => 'That action cannot be done this way.'];
    http_response_code($code);
    $title = $titles[$code] ?? 'Error';
    $detail = $detail !== '' ? $detail : ($defaults[$code] ?? 'Something went wrong.');
    require __DIR__ . '/../includes/error_page.php';
    exit;
}

function csrf_verify(): void
{
    $t = $_POST['csrf_token'] ?? '';
    $s = $_SESSION['csrf'] ?? '';
    // Both must be non-empty: otherwise a missing token would equal a missing session token.
    if (!is_string($t) || $t === '' || $s === '' || !hash_equals($s, $t)) {
        // 403, not 419: Apache turns unknown status codes into a 500
        http_error(403, 'Your form has expired or is invalid. Go back, refresh the page, and try again.');
    }
}

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_error(405);
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
    try {
        Database::run(
            'INSERT INTO activity_logs (user_id, user_name, action, related_id, ip, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$u['id'] ?? null, $u ? $u['name'] . ' (' . $u['role'] . ')' : 'Guest', $action,
             $relatedId, substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45), date('Y-m-d H:i:s')]
        );
    } catch (PDOException $e) {
        error_log('log_activity failed: ' . $e->getMessage());   // never block sign-in because of a log write
    }
}
