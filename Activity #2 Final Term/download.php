<?php
require_once __DIR__ . '/core/bootstrap.php';
$u = Auth::require();
$a = Storage::find('attachments', (int)($_GET['id'] ?? 0));
$c = $a ? Complaint::findFor((int)$a['complaint_id'], $u) : null;
$path = $a ? UPLOAD_PATH . '/' . basename($a['stored_name']) : '';
if (!$a || !$c || !is_file($path)) { http_response_code(404); exit('File not found.'); }
$inline = in_array($a['mime'], ['image/jpeg', 'image/png', 'application/pdf'], true);
header('Content-Type: ' . ($inline ? $a['mime'] : 'application/octet-stream'));
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . rawurlencode($a['original_name']) . '"');
readfile($path);
