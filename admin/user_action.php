<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin.php';

$user = require_role('admin');
$back = 'admin/users.php' . (($q = http_build_query(safe_back_query($_POST['back'] ?? ''))) !== '' ? '?' . $q : '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    flash_set('danger', 'Invalid form submission. Please reload the page and try again.');
    redirect('admin/users.php');
}

$rawId  = $_POST['id'] ?? '';
$action = $_POST['action'] ?? '';
if (!is_string($rawId) || !ctype_digit($rawId) || (int)$rawId < 1 || !in_array($action, ['suspend', 'activate'], true)) {
    http_response_code(404);
    exit('Account not found.');
}

try {
    $result = set_user_status($user['id'], (int)$rawId, $action === 'suspend' ? 'suspended' : 'active');
} catch (PDOException $ex) {
    error_log('Account status change failed: ' . $ex->getMessage());
    flash_set('danger', 'The change could not be saved due to a server error. Please try again.');
    redirect($back);
}

if ($result === 'not_found') {
    http_response_code(404);
    exit('Account not found.');
}
$messages = [
    'suspended' => ['success', 'The account has been suspended.'],
    'activated' => ['success', 'The account has been activated.'],
    'unchanged' => ['info', 'Nothing changed: the account already has that status.'],
    'self'      => ['warning', 'You cannot suspend your own account.'],
    'admin'     => ['warning', 'Administrator accounts cannot be changed here.'],
];
[$type, $text] = $messages[$result] ?? ['danger', 'That change is not valid.'];
flash_set($type, $text);
redirect($back);
