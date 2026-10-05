<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Logout must be a POST with a CSRF token, otherwise any site could log users out via <img src=logout>.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid()) {
    logout_user();
    session_start();                       // fresh session just to show the flash message
    flash_set('success', 'You have been logged out.');
}
redirect('login.php');
