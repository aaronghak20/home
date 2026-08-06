<?php
/**
 * db.php
 * Returns a shared PDO connection using prepared-statement-friendly defaults.
 */

declare(strict_types=1);
require_once __DIR__ . '/config.php';

function get_pdo(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // real prepared statements, blocks SQL injection
        ]);
    }

    return $pdo;
}
