<?php
declare(strict_types=1);

final class Complaint
{
    /** Creates a complaint with a unique reference number (generated inside the write lock). */
    public static function create(array $data, int $userId): array
    {
        $created = [];
        Storage::mutate('complaints', function (array $rows) use ($data, $userId, &$created) {
            $year = date('Y');
            $max = 0; $maxId = 0;
            foreach ($rows as $r) {
                $maxId = max($maxId, (int)$r['id']);
                if (preg_match('/^CMP-' . $year . '-(\d+)$/', $r['reference'], $m)) {
                    $max = max($max, (int)$m[1]);
                }
            }
            $created = [
                'id' => $maxId + 1,
                'reference' => sprintf('CMP-%s-%05d', $year, $max + 1),
                'user_id' => $userId,
                'title' => $data['title'], 'category_id' => (int)$data['category_id'],
                'description' => $data['description'], 'location' => $data['location'],
                'incident_date' => $data['incident_date'], 'additional_info' => $data['additional_info'],
                'priority' => 'medium',         // never trusted from the user; admins adjust it
                'status' => STATUS_SUBMITTED, 'assigned_to' => null,
                'resolution' => null, 'rejection_reason' => null,
                'created_at' => now(), 'updated_at' => now(),
            ];
            $rows[] = $created;
            return $rows;
        });
        self::history((int)$created['id'], null, STATUS_SUBMITTED, $userId, 'Complaint submitted');
        return $created;
    }

    public static function history(int $cid, ?string $from, string $to, ?int $by, string $remarks): void
    {
        Storage::insert('status_history', [
            'complaint_id' => $cid, 'previous_status' => $from, 'new_status' => $to,
            'updated_by' => $by, 'remarks' => $remarks, 'created_at' => now(),
        ]);
    }

    public static function canView(array $c, array $u): bool
    {
        return match ($u['role']) {
            ROLE_ADMIN => true,
            ROLE_STAFF => (int)$c['assigned_to'] === (int)$u['id'],
            default => (int)$c['user_id'] === (int)$u['id'],
        };
    }

    /** Load a complaint only if $u may see it; otherwise null (callers answer 404 to avoid ID probing). */
    public static function findFor(int $id, array $u): ?array
    {
        $c = Storage::find('complaints', $id);
        return ($c && self::canView($c, $u)) ? $c : null;
    }

    public static function visibleTo(array $u): array
    {
        $rows = Storage::where('complaints', fn($c) => self::canView($c, $u));
        usort($rows, fn($a, $b) => strcmp($b['created_at'], $a['created_at']) ?: ($b['id'] <=> $a['id']));
        return $rows;
    }

    public static function allowedNext(array $c, array $actor): array
    {
        if (!self::canView($c, $actor)) { return []; }
        $out = [];
        foreach (STATUS_TRANSITIONS[$c['status']] ?? [] as $to => $roles) {
            if (in_array($actor['role'], $roles, true)) { $out[] = $to; }
        }
        return $out;
    }

    /**
     * The only place a status may change. Validates workflow + role + ownership,
     * records history, and notifies the other parties.
     * @return array{0:bool,1:string}
     */
    public static function changeStatus(int $id, string $to, array $actor, string $remarks = '', array $extra = []): array
    {
        $c = Storage::find('complaints', $id);
        if (!$c || !self::canView($c, $actor)) {
            return [false, 'Complaint not found.'];
        }
        if (!in_array($to, self::allowedNext($c, $actor), true)) {
            return [false, 'That status change is not allowed from "' . (STATUS_LABELS[$c['status']] ?? $c['status']) . '".'];
        }
        $from = $c['status'];
        Storage::update('complaints', $id, array_merge($extra, ['status' => $to, 'updated_at' => now()]));
        self::history($id, $from, $to, (int)$actor['id'], $remarks);
        log_activity("Status of {$c['reference']}: " . STATUS_LABELS[$from] . ' -> ' . STATUS_LABELS[$to], $id);

        $label = STATUS_LABELS[$to];
        if ((int)$actor['id'] !== (int)$c['user_id']) {
            $msg = match ($to) {
                STATUS_AWAITING => "More information is needed for {$c['reference']}.",
                STATUS_RESOLVED => "{$c['reference']} has been resolved.",
                STATUS_CLOSED => "{$c['reference']} is now closed. Please leave feedback.",
                default => "{$c['reference']} status updated to $label.",
            };
            Notification::send((int)$c['user_id'], $msg, 'user/complaint_details.php?id=' . $id, $id);
        }
        $assignee = (int)($extra['assigned_to'] ?? $c['assigned_to'] ?? 0);
        if ($assignee && $assignee !== (int)$actor['id'] && $actor['role'] === ROLE_USER) {
            Notification::send($assignee, "{$c['reference']} was updated by the complainant ($label).", 'staff/complaint_details.php?id=' . $id, $id);
        }
        if ($to === STATUS_RESOLVED && $actor['role'] !== ROLE_ADMIN) {
            Notification::toAdmins("{$c['reference']} was marked resolved by {$actor['name']} and awaits your approval.", $id);
        }
        return [true, "Status changed to $label."];
    }

