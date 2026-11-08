<?php
declare(strict_types=1);

final class Notification
{
    public static function send(int $userId, string $message, ?string $link = null, ?int $complaintId = null): void
    {
        Storage::insert('notifications', [
            'user_id' => $userId, 'message' => $message, 'link' => $link,
            'complaint_id' => $complaintId, 'read' => false, 'created_at' => now(),
        ]);
    }

    public static function toAdmins(string $message, ?int $complaintId = null): void
    {
        foreach (Storage::where('users', fn($u) => $u['role'] === ROLE_ADMIN && $u['active']) as $a) {
            self::send((int)$a['id'], $message, $complaintId ? 'admin/complaint_details.php?id=' . $complaintId : null, $complaintId);
        }
    }

    public static function forUser(int $userId): array
    {
        $rows = Storage::where('notifications', fn($n) => (int)$n['user_id'] === $userId);
        usort($rows, fn($a, $b) => strcmp($b['created_at'], $a['created_at']) ?: ($b['id'] <=> $a['id']));
        return $rows;
    }

    public static function unreadCount(int $userId): int
    {
        return count(Storage::where('notifications', fn($n) => (int)$n['user_id'] === $userId && !$n['read']));
    }

    public static function markAllRead(int $userId): void
    {
        Storage::mutate('notifications', function (array $rows) use ($userId) {
            foreach ($rows as $i => $n) {
                if ((int)$n['user_id'] === $userId) { $rows[$i]['read'] = true; }
            }
            return $rows;
        });
    }
}
