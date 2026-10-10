<?php
/**
 * Include this (after bootstrap.php) at the top of any page that
 * requires the visitor to be logged in.
 */

declare(strict_types=1);

if (!is_logged_in()) {
    flash('error', 'Please log in to continue.');
    redirect('login.php');
}