    /**
     * Assign (or reassign) a complaint. A SUBMITTED complaint is moved through UNDER REVIEW first,
     * so history always reflects the real workflow. Admin only.
     * @return array{0:bool,1:string}
     */
    public static function assign(int $id, int $staffId, array $actor, string $remarks = ''): array
    {
        if ($actor['role'] !== ROLE_ADMIN) { return [false, 'Only administrators can assign complaints.']; }
        $c = Storage::find('complaints', $id);
        $staff = Storage::find('users', $staffId);
        if (!$c) { return [false, 'Complaint not found.']; }
        if (!$staff || $staff['role'] !== ROLE_STAFF || !$staff['active']) { return [false, 'Select an active staff member.']; }
        $remarks = $remarks !== '' ? $remarks : 'Assigned to ' . $staff['name'];
        $old = $c['assigned_to'] ? (int)$c['assigned_to'] : null;

        if (in_array($c['status'], [STATUS_ASSIGNED, STATUS_PROGRESS, STATUS_AWAITING], true)) {   // reassignment, status unchanged
            if ($old === $staffId) { return [false, $staff['name'] . ' is already assigned to this complaint.']; }
            Storage::update('complaints', $id, ['assigned_to' => $staffId, 'updated_at' => now()]);
            self::history($id, $c['status'], $c['status'], (int)$actor['id'], 'Reassigned to ' . $staff['name']);
            if ($old) { Notification::send($old, "{$c['reference']} was reassigned to another staff member.", null, $id); }
        } elseif (in_array($c['status'], [STATUS_SUBMITTED, STATUS_REVIEW, STATUS_REOPENED], true)) {
            if ($c['status'] === STATUS_SUBMITTED) {
                [$ok, $msg] = self::changeStatus($id, STATUS_REVIEW, $actor, 'Review started');
                if (!$ok) { return [false, $msg]; }
            }
            [$ok, $msg] = self::changeStatus($id, STATUS_ASSIGNED, $actor, $remarks, ['assigned_to' => $staffId]);
            if (!$ok) { return [false, $msg]; }
        } else {
            return [false, 'A ' . strtolower(STATUS_LABELS[$c['status']]) . ' complaint cannot be assigned.'];
        }
        Storage::insert('assignments', ['complaint_id' => $id, 'staff_id' => $staffId, 'assigned_by' => (int)$actor['id'],
            'previous_staff_id' => $old, 'created_at' => now()]);
        log_activity("Assigned {$c['reference']} to {$staff['name']}", $id);
        Notification::send($staffId, "You have been assigned {$c['reference']}: " . str_cut($c['title'], 60), 'staff/complaint_details.php?id=' . $id, $id);
        return [true, "Assigned to {$staff['name']}."];
    }

    /** Number of open complaints per active staff id. */
    public static function staffWorkload(): array
    {
        $out = [];
        foreach (Storage::read('complaints') as $c) {
            if ($c['assigned_to'] && is_open($c)) { $out[(int)$c['assigned_to']] = ($out[(int)$c['assigned_to']] ?? 0) + 1; }
        }
        return $out;
    }
}
