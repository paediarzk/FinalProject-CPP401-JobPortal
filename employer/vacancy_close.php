<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/vacancy.php';

$user = require_role('employer');

// Closing changes data, so it must be a POST with a valid CSRF token (a link or an <img> cannot trigger it).
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    flash_set('danger', 'Invalid form submission. Please reload the page and try again.');
    redirect('employer/vacancies.php');
}

$rawId = $_POST['id'] ?? '';
if (!is_string($rawId) || !ctype_digit($rawId) || (int)$rawId < 1) {
    http_response_code(404);
    exit('Vacancy not found.');
}

try {
    $result = close_vacancy((int)$rawId, $user['id']);        // the employer comes from the session
} catch (PDOException $ex) {
    error_log('Vacancy close failed: ' . $ex->getMessage());
    flash_set('danger', 'The vacancy could not be closed due to a server error. Please try again.');
    redirect('employer/vacancies.php');
}

if ($result === 'not_found') {                                // missing, or it belongs to another employer
    http_response_code(404);
    exit('Vacancy not found.');
}
flash_set($result === 'closed' ? 'success' : 'info',
          $result === 'closed' ? 'The vacancy has been closed.' : 'This vacancy was already closed.');
redirect('employer/vacancies.php');
