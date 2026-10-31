<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/application.php';

$user = require_role('job_seeker');          // guests -> login, other roles -> 403

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    flash_set('danger', 'Invalid form submission. Please reload the page and try again.');
    redirect('vacancies.php');
}

$vacancyId = read_id($_POST['vacancy_id'] ?? null);
if ($vacancyId === 0) {
    flash_set('danger', 'Invalid form submission. Please reload the page and try again.');
    redirect('vacancies.php');
}

// The applicant is ALWAYS the logged-in user (session); the status is always 'submitted'.
switch (submit_application($user['id'], $vacancyId)) {
    case 'submitted':
        flash_set('success', 'Your application has been submitted. You can follow its status here.');
        redirect('seeker/applications.php');
    case 'duplicate':
        flash_set('info', 'You have already applied for this vacancy.');
        redirect('seeker/applications.php');
    case 'no_cv':
        flash_set('warning', 'Please upload your CV (PDF) on your profile before applying. The employer reviews your CV.');
        redirect('seeker/profile.php');
    default:                                  // not_found: missing, not published, or the deadline has passed
        flash_set('warning', 'This vacancy is no longer open for applications.');
        redirect('vacancies.php');
}
