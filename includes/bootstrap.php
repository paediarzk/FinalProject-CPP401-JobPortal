<?php
declare(strict_types=1);

/**
 * Loaded at the top of EVERY page. Order matters:
 * config -> database -> helpers -> auth -> session.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,   // JavaScript cannot read the session cookie (limits XSS damage)
        'samesite' => 'Lax',  // cookie not sent on cross-site POSTs (limits CSRF)
    ]);
    session_start();
}
