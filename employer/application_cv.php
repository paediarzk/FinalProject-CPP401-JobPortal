<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/application.php';

$user = require_role('employer');

// The CV is delivered ONLY if the application belongs to a vacancy of the logged-in employer.
// (cv.php: "the CALLER must already have checked that the current user is allowed to see this file".)
$id = read_id($_GET['id'] ?? null);
$stmt = db()->prepare(
    'SELECT js.cv_path, js.full_name
     FROM applications a
     JOIN vacancies v ON v.id = a.vacancy_id
     JOIN job_seekers js ON js.user_id = a.job_seeker_id
     WHERE a.id = :id AND v.employer_id = :e'
);
$stmt->execute([':id' => $id, ':e' => $user['id']]);
$row = $id > 0 ? $stmt->fetch() : false;

if ($row === false || empty($row['cv_path'])) {
    http_response_code(404);
    exit('CV not found.');
}
send_cv_file($row['cv_path'], cv_download_name($row['full_name']));
