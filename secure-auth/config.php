<?php
/**
 * config.php
 * Central configuration: DB credentials, security constants, session setup.
 * Edit the DB_* constants to match your environment before running the app.
 */

declare(strict_types=1);

// ---- Database credentials --------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'auth_demo');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ---- Security / lockout policy ---------------------------------------------
define('MAX_FAILED_ATTEMPTS', 5);     // attempts allowed before lockout
define('LOCKOUT_MINUTES', 15);        // how long the account stays locked
define('PASSWORD_MIN_LENGTH', 8);   
define('RESET_TOKEN_MINUTES', 30);   
                // ---- Session hardening -------------------------------------------------------
              // Must run before session_start() is ever called.
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    // Uncomment the next line when serving the app over HTTPS (recommended in production).
    // ini_set('session.cookie_secure', '1');
    session_start();
}

// ---- Error visibility --------------------------------------------------------
// Never show raw PHP errors to visitors in production; log them instead.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
