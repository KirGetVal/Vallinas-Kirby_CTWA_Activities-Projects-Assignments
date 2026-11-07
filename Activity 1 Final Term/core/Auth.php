<?php
declare(strict_types=1);

final class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']),
        ]);
        session_start();
        if (isset($_SESSION['last_active']) && time() - $_SESSION['last_active'] > SESSION_LIFETIME) {
            $_SESSION = [];
            session_regenerate_id(true);
            flash('warning', 'Your session expired. Please sign in again.');
        }
        $_SESSION['last_active'] = time();
    }

    /** Current user, re-read from the database so deactivation/role changes take effect immediately. */
    public static function user(): ?array
    {
        $id = (int)($_SESSION['user_id'] ?? 0);
        if ($id === 0) {
            return null;
        }
        $u = Database::findUser($id);
        if (!$u || !$u['active']) {
            unset($_SESSION['user_id']);
            return null;
        }
        return $u;
    }

    public static function homeFor(string $role): string
    {
        return match ($role) {
            ROLE_ADMIN => 'admin/dashboard.php',
            ROLE_STAFF => 'staff/dashboard.php',
            default => 'user/dashboard.php',
        };
    }

    public static function require(): array
    {
        $u = self::user();
        if (!$u) {
            flash('warning', 'Please sign in to continue.');
            redirect('login.php');
        }
        return $u;
    }

    /** Enforce role on the server. Call at the top of every protected page/action. */
    public static function requireRole(string ...$roles): array
    {
        $u = self::require();
        if (!in_array($u['role'], $roles, true)) {
            http_error(403, 'Your account does not have permission to open this page.');
        }
        return $u;
    }

    /** @return array{0:bool,1:array|string} */
    public static function attempt(string $email, string $password): array
    {
        $email = strtolower(trim($email));
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        $key = hash('sha256', $ip . '|' . $email);

        $rec = Database::row('SELECT fail_count, locked_until FROM login_attempts WHERE attempt_key = ?', [$key]);
        if ($rec && (int)$rec['locked_until'] > time()) {
            return [false, 'Too many failed attempts. Try again in a few minutes.'];
        }

        $user = Database::findUserByEmail($email);
        // Always verify a hash so timing doesn't reveal whether the email exists.
        $hash = $user['password'] ?? password_hash('dummy', PASSWORD_DEFAULT);
        $ok = password_verify($password, $hash) && $user !== null;

        if (!$ok) {
            $count = (int)($rec['fail_count'] ?? 0) + 1;
            $until = 0;
            if ($count >= MAX_LOGIN_ATTEMPTS) {
                $count = 0;
                $until = time() + LOGIN_LOCK_SECONDS;
            }
            Database::run(
                'INSERT INTO login_attempts (attempt_key, fail_count, locked_until) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE fail_count = VALUES(fail_count), locked_until = VALUES(locked_until)',
                [$key, $count, $until]
            );
            return [false, 'Invalid email or password.'];
        }
        if (!$user['active']) {
            return [false, 'This account has been deactivated. Contact an administrator.'];
        }

        Database::run('DELETE FROM login_attempts WHERE attempt_key = ?', [$key]);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        log_activity('Logged in', (int)$user['id']);
        return [true, $user];
    }

    public static function logout(): void
    {
        if (self::user()) {
            log_activity('Logged out');
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
