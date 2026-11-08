<?php
require_once __DIR__ . '/../core/bootstrap.php';
$u = Auth::requireRole(ROLE_USER);
require_post();

// One-time form nonce: a refresh/double-click re-POST is rejected instead of creating a duplicate.
$nonce = (string)($_POST['form_nonce'] ?? '');
if ($nonce === '' || !hash_equals((string)($_SESSION['form_nonce'] ?? ''), $nonce)) {
    flash('warning', 'This form was already submitted or has expired.');
    redirect('user/my_complaints.php');
}

$v = new Validator($_POST);
$v->required('title', 'Title')->length('title', 'Title', 5, 150)
  ->required('description', 'Description')->length('description', 'Description', 20, 3000)
  ->required('location', 'Location')->length('location', 'Location', 2, 150)
  ->required('incident_date', 'Incident date')->date('incident_date', 'Incident date')
  ->length('additional_info', 'Additional information', 0, 1500);
$cat = Storage::find('categories', (int)($_POST['category_id'] ?? 0));
if (!$cat || !$cat['active']) { $v->fail('category_id', 'Select a valid category.'); }

// ---- validate every file BEFORE anything is saved ----
$uploads = [];
$f = $_FILES['evidence'] ?? null;
if ($f && is_array($f['name'])) {
    $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
    foreach ($f['name'] as $i => $orig) {
        if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) { continue; }
        if (count($uploads) >= 5) { $v->fail('evidence', 'You can upload at most 5 files.'); break; }
        $ext = strtolower(pathinfo((string)$orig, PATHINFO_EXTENSION));
        if (in_array($f['error'][$i], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) { $v->fail('evidence', "\"$orig\" is larger than the allowed upload size."); break; }
        if ($f['error'][$i] !== UPLOAD_ERR_OK) { $v->fail('evidence', "Upload failed for \"$orig\"."); break; }
        if ($f['size'][$i] > MAX_UPLOAD_BYTES) { $v->fail('evidence', "\"$orig\" is larger than 5 MB."); break; }
        if (!in_array($ext, ALLOWED_UPLOAD_EXT, true)) { $v->fail('evidence', "\"$orig\": file type not allowed."); break; }
        $mime = $finfo ? finfo_file($finfo, $f['tmp_name'][$i]) : '';
        if ($finfo && !in_array($mime, ALLOWED_UPLOAD_MIME, true)) { $v->fail('evidence', "\"$orig\" does not look like a valid $ext file."); break; }
        if (in_array($ext, ['jpg', 'jpeg', 'png'], true) && @getimagesize($f['tmp_name'][$i]) === false) {
            $v->fail('evidence', "\"$orig\" is not a valid image."); break;
        }
        $uploads[] = ['tmp' => $f['tmp_name'][$i], 'orig' => Validator::clean(basename((string)$orig)), 'ext' => $ext, 'size' => (int)$f['size'][$i], 'mime' => $mime ?: 'application/octet-stream'];
    }
}

// Duplicate protection: same user + same title within 2 minutes
if ($v->passes()) {
    foreach (Storage::read('complaints') as $c) {
        if ((int)$c['user_id'] === (int)$u['id'] && strcasecmp($c['title'], $v->get('title')) === 0
            && strtotime($c['created_at']) > time() - 120) {
            flash('warning', 'You already submitted a complaint with this title a moment ago.');
            redirect('user/my_complaints.php');
        }
    }
}

if (!$v->passes()) {
    $_SESSION['errors'] = $v->errors();
    $_SESSION['old'] = array_intersect_key($_POST, array_flip(['title', 'category_id', 'description', 'location', 'incident_date', 'additional_info']));
    redirect('user/submit_complaint.php');
}

unset($_SESSION['form_nonce']);
if (!is_dir(UPLOAD_PATH)) { mkdir(UPLOAD_PATH, 0775, true); }
$c = Complaint::create([
    'title' => $v->get('title'), 'category_id' => (int)$cat['id'], 'description' => $v->get('description'),
    'location' => $v->get('location'), 'incident_date' => $v->get('incident_date'),
    'additional_info' => $v->get('additional_info'),
], (int)$u['id']);

foreach ($uploads as $up) {
    $stored = bin2hex(random_bytes(16)) . '.' . $up['ext'];     // never use the client's filename on disk
    if (move_uploaded_file($up['tmp'], UPLOAD_PATH . '/' . $stored)) {
        Storage::insert('attachments', [
            'complaint_id' => (int)$c['id'], 'original_name' => $up['orig'], 'stored_name' => $stored,
            'mime' => $up['mime'], 'size' => $up['size'], 'uploaded_by' => (int)$u['id'], 'created_at' => now(),
        ]);
    }
}
log_activity("Submitted complaint {$c['reference']}", (int)$c['id']);
Notification::send((int)$u['id'], "Your complaint {$c['reference']} was submitted successfully.", 'user/complaint_details.php?id=' . $c['id'], (int)$c['id']);
Notification::toAdmins("New complaint {$c['reference']} submitted: {$c['title']}", (int)$c['id']);
flash('success', "Complaint submitted. Your reference number is {$c['reference']}.");
redirect('user/complaint_details.php?id=' . $c['id']);
