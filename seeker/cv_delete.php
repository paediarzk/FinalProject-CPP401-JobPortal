<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/cv.php';

$user = require_role('job_seeker');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    flash_set('danger', 'Invalid form submission. Please reload the page and try again.');
    redirect('seeker/profile.php');
}

try {
    delete_cv_for_user($user['id']);
    flash_set('success', 'Your CV has been deleted.');
} catch (PDOException $ex) {
    error_log('CV delete failed: ' . $ex->getMessage());
    flash_set('danger', 'Your CV could not be deleted due to a server error. Please try again.');
}
redirect('seeker/profile.php');
