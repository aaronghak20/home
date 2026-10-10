<?php
/**
 * Bootstrap: secure session setup + shared includes.
 * Every page in the project starts with:  require __DIR__ . '/includes/bootstrap.php';
 *
 * Implements the session-management measures from Section 3.5.5:
 *  - server-side sessions only (no sensitive data in cookies)
 *  - HttpOnly + Secure cookie flags
 *  - session ID regenerated on privilege change (login) to stop fixation
 *  - automatic expiry after a period of inactivity
 */

declare(strict_types=1);

const SESSION_IDLE_TIMEOUT = 1800; // 30 minutes

// Detect HTTPS so the Secure cookie flag only applies when it will actually work
// (e.g. plain http:// on localhost during development).
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Enforce idle timeout.
if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_IDLE_TIMEOUT) {
    $_SESSION = [];
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();

require __DIR__ . '/../config/database.php';   // provides $pdo
require __DIR__ . '/functions.php';
