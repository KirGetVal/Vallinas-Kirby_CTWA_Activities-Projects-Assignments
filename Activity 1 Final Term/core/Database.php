<?php
declare(strict_types=1);

/** MySQL access through PDO (prepared statements only). */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
            try {
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                self::fatal('Cannot connect to the database. Start MySQL in XAMPP, import database.sql in phpMyAdmin, '
                    . 'and check config/database.php.', $e);
            }
        }
        return self::$pdo;
    }

    /** Plain error page (does not touch the session or database, so it can never loop). */
    public static function fatal(string $msg, ?Throwable $e = null): never
    {
        if ($e) { error_log('Database: ' . $e->getMessage()); }
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><meta charset="utf-8"><title>Database error</title>'
           . '<body style="font-family:sans-serif;max-width:560px;margin:15vh auto;padding:0 1rem">'
           . '<h1 style="font-size:1.4rem">Database error</h1><p>' . htmlspecialchars($msg) . '</p>'
           . (APP_DEBUG && $e ? '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>' : '') . '</body>';
        exit;
    }

    public static function init(): void
    {
        try {
            self::pdo()->query('SELECT 1 FROM users LIMIT 1');
        } catch (PDOException $e) {
            self::fatal('The database tables are missing. Import database.sql in phpMyAdmin first.', $e);
        }
        self::seed();
    }

    /** Run a query and return all rows. */
    public static function rows(string $sql, array $params = []): array
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /** Run a query and return the first row or null. */
    public static function row(string $sql, array $params = []): ?array
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        $r = $st->fetch();
        return $r === false ? null : $r;
    }

    /** Run INSERT/UPDATE/DELETE; returns affected rows. */
    public static function run(string $sql, array $params = []): int
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st->rowCount();
    }

    public static function findUser(int $id): ?array
    {
        $u = self::row('SELECT * FROM users WHERE id = ?', [$id]);
        return $u ? self::castUser($u) : null;
    }

    public static function findUserByEmail(string $email): ?array
    {
        $u = self::row('SELECT * FROM users WHERE email = ?', [strtolower(trim($email))]);
        return $u ? self::castUser($u) : null;
    }

    private static function castUser(array $u): array
    {
        $u['id'] = (int)$u['id'];
        $u['active'] = (bool)$u['active'];
        return $u;
    }

    public static function setting(string $key, string $default = ''): string
    {
        static $cache = null;
        if ($cache === null) {
            $cache = [];
            foreach (self::rows('SELECT setting_key, setting_value FROM settings') as $r) {
                $cache[$r['setting_key']] = $r['setting_value'];
            }
        }
        $v = trim((string)($cache[$key] ?? ''));
        return $v !== '' ? $v : $default;
    }

    /** First-run demo accounts (development only; turn off with SEED_DEMO_DATA = false). */
    private static function seed(): void
    {
        if (!SEED_DEMO_DATA) {
            return;
        }
        if ((int)self::row('SELECT COUNT(*) AS c FROM users')['c'] > 0) {
            return;
        }
        $ins = self::pdo()->prepare(
            'INSERT IGNORE INTO users (name, email, password, role, phone, active) VALUES (?, ?, ?, ?, \'\', 1)'
        );
        foreach ([
            ['System Administrator', 'admin@cms.local', 'Admin123!', ROLE_ADMIN],
            ['Sample Staff',         'staff@cms.local', 'Staff123!', ROLE_STAFF],
            ['Sample User',          'user@cms.local',  'User123!',  ROLE_USER],
        ] as [$n, $e, $p, $r]) {
            $ins->execute([$n, $e, password_hash($p, PASSWORD_DEFAULT), $r]);
        }
    }
}
