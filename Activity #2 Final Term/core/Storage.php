<?php
declare(strict_types=1);

/**
 * JSON file storage with exclusive locking for writes.
 * All mutations go through mutate(): lock -> read -> modify -> write -> unlock,
 * which prevents lost updates between concurrent requests.
 */
final class Storage
{
    public static function path(string $name): string
    {
        if (!array_key_exists($name, STORAGE_FILES)) {
            throw new InvalidArgumentException("Unknown collection: $name");
        }
        return STORAGE_PATH . '/' . $name . '.json';
    }

    public static function init(): void
    {
        if (!is_dir(STORAGE_PATH) && !@mkdir(STORAGE_PATH, 0775, true)) {
            throw new RuntimeException('Cannot create storage directory.');
        }
        foreach (STORAGE_FILES as $name => $default) {
            $p = self::path($name);
            if (!is_file($p)) {
                file_put_contents($p, json_encode($default, JSON_PRETTY_PRINT), LOCK_EX);
            }
        }
        self::seed();
    }

    public static function read(string $name): array
    {
        $p = self::path($name);
        $default = STORAGE_FILES[$name];
        if (!is_file($p)) {
            return $default;
        }
        $fp = @fopen($p, 'rb');
        if (!$fp) {
            error_log("Storage: cannot open $p");
            return $default;
        }
        flock($fp, LOCK_SH);
        $raw = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        $data = json_decode((string)$raw, true);
        if (!is_array($data)) {
            error_log("Storage: corrupted JSON in $name.json, using defaults");
            return $default;
        }
        return $data;
    }

    /** Atomically modify a collection. $fn receives the data and returns the new data. */
    public static function mutate(string $name, callable $fn): array
    {
        $p = self::path($name);
        $fp = fopen($p, 'c+');
        if (!$fp) {
            throw new RuntimeException("Cannot open storage file: $name");
        }
        try {
            if (!flock($fp, LOCK_EX)) {
                throw new RuntimeException("Cannot lock storage file: $name");
            }
            $raw = stream_get_contents($fp);
            $data = json_decode((string)$raw, true);
            if (!is_array($data)) {
                $data = STORAGE_FILES[$name];
            }
            $new = $fn($data);
            $json = json_encode($new, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                throw new RuntimeException("Cannot encode data for $name");
            }
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, $json);
            fflush($fp);
            flock($fp, LOCK_UN);
            return $new;
        } finally {
            fclose($fp);
        }
    }

    // ---- list helpers (collections whose records have an integer 'id') ----

    public static function all(string $name): array
    {
        return array_values(self::read($name));
    }

    public static function find(string $name, int $id): ?array
    {
        foreach (self::read($name) as $r) {
            if ((int)($r['id'] ?? 0) === $id) {
                return $r;
            }
        }
        return null;
    }

    public static function where(string $name, callable $pred): array
    {
        return array_values(array_filter(self::read($name), $pred));
    }

    public static function insert(string $name, array $record): array
    {
        $out = $record;
        self::mutate($name, function (array $rows) use (&$out, $record) {
            $max = 0;
            foreach ($rows as $r) {
                $max = max($max, (int)($r['id'] ?? 0));
            }
            $out = ['id' => $max + 1] + $record;
            $rows[] = $out;
            return $rows;
        });
        return $out;
    }

    public static function update(string $name, int $id, array $changes): ?array
    {
        $updated = null;
        self::mutate($name, function (array $rows) use ($id, $changes, &$updated) {
            foreach ($rows as $i => $r) {
                if ((int)($r['id'] ?? 0) === $id) {
                    $rows[$i] = $updated = array_merge($r, $changes);
                }
            }
            return $rows;
        });
        return $updated;
    }

    public static function delete(string $name, int $id): void
    {
        self::mutate($name, fn(array $rows) => array_values(array_filter(
            $rows, fn($r) => (int)($r['id'] ?? 0) !== $id
        )));
    }

    // ---- first-run seeding ----

    private static function seed(): void
    {
        if (!SEED_DEMO_DATA) {
            return;
        }
        if (self::read('users') === []) {
            $now = date('c');
            $mk = fn(string $n, string $e, string $p, string $r) => [
                'name' => $n, 'email' => $e, 'password' => password_hash($p, PASSWORD_DEFAULT),
                'role' => $r, 'phone' => '', 'active' => true, 'created_at' => $now,
            ];
            foreach ([
                $mk('System Administrator', 'admin@cms.local', 'Admin123!', ROLE_ADMIN),
                $mk('Sample Staff', 'staff@cms.local', 'Staff123!', ROLE_STAFF),
                $mk('Sample User', 'user@cms.local', 'User123!', ROLE_USER),
            ] as $u) {
                self::insert('users', $u);
            }
        }
        if (self::read('categories') === []) {
            foreach (DEFAULT_CATEGORIES as $c) {
                self::insert('categories', ['name' => $c, 'active' => true, 'created_at' => date('c')]);
            }
        }
    }
}
