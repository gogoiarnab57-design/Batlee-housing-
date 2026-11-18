<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

// Logout is POST + CSRF so other sites cannot log a visitor out by embedding a link.
if (is_post() && csrf_valid()) {
    logout_user();
    flash('You have been logged out.');
}
redirect(url('index.php'));
