<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Logout must be a POST with a CSRF token, otherwise any site could log users out via <img src=logout>.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid()) {
    logout_with_message('success', 'You have been logged out.');   // new session ID, so the message survives
}
redirect('login.php');
