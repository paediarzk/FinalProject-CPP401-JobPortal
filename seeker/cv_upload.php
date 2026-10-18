<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/cv.php';

$user = require_role('job_seeker');          // guests -> login, other roles -> 403

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('seeker/profile.php');
}

// If the upload exceeds PHP's post_max_size, PHP discards BOTH $_POST and $_FILES.
// Without this check the user would wrongly see a "CSRF" error.
if (empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    flash_set('danger', 'The file is too large. Please upload a PDF of at most 2 MB.');
    redirect('seeker/profile.php');
}

if (!csrf_valid()) {
    flash_set('danger', 'Invalid form submission. Please reload the page and try again.');
    redirect('seeker/profile.php');
}

// The owner is ALWAYS the logged-in user (session), never a value from the request.
$errors = store_cv_for_user($user['id'], $_FILES['cv'] ?? []);

if ($errors) {
    flash_set('danger', implode(' ', $errors));
} else {
    flash_set('success', 'Your CV has been uploaded.');
}
redirect('seeker/profile.php');                // Post/Redirect/Get
