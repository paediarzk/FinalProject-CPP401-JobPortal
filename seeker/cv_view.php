<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/profile.php';
require_once __DIR__ . '/../includes/cv.php';

$user    = require_role('job_seeker');
// Only the logged-in user's own CV can be opened: the owner comes from the session,
// so parameters such as ?id=5 in the URL are simply never read.
$profile = get_job_seeker_profile($user['id']);

if ($profile === null || empty($profile['cv_path'])) {
    http_response_code(404);
    exit('You have not uploaded a CV yet.');
}

send_cv_file($profile['cv_path'], cv_download_name($profile['full_name']));
