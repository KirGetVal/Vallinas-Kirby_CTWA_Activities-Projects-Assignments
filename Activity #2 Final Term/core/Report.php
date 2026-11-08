<?php
declare(strict_types=1);

/** Read-only analytics over complaints.json (+ feedback/users). Used by dashboard, reports and CSV export. */
final class Report
{
    /** Validate raw query input into safe filters. */
    public static function filters(array $q): array
    {
        $date = function (string $k) use ($q): string {
            $v = trim((string)($q[$k] ?? ''));
            $d = DateTime::createFromFormat('Y-m-d', $v);
            return ($d && $d->format('Y-m-d') === $v) ? $v : '';
        };
        $st = (string)($q['status'] ?? '');
        return [
            'from' => $date('from'), 'to' => $date('to'),
            'category_id' => (int)($q['category_id'] ?? 0),
            'status' => isset(STATUS_LABELS[$st]) ? $st : '',
        ];
    }

    public static function complaints(array $f): array
    {
        $rows = Storage::where('complaints', function (array $c) use ($f) {
            $day = substr($c['created_at'], 0, 10);
            return ($f['from'] === '' || $day >= $f['from'])
                && ($f['to'] === '' || $day <= $f['to'])
                && (!$f['category_id'] || (int)$c['category_id'] === $f['category_id'])
                && ($f['status'] === '' || $c['status'] === $f['status']);
        });
        usort($rows, fn($a, $b) => strcmp($b['created_at'], $a['created_at']) ?: ($b['id'] <=> $a['id']));
        return $rows;
    }

    private static function days(array $c): ?float
    {
        if (empty($c['resolved_at'])) { return null; }
        $d = (strtotime($c['resolved_at']) - strtotime($c['created_at'])) / 86400;
        return $d >= 0 ? $d : null;
    }

    public static function build(array $rows): array
    {
        $byStatus = count_by_status($rows);
        $byCat = [];
        $monthly = [];
        $times = [];
        $staff = [];
        foreach ($rows as $c) {
            $cat = category_name((int)$c['category_id']);
            $byCat[$cat] = ($byCat[$cat] ?? 0) + 1;
            $m = substr($c['created_at'], 0, 7);
            $monthly[$m] = ($monthly[$m] ?? 0) + 1;
            $d = self::days($c);
            if ($d !== null) { $times[] = $d; }
            if ($c['assigned_to']) {
                $sid = (int)$c['assigned_to'];
                $staff[$sid] ??= ['id' => $sid, 'name' => user_name($sid), 'assigned' => 0, 'open' => 0, 'resolved' => 0, 'overdue' => 0, 'times' => []];
                $staff[$sid]['assigned']++;
                if (in_array($c['status'], [STATUS_RESOLVED, STATUS_CLOSED], true)) { $staff[$sid]['resolved']++; }
                if (is_open($c)) { $staff[$sid]['open']++; }
                if (is_overdue($c)) { $staff[$sid]['overdue']++; }
                if ($d !== null) { $staff[$sid]['times'][] = $d; }
            }
        }
        // fill gaps between the first and last month (capped) so the trend chart is continuous
        ksort($monthly);
        if ($monthly) {
            $filled = []; $k = array_key_first($monthly); $last = array_key_last($monthly);
            for ($i = 0; $i < 60 && $k <= $last; $i++) {
                $filled[$k] = $monthly[$k] ?? 0;
                $k = date('Y-m', strtotime($k . '-01 +1 month'));
            }
            $monthly = $filled;
        }
        arsort($byCat);
        foreach ($staff as &$s) {
            $s['avg_days'] = $s['times'] ? array_sum($s['times']) / count($s['times']) : null;
            unset($s['times']);
        }
        unset($s);
        usort($staff, fn($a, $b) => $b['open'] <=> $a['open'] ?: strcmp($a['name'], $b['name']));

        $ids = array_column($rows, 'id');
        $ratings = array_column(array_filter(Storage::read('feedback'), fn($f) => in_array($f['complaint_id'], $ids, true)), 'rating');

        return [
            'total' => count($rows),
            'by_status' => $byStatus,
            'by_category' => $byCat,
            'monthly' => $monthly,
            'resolved' => $byStatus[STATUS_RESOLVED] + $byStatus[STATUS_CLOSED],
            'unresolved' => count(array_filter($rows, fn($c) => is_open($c))),
            'rejected' => $byStatus[STATUS_REJECTED],
            'overdue' => count(array_filter($rows, 'is_overdue')),
            'avg_days' => $times ? array_sum($times) / count($times) : null,
            'staff' => $staff,
            'avg_rating' => $ratings ? array_sum($ratings) / count($ratings) : null,
            'rating_count' => count($ratings),
        ];
    }

    /** Neutralise spreadsheet formula injection in CSV cells. */
    public static function csvCell(mixed $v): string
    {
        $s = (string)$v;
        return ($s !== '' && strpbrk($s[0], "=+-@\t\r") !== false) ? "'" . $s : $s;
    }
}
