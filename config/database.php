<?php
declare(strict_types=1);

/**
 * Returns ONE shared PDO connection (Singleton via static variable).
 * Why PDO? It supports prepared statements, which separate SQL code from user
 * data and therefore prevent SQL injection.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // errors become exceptions
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // rows as ['column' => value]
            PDO::ATTR_EMULATE_PREPARES   => false,                  // REAL server-side prepared statements
        ]);
    }
    return $pdo;
}
