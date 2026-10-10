<?php
/**
 * Shared helper functions.
 * Implements the XSS-prevention (3.5.3) and CSRF-protection (3.5.4)
 * measures described in the project report.
 */

declare(strict_types=1);

/**
 * Escape a value for safe output in HTML. Use this every time
 * user-supplied or database-sourced text is echoed into a page.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate (or reuse) a per-session CSRF token.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Render a hidden CSRF input field for use inside a <form>. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verify a submitted CSRF token. Call this at the top of every
 * POST handler before touching the database.
 */
function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submitted)) {
        http_response_code(403);
        die('Your session expired or the request could not be verified. Please go back and try again.');
    }
}

/** Store a one-time flash message to display after a redirect. */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Pull and clear all pending flash messages. */
function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/** Redirect to another page in this app and stop execution. */
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/** True when a user is currently logged in. */
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

/** Format a decimal amount as Kenyan Shillings, e.g. "KSh 1,250.00". */
function fmt_money(float|string $amount): string
{
    return 'KSh ' . number_format((float) $amount, 2);
}
