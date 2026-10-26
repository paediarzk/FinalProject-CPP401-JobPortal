<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin.php';

$user = require_role('admin');

// A decision changes data: POST with a valid CSRF token only.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    flash_set('danger', 'Invalid form submission. Please reload the page and try again.');
    redirect('admin/vacancies.php');
}

$rawId  = $_POST['id'] ?? '';
$action = $_POST['action'] ?? '';
if (!is_string($rawId) || !ctype_digit($rawId) || (int)$rawId < 1 || !is_string($action)) {
    http_response_code(404);
    exit('Vacancy not found.');
}
$id = (int)$rawId;

$note = null;
if ($action === 'reject' || $action === 'takedown') {
    [$note, $noteError] = validate_moderation_note(is_string($_POST['note'] ?? null) ? $_POST['note'] : '');
    if ($noteError !== null) {
        flash_set('danger', $noteError);
        redirect('admin/vacancy_view.php?id=' . $id);
    }
}

try {
    $result = moderate_vacancy($id, $action, $note);
} catch (PDOException $ex) {
    error_log('Moderation failed: ' . $ex->getMessage());
    flash_set('danger', 'The decision could not be saved due to a server error. Please try again.');
    redirect('admin/vacancy_view.php?id=' . $id);
}

if ($result === 'not_found') {
    http_response_code(404);
    exit('Vacancy not found.');
}
$messages = [
    'approved'           => ['success', 'The vacancy has been approved and is now published.'],
    'rejected'           => ['success', 'The vacancy has been rejected. The employer can read your reason and submit it again.'],
    'taken_down'         => ['success', 'The vacancy has been taken down.'],
    'invalid_state'      => ['warning', 'This vacancy was already handled or is no longer in the right state for that decision.'],
    'expired'            => ['warning', 'The deadline has already passed, so the vacancy cannot be approved. Reject it with a reason instead.'],
    'employer_suspended' => ['warning', 'The employer account is suspended, so the vacancy cannot be approved.'],
    'invalid_action'     => ['danger', 'That decision is not valid.'],
];
[$type, $text] = $messages[$result] ?? ['danger', 'That decision is not valid.'];
flash_set($type, $text);

// After a finished decision go back to the waiting list; after a refusal stay on the vacancy.
redirect(in_array($result, ['approved', 'rejected', 'taken_down'], true) ? 'admin/vacancies.php?status=pending' : 'admin/vacancy_view.php?id=' . $id);
