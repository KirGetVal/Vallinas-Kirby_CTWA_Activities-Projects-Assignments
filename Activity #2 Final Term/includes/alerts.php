<?php foreach (flash_pull() as $f):
    $cls = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'][$f['type']] ?? 'info'; ?>
<div class="alert alert-<?= $cls ?> alert-dismissible fade show" role="alert">
    <?= e($f['msg']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endforeach; ?>
