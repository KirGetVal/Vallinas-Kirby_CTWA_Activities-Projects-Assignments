<?php
require_once __DIR__ . '/../core/bootstrap.php';
$u = Auth::requireRole(ROLE_ADMIN);
require_post();
$back = 'admin/categories.php';
$fail = function (string $m) use ($back): never { flash('error', $m); redirect($back); };

$name = Validator::clean($_POST['name'] ?? '');
$taken = function (string $n, int $except = 0): bool {
    foreach (Storage::read('categories') as $c) {
        if (str_lower($c['name']) === str_lower($n) && (int)$c['id'] !== $except) { return true; }
    }
    return false;
};
$lenOk = fn(string $n) => str_len($n) >= 2 && str_len($n) <= 80;

switch ((string)($_POST['action'] ?? '')) {
    case 'add':
        if (!$lenOk($name)) { $fail('Category name must be between 2 and 80 characters.'); }
        if ($taken($name)) { $fail('A category with that name already exists.'); }
        $c = Storage::insert('categories', ['name' => $name, 'active' => true, 'created_at' => now()]);
        log_activity("Added category \"$name\"", (int)$c['id']);
        flash('success', 'Category added.');
        break;
    case 'rename':
        $c = Storage::find('categories', (int)($_POST['category_id'] ?? 0)) ?? $fail('Category not found.');
        if (!$lenOk($name)) { $fail('Category name must be between 2 and 80 characters.'); }
        if ($taken($name, (int)$c['id'])) { $fail('A category with that name already exists.'); }
        Storage::update('categories', (int)$c['id'], ['name' => $name]);
        log_activity("Renamed category \"{$c['name']}\" to \"$name\"", (int)$c['id']);
        flash('success', 'Category updated.');
        break;
    case 'toggle':
        $c = Storage::find('categories', (int)($_POST['category_id'] ?? 0)) ?? $fail('Category not found.');
        if ($c['active']) {   // never leave users with nothing to choose
            $others = Storage::where('categories', fn($x) => $x['active'] && (int)$x['id'] !== (int)$c['id']);
            if (!$others) { $fail('At least one category must stay active.'); }
        }
        Storage::update('categories', (int)$c['id'], ['active' => !$c['active']]);
        log_activity(($c['active'] ? 'Deactivated' : 'Activated') . " category \"{$c['name']}\"", (int)$c['id']);
        flash('success', "\"{$c['name']}\" " . ($c['active'] ? 'deactivated. Existing complaints keep it.' : 'activated.'));
        break;
    default:
        http_response_code(400); exit('Unknown action.');
}
redirect($back);
