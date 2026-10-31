<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/application.php';

$user = require_role('employer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    flash_set('danger', 'Invalid form submission. Please reload the page and try again.');
    redirect('employer/applications.php');
}
$id     = read_id($_POST['id'] ?? null);
$status = is_string($_POST['status'] ?? null) ? $_POST['status'] : '';

// The employer comes from the session. The model checks that the application belongs to one of THEIR vacancies.
$result = $id > 0 ? set_application_status($id, $user['id'], $status) : 'not_found';

$messages = [
    'updated'            => ['success', 'The application status has been updated.'],
    'not_found'          => ['danger',  'Application not found.'],
    'invalid_status'     => ['danger',  'Invalid status.'],
    'invalid_transition' => ['warning', 'This application can no longer be changed to that status.'],
];
[$type, $text] = $messages[$result];
flash_set($type, $text);
redirect($result === 'not_found' ? 'employer/applications.php' : 'employer/application_view.php?id=' . $id);
