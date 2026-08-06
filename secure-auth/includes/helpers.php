<?php
/**
 * includes/helpers.php
 * Small shared helpers used across the auth pages.
 */

declare(strict_types=1);

/** Generate (or reuse) a per-session CSRF token. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Validate a submitted CSRF token using a timing-safe comparison. */
function csrf_verify(?string $token): bool
{
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/** Redirect somewhere and stop execution. */
function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

/** Escape output for safe HTML insertion. */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/** Require a logged-in session; otherwise bounce to the login page. */
function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        redirect('login.php');
    }
}

/** Generate a URL-safe password-reset token. */
function generate_reset_token(): string
{
    return bin2hex(random_bytes(32));
}