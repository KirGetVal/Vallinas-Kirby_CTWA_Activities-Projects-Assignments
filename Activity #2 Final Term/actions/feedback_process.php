<?php
require_once __DIR__ . '/../core/bootstrap.php';
$u = Auth::requireRole(ROLE_USER);
require_post();
$c = Complaint::findFor((int)($_POST['complaint_id'] ?? 0), $u);
if (!$c) { http_response_code(404); exit('Complaint not found.'); }
$back = 'user/complaint_details.php?id=' . $c['id'];
$rating = (int)($_POST['rating'] ?? 0);
if ($c['status'] !== STATUS_CLOSED) { flash('error', 'Feedback can only be left once a complaint is closed.'); redirect($back); }
if ($rating < 1 || $rating > 5) { flash('error', 'Select a rating from 1 to 5.'); redirect($back); }
$comment = Validator::clean($_POST['comment'] ?? '');
if (str_len($comment) > 1000) { flash('error', 'Comment is too long.'); redirect($back); }

$saved = false;
Storage::mutate('feedback', function (array $rows) use ($c, $u, $rating, $comment, &$saved) {
    foreach ($rows as $r) { if ((int)$r['complaint_id'] === (int)$c['id']) { return $rows; } }   // one per complaint
    $max = 0; foreach ($rows as $r) { $max = max($max, (int)$r['id']); }
    $rows[] = ['id' => $max + 1, 'complaint_id' => (int)$c['id'], 'user_id' => (int)$u['id'], 'rating' => $rating, 'comment' => $comment, 'created_at' => now()];
    $saved = true;
    return $rows;
});
if ($saved) { log_activity("Feedback submitted for {$c['reference']} ($rating/5)", (int)$c['id']); flash('success', 'Thank you for your feedback!'); }
else { flash('warning', 'Feedback was already submitted for this complaint.'); }
redirect($back);
