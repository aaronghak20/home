<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/helpers.php';

// Only accept logout via POST to avoid CSRF-triggered logouts via a stray <img> tag etc.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

redirect('login.php');
